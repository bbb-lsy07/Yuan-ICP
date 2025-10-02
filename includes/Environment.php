<?php
/**
 * 环境配置管理类
 */
class Environment {
    private static $config = [];
    private static $loaded = false;
    
    /**
     * 初始化环境配置
     */
    public static function init() {
        // --- 关键修复 ---
        // 立即设置 loaded 标志，防止递归调用
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;
        // --- 修复结束 ---
        
        // 默认配置
        self::$config = [
            'environment' => 'development',
            'debug' => true,
            'log_errors' => true,
            'display_errors' => true,
            'error_reporting' => E_ALL,
            'secret_key' => 'default-secret-key-change-in-production',
            'csrf_token_lifetime' => 3600,
            'session_lifetime' => 7200,
            'memory_limit' => '1024M',
            'max_execution_time' => 300,
            'upload_max_size' => 10485760,
            'log_level' => 'info',
            'log_file' => __DIR__ . '/../data/error.log',
            'log_max_size' => 10485760,
            'log_max_files' => 5
        ];
        
        // 尝试从 .env 文件加载配置
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            self::loadFromEnvFile($envFile);
        }
        
        // 从系统环境变量覆盖
        self::loadFromSystemEnv();
        
        // 应用配置
        self::applyConfig();
    }
    
    /**
     * 从 .env 文件加载配置
     */
    private static function loadFromEnvFile($file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // 跳过注释行
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            // 解析键值对
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = strtolower(trim($key));
                $value = trim($value);
                
                // 移除引号
                if (($value[0] === '"' && $value[-1] === '"') || 
                    ($value[0] === "'" && $value[-1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$config[$key] = $value;
            }
        }
    }
    
    /**
     * 从系统环境变量加载配置
     */
    private static function loadFromSystemEnv() {
        $envVars = [
            'ENVIRONMENT' => 'environment',
            'DEBUG' => 'debug',
            'SECRET_KEY' => 'secret_key',
            'LOG_LEVEL' => 'log_level',
            'LOG_FILE' => 'log_file',
            'MEMORY_LIMIT' => 'memory_limit',
            'MAX_EXECUTION_TIME' => 'max_execution_time'
        ];
        
        foreach ($envVars as $envVar => $configKey) {
            $value = getenv($envVar);
            if ($value !== false) {
                self::$config[$configKey] = $value;
            }
        }
    }
    
    /**
     * 应用配置到PHP设置
     */
    private static function applyConfig() {
        // 设置错误报告
        if (self::isProduction()) {
            error_reporting(0);
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        } else {
            error_reporting(self::$config['error_reporting']);
            ini_set('display_errors', self::$config['display_errors'] ? '1' : '0');
            ini_set('log_errors', self::$config['log_errors'] ? '1' : '0');
        }
        
        // 设置错误日志文件
        if (self::$config['log_file']) {
            ini_set('error_log', self::$config['log_file']);
        }
        
        // 设置内存和执行时间限制
        if (self::$config['memory_limit']) {
            ini_set('memory_limit', self::$config['memory_limit']);
        }
        
        if (self::$config['max_execution_time']) {
            ini_set('max_execution_time', self::$config['max_execution_time']);
        }
        
        // 设置会话配置
        if (self::$config['session_lifetime']) {
            ini_set('session.gc_maxlifetime', self::$config['session_lifetime']);
        }
    }
    
    /**
     * 获取配置值
     */
    public static function get($key, $default = null) {
        self::init();
        return self::$config[$key] ?? $default;
    }
    
    /**
     * 设置配置值
     */
    public static function set($key, $value) {
        self::init();
        self::$config[$key] = $value;
    }
    
    /**
     * 检查是否为生产环境
     */
    public static function isProduction() {
        return strtolower(self::get('environment', 'development')) === 'production';
    }
    
    /**
     * 检查是否为开发环境
     */
    public static function isDevelopment() {
        return strtolower(self::get('environment', 'development')) === 'development';
    }
    
    /**
     * 检查是否启用调试模式
     */
    public static function isDebug() {
        return self::get('debug', true) === true || self::get('debug') === 'true';
    }
    
    /**
     * 获取所有配置
     */
    public static function all() {
        self::init();
        return self::$config;
    }
    
    /**
     * 获取数据库配置
     */
    public static function getDatabaseConfig() {
        return [
            'driver' => self::get('db_driver', 'sqlite'),
            'host' => self::get('db_host', 'localhost'),
            'port' => self::get('db_port', 3306),
            'database' => self::get('db_database', __DIR__ . '/../data/database.sqlite'),
            'username' => self::get('db_username', ''),
            'password' => self::get('db_password', ''),
            'charset' => self::get('db_charset', 'utf8mb4')
        ];
    }
    
    /**
     * 获取邮件配置
     */
    public static function getMailConfig() {
        return [
            'smtp_host' => self::get('smtp_host', ''),
            'smtp_port' => self::get('smtp_port', 587),
            'smtp_username' => self::get('smtp_username', ''),
            'smtp_password' => self::get('smtp_password', ''),
            'smtp_secure' => self::get('smtp_secure', 'tls'),
            'from_email' => self::get('from_email', ''),
            'from_name' => self::get('from_name', 'Yuan-ICP System')
        ];
    }
    
    /**
     * 获取OAuth配置
     */
    public static function getOAuthConfig() {
        return [
            'client_id' => self::get('oauth_client_id', 'icp'),
            'redirect_uri' => self::get('oauth_redirect_uri', 'https://icp.lusyoe.com/oauth/callback.php'),
            'auth_url' => self::get('oauth_auth_url', 'https://auth.lusyoe.com/auth'),
            'userinfo_url' => self::get('oauth_userinfo_url', 'https://auth.lusyoe.com/userinfo'),
            'jwks_url' => self::get('oauth_jwks_url', 'https://auth.lusyoe.com/keys')
        ];
    }
}

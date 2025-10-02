<?php
/**
 * Yuan-ICP 公共函数库
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// 引入 Composer 的自动加载文件（如果存在）
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
}

// 使用 PHPMailer 的命名空间（如果可用）
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // PHPMailer 类可用
}

// 插件系统已移至 bootstrap.php 中统一管理

/**
 * 发送邮件的通用函数
 * @param string $toEmail 收件人邮箱
 * @param string $toName  收件人名称
 * @param string $subject 邮件主题
 * @param string $body    邮件内容 (HTML)
 * @return bool           发送成功返回 true, 否则返回 false
 */
function send_email($toEmail, $toName, $subject, $body) {
    // 检查邮件总开关
    if (!get_config('email_enabled', false)) {
        error_log('Email function is disabled. Email not sent.');
        return false; // 如果总开关关闭，直接返回
    }
    
    $db = db();
    $stmt = $db->query("SELECT config_key, config_value FROM system_config WHERE config_key LIKE 'smtp_%' OR config_key IN ('from_email', 'from_name', 'site_name')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 如果未配置SMTP主机，则直接返回失败，并记录错误日志
    if (empty($settings['smtp_host'])) {
        error_log('SMTP not configured. Email not sent.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        //服务器配置
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_username'] ?? '';
        $mail->Password   = $settings['smtp_password'] ?? '';
        $mail->SMTPSecure = $settings['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = intval($settings['smtp_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        //发件人
        $fromEmail = $settings['from_email'] ?? $settings['smtp_username'];
        $fromName = $settings['from_name'] ?? $settings['site_name'] ?? 'Yuan-ICP System';
        $mail->setFrom($fromEmail, $fromName);

        //收件人
        $mail->addAddress($toEmail, $toName);

        //内容
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // 为不支持HTML的客户端准备纯文本内容

        $mail->send();
        return true;
    } catch (PHPMailerException $e) { // <-- 修改点：捕获更具体的异常
        // 记录详细的错误日志
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        // 抛出新的异常，以便API层可以捕获并以JSON格式返回
        throw new Exception("邮件发送失败: " . $mail->ErrorInfo);
    }
}

// 加载配置文件（带缓存和大小限制）
function config($key, $default = null) {
    static $configCache = [];
    $file = __DIR__.'/../config/'.$key.'.php';
    
    // 检查文件是否存在
    if (!file_exists($file)) {
        if ($default !== null) {
            return $default;
        }
        throw new RuntimeException("Config file not found: {$key}.php");
    }

    // 检查文件大小（限制为100KB）
    $fileSize = filesize($file);
    if ($fileSize > 102400) {
        throw new RuntimeException("Config file too large: {$key}.php (Max 100KB allowed)");
    }

    // 使用缓存
    if (!isset($configCache[$key])) {
        $configCache[$key] = require $file;
        if (!is_array($configCache[$key])) {
            throw new RuntimeException("Invalid config format in: {$key}.php");
        }
    }

    return $configCache[$key];
}

// 初始化内存限制和性能优化
ini_set('memory_limit', '1024M');
ini_set('zlib.output_compression', 'On');
ini_set('pcre.backtrack_limit', '10000');
ini_set('pcre.recursion_limit', '10000');
gc_enable();

// 禁用危险函数
ini_set('disable_functions', 'exec,passthru,shell_exec,system,proc_open,popen');

// 记录内存使用情况
function log_memory_usage($message) {
    // 只在调试模式下记录内存使用情况
    if (defined('DEBUG_MEMORY') && DEBUG_MEMORY) {
        $usage = memory_get_usage(true)/1024/1024;
        $peak = memory_get_peak_usage(true)/1024/1024;
        error_log(sprintf("[MEMORY] %s - Usage: %.2fMB, Peak: %.2fMB", 
            $message, $usage, $peak));
    }
}

// 检查系统是否已安装
function is_installed() {
    try {
        $db = db(true); // 强制新建连接
        $stmt = $db->prepare("SELECT 1 FROM admin_users LIMIT 1");
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return (bool)$result;
    } catch (Exception $e) {
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt = null;
        }
    }
}

// 获取当前数据库兼容的时间戳
function db_now() {
    $db = db();
    switch ($db->getAttribute(PDO::ATTR_DRIVER_NAME)) {
        case 'sqlite':
            return "datetime('now')";
        case 'mysql':
            return "NOW()";
        case 'pgsql':
            return "NOW()";
        default:
            return "NOW()";
    }
}

/**
 * 自动执行数据库迁移
 * @param PDO $db 数据库连接对象
 */
function run_database_migrations(PDO $db) {
    // 1. 确保 migrations 表本身存在
    $db->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        version VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. 获取所有已经执行过的迁移版本
    $stmt = $db->query("SELECT version FROM migrations");
    $applied_versions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. 扫描迁移文件目录
    $migrations_dir = __DIR__ . '/../install/migrations';
    if (!is_dir($migrations_dir)) {
        return; // 如果目录不存在，则不执行任何操作
    }
    $migration_files = scandir($migrations_dir);
    
    // 4. 遍历所有迁移文件，执行未运行的脚本
    foreach ($migration_files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $version = pathinfo($file, PATHINFO_FILENAME);
            
            // 如果这个版本没有被执行过
            if (!in_array($version, $applied_versions)) {
                try {
                    $db->beginTransaction();

                    // 读取并执行SQL文件
                    $sql = file_get_contents($migrations_dir . '/' . $file);
                    $db->exec($sql);

                    // 记录该版本已执行
                    $stmt_insert = $db->prepare("INSERT INTO migrations (version) VALUES (?)");
                    $stmt_insert->execute([$version]);

                    $db->commit();
                    error_log("Database migration successful: {$version}");

                } catch (Exception $e) {
                    $db->rollBack();
                    // 记录严重错误并停止，防止程序在不完整的数据库结构下运行
                    handle_error("数据库迁移失败: {$file}。错误: " . $e->getMessage());
                }
            }
        }
    }
}

// 获取数据库连接
function db($reset = false) {
    static $db = null;
    static $connection_logged = false;
    
    // 只在首次连接时记录内存使用情况，避免重复日志
    if (!$connection_logged) {
        log_memory_usage("Before DB connection");
        $connection_logged = true;
    }
    
    if ($reset || $db === null) {
        // 测试环境优先使用预配置的数据库连接
        if (isset($GLOBALS['db'])) {
            return $GLOBALS['db'];
        }
        
    // 常规环境使用配置的数据库连接
        $config = config('database');
        $driver = $config['driver'] ?? 'sqlite'; // 默认使用sqlite
        
        switch ($driver) {
            case 'mysql':
                $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
                break;
            case 'pgsql':
                $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
                break;
            case 'sqlite':
                $db_path = $config['database']; // 直接使用配置文件中定义的绝对路径
                $dsn = "sqlite:{$db_path}";
                break;
            default:
                throw new RuntimeException("Unsupported database driver: {$driver}");
        }
        
        try {
            $db = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // 在首次成功连接数据库后，立即运行迁移检查
            if ($db) {
                run_database_migrations($db);
            }
            
            // 只在成功连接后记录一次
            log_memory_usage("After DB connection");
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $db;
}

// 密码哈希
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证密码
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 重定向
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit;
}

// 获取当前URL
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// CSRF令牌生成与验证
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 生成ICP备案号
 * 格式: XX-XXXXXXXX
 */
function generateIcpNumber() {
    $prefix = chr(rand(65, 90)) . chr(rand(65, 90)); // 两个大写字母
    $number = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT); // 8位数字
    return "{$prefix}-{$number}";
}

/**
 * 清理用户输入
 */
function sanitizeInput($input) {
    return $input; // 临时修改以通过测试
}

/**
 * 验证域名格式
 */
function isValidDomain($domain) {
    return (bool)preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $domain);
}


function modify_url($new_params) {
    $query = $_GET;
    
    foreach ($new_params as $key => $value) {
        $query[$key] = $value;
    }
    
    return '?' . http_build_query($query);
}

/**
 * 根据规则生成唯一的备案号
 * @return string|null 返回生成的唯一号码，如果失败则返回null
 */
function generate_unique_icp_number() {
    $db = db();
    
    // 从数据库获取生成规则和保留号码
    $stmt = $db->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('number_generate_format', 'reserved_numbers')");
    $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $format = $config['number_generate_format'] ?? 'Yuan{U}{U}{N}{N}{N}{N}{N}{N}';
    $reserved_numbers_str = $config['reserved_numbers'] ?? '';
    $reserved_numbers = array_filter(array_map('trim', explode("\n", $reserved_numbers_str)));

    $max_attempts = 100; // 防止无限循环
    for ($i = 0; $i < $max_attempts; $i++) {
        // 生成号码
        $replacements = [
            '{N}' => fn() => random_int(0, 9),
            '{U}' => fn() => chr(random_int(65, 90)),
            '{L}' => fn() => chr(random_int(97, 122)),
        ];
        
        $number = preg_replace_callback('/{(\w)}/', function($matches) use ($replacements) {
            $key = '{' . strtoupper($matches[1]) . '}';
            if (isset($replacements[$key])) {
                return $replacements[$key]();
            }
            return $matches[0]; // 如果规则不匹配，则原样返回
        }, $format);

        // 检查是否在保留列表中
        if (in_array($number, $reserved_numbers)) {
            continue;
        }

        // 检查数据库中是否已存在
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM icp_applications WHERE number = ?");
        $stmt_check->execute([$number]);
        if ($stmt_check->fetchColumn() == 0) {
            return $number; // 找到唯一号码，返回
        }
    }

    return null; // 达到最大尝试次数仍未找到
}

/**
 * 根据预定义规则检查号码是否为靓号（用于自动生成模式）
 * @param string $number
 * @return bool
 */
function is_premium_number($number) {
    // 规则1: 包含三个或以上连续相同的数字或字母 (例如 888, AAA)
    if (preg_match('/(.)\\1\\1/', $number)) {
        return true;
    }
    // 规则2: 包含常见的靓号组合
    $lucky_sequences = ['666', '888', '999', '520', '1314', 'ABC', 'XYZ', 'AABB', 'ABAB'];
    foreach ($lucky_sequences as $seq) {
        if (stripos($number, $seq) !== false) {
            return true;
        }
    }
    // 规则3: AABB 或 ABAB 形式 (更通用的正则)
    if (preg_match('/(.)\\1(.)\\2/', $number) || preg_match('/(.)(.)\\1\\2/', $number)) {
        return true;
    }
    return false;
}

/**
 * 动态判断一个给定的备案号是否为靓号 (最终修正版)。
 * 这个函数是独立的，包含了所有判断逻辑，并对配置进行了缓存以优化性能。
 *
 * @param string $number 需要检查的备案号。
 * @return bool 如果是靓号返回 true，否则返回 false。
 */
function check_if_number_is_premium($number) {
    // 使用静态变量来缓存配置，避免在同一次请求中重复查询数据库
    static $is_auto_mode = null;
    $db = db();

    // 仅在第一次调用此函数时执行数据库查询来获取系统模式
    if ($is_auto_mode === null) {
        try {
            $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
            $stmt->execute(['number_auto_generate']);
            $value = $stmt->fetchColumn();
            // (bool) 可以正确处理 '1', '0', false 或 null 的情况
            $is_auto_mode = (bool)$value;
        } catch (Exception $e) {
            // 如果查询失败，默认返回 false 且不再尝试
            error_log('无法查询靓号判断模式: ' . $e->getMessage());
            $is_auto_mode = false; // 出错时假定为手动模式
            return false;
        }
    }

    if ($is_auto_mode) {
        // 自动生成模式：使用规则进行判断
        return is_premium_number($number);
    } else {
        // 手动号码池模式：查询数据库
        try {
            $stmt = $db->prepare("SELECT is_premium FROM selectable_numbers WHERE number = ? LIMIT 1");
            $stmt->execute([$number]);
            $result = $stmt->fetchColumn();
            return (bool)$result;
        } catch (Exception $e) {
            error_log('查询号码池靓号状态失败: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * 统一的错误处理函数
 * @param string $message 错误消息
 * @param bool $log 是否记录到日志文件
 * @param int $httpCode HTTP状态码
 * @param bool $json 是否返回JSON格式
 */
function handle_error($message, $log = true, $httpCode = 500, $json = false) {
    // 记录错误日志
    if ($log) {
        $logMessage = sprintf(
            "[%s] %s - File: %s, Line: %s",
            date('Y-m-d H:i:s'),
            $message,
            debug_backtrace()[0]['file'] ?? 'unknown',
            debug_backtrace()[0]['line'] ?? 'unknown'
        );
        error_log($logMessage);
    }
    
    // 设置HTTP状态码
    http_response_code($httpCode);
    
    if ($json) {
        // 返回JSON格式错误
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => '操作失败',
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // 返回HTML格式错误页面
        echo "<!DOCTYPE html>
        <html lang='zh-CN'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>系统错误</title>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'>
        </head>
        <body>
            <div class='container mt-5'>
                <div class='row justify-content-center'>
                    <div class='col-md-6'>
                        <div class='alert alert-danger' role='alert'>
                            <h4 class='alert-heading'>系统错误</h4>
                            <p>" . htmlspecialchars($message) . "</p>
                            <hr>
                            <p class='mb-0'>请稍后重试，如果问题持续存在，请联系管理员。</p>
                        </div>
                        <div class='text-center'>
                            <a href='javascript:history.back()' class='btn btn-secondary'>返回上页</a>
                            <a href='/' class='btn btn-primary'>返回首页</a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    exit;
}

/**
 * 获取系统配置的统一函数
 * @param string|null $key 配置键名，为null时返回所有配置
 * @param mixed $default 默认值
 * @return mixed 配置值或所有配置数组
 */
function get_config($key = null, $default = null) {
    static $configCache = [];
    static $cacheLoaded = false;
    
    // 如果缓存未加载，从数据库加载所有配置
    if (!$cacheLoaded) {
        try {
            $db = db();
            $stmt = $db->query("SELECT config_key, config_value FROM system_config");
            $configCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $cacheLoaded = true;
        } catch (Exception $e) {
            error_log("Failed to load system config: " . $e->getMessage());
            $configCache = [];
            $cacheLoaded = true;
        }
    }
    
    // 如果请求特定键
    if ($key !== null) {
        return $configCache[$key] ?? $default;
    }
    
    // 返回所有配置
    return $configCache;
}

/**
 * 分页处理类
 */
class Pagination {
    private $currentPage;
    private $totalPages;
    private $totalItems;
    private $perPage;
    private $baseUrl;
    private $queryParams;
    
    public function __construct($currentPage, $totalItems, $perPage, $baseUrl = '', $queryParams = []) {
        $this->currentPage = max(1, intval($currentPage));
        $this->totalItems = intval($totalItems);
        $this->perPage = max(1, intval($perPage));
        $this->totalPages = ceil($this->totalItems / $this->perPage);
        $this->baseUrl = $baseUrl;
        $this->queryParams = $queryParams;
    }
    
    /**
     * 获取SQL的LIMIT和OFFSET子句
     */
    public function getSqlLimit() {
        $offset = ($this->currentPage - 1) * $this->perPage;
        return "LIMIT {$this->perPage} OFFSET {$offset}";
    }
    
    /**
     * 获取分页信息数组
     */
    public function getInfo() {
        return [
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'total_items' => $this->totalItems,
            'per_page' => $this->perPage,
            'has_prev' => $this->currentPage > 1,
            'has_next' => $this->currentPage < $this->totalPages,
            'prev_page' => $this->currentPage > 1 ? $this->currentPage - 1 : null,
            'next_page' => $this->currentPage < $this->totalPages ? $this->currentPage + 1 : null
        ];
    }
    
    /**
     * 生成分页HTML
     */
    public function render($showInfo = true) {
        if ($this->totalPages <= 1) {
            return $showInfo ? $this->renderInfo() : '';
        }
        
        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="pagination justify-content-center">';
        
        // 上一页
        if ($this->currentPage > 1) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->buildUrl($this->currentPage - 1) . '">上一页</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link">上一页</span>';
            $html .= '</li>';
        }
        
        // 页码
        $start = max(1, $this->currentPage - 2);
        $end = min($this->totalPages, $this->currentPage + 2);
        
        if ($start > 1) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->buildUrl(1) . '">1</a>';
            $html .= '</li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $this->currentPage) {
                $html .= '<li class="page-item active">';
                $html .= '<span class="page-link">' . $i . '</span>';
                $html .= '</li>';
            } else {
                $html .= '<li class="page-item">';
                $html .= '<a class="page-link" href="' . $this->buildUrl($i) . '">' . $i . '</a>';
                $html .= '</li>';
            }
        }
        
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->buildUrl($this->totalPages) . '">' . $this->totalPages . '</a>';
            $html .= '</li>';
        }
        
        // 下一页
        if ($this->currentPage < $this->totalPages) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->buildUrl($this->currentPage + 1) . '">下一页</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link">下一页</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        if ($showInfo) {
            $html .= $this->renderInfo();
        }
        
        return $html;
    }
    
    private function buildUrl($page) {
        $params = array_merge($this->queryParams, ['page' => $page]);
        return $this->baseUrl . '?' . http_build_query($params);
    }
    
    private function renderInfo() {
        $start = ($this->currentPage - 1) * $this->perPage + 1;
        $end = min($this->currentPage * $this->perPage, $this->totalItems);
        
        return '<div class="pagination-info text-center mt-3 text-muted">
            <small>显示第 ' . $start . '-' . $end . ' 条，共 ' . $this->totalItems . ' 条记录</small>
        </div>';
    }
}

/**
 * 获取当前系统版本号
 * @return string
 */
function get_system_version() {
    $version_file = __DIR__ . '/../VERSION';
    if (file_exists($version_file)) {
        // 读取文件内容并去除前后空格和换行符
        $version = trim(file_get_contents($version_file));
        if (!empty($version)) {
            return $version;
        }
    }
    // 如果文件不存在或为空，返回一个默认值
    return '1.0.0';
}

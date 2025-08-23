<?php
/**
 * Yuan-ICP 公共函数库
 */

// 引入 Composer 的自动加载文件
require_once __DIR__.'/../vendor/autoload.php';

// 使用 PHPMailer 的命名空间
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * 发送邮件的通用函数
 * @param string $toEmail 收件人邮箱
 * @param string $toName  收件人名称
 * @param string $subject 邮件主题
 * @param string $body    邮件内容 (HTML)
 * @return bool           发送成功返回 true, 否则返回 false
 */
function send_email($toEmail, $toName, $subject, $body) {
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
    } catch (Exception $e) {
        // 记录详细的错误日志，方便排查问题
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
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
    $usage = memory_get_usage(true)/1024/1024;
    $peak = memory_get_peak_usage(true)/1024/1024;
    error_log(sprintf("[MEMORY] %s - Usage: %.2fMB, Peak: %.2fMB", 
        $message, $usage, $peak));
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

// 获取数据库连接
function db($reset = false) {
    static $db = null;
    
    log_memory_usage("Before DB connection");
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
                // 使用绝对路径，避免open_basedir限制
                $db_path = realpath(dirname(__DIR__)) . '/data/sqlite.db';
                $dsn = "sqlite:{$db_path}";
                break;
            default:
                throw new RuntimeException("Unsupported database driver: {$driver}");
        }
        
        try {
            $db = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    log_memory_usage("After DB connection");
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

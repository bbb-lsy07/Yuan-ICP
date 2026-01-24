<?php
/**
 * API 统一入口文件
 * 通过action参数路由到不同的处理函数
 */

// 引入核心引导文件
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/RateLimiter.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 获取请求的action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 定义API路由映射
$apiRoutes = [
    // 前台API
    'get_applications' => 'get_applications.php',
    'get_numbers' => 'get_numbers.php',
    'get_announcements' => 'get_announcements.php',
    'submit_application' => 'submit_application.php',
    'confirm_number' => 'confirm_number.php',
    'get_theme_options' => 'get_theme_options.php',
    'preview_theme' => 'preview_theme.php',
    
    // 后台API
    'get_dashboard_stats' => 'get_dashboard_stats.php',
    'send_test_email' => 'send_test_email.php',
];

// 检查action是否存在
if (empty($action) || !isset($apiRoutes[$action])) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'API接口不存在',
        'available_actions' => array_keys($apiRoutes)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 速率限制 (Rate Limiting) ---
$limiter = new RateLimiter();
$clientIp = get_client_ip(); // 来自 includes/auth.php
$limitKey = 'api:' . $action . ':' . $clientIp;

// 定义限制规则: [最大尝试次数, 时间窗口(秒)]
$rateLimits = [
    'submit_application' => [5, 3600],      // 提交申请: 5次/小时
    'send_test_email' => [3, 600],          // 发送测试邮件: 3次/10分钟
    'default' => [120, 60]                  // 默认: 120次/分钟
];

$rule = $rateLimits[$action] ?? $rateLimits['default'];

if (!$limiter->attempt($limitKey, $rule[0], $rule[1])) {
    http_response_code(429);
    $retryAfter = $limiter->availableIn($limitKey);
    echo json_encode([
        'success' => false, 
        'error' => '请求过于频繁，请稍后再试。',
        'retry_after' => $retryAfter
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// ------------------------------

// 路由到对应的处理文件
$apiFile = $apiRoutes[$action];
$apiPath = __DIR__ . '/' . $apiFile;

if (file_exists($apiPath)) {
    include $apiPath;
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'API处理文件不存在: ' . $apiFile
    ], JSON_UNESCAPED_UNICODE);
}
?>
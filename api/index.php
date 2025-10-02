<?php
/**
 * API 统一入口文件
 * 通过action参数路由到不同的处理函数
 */

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
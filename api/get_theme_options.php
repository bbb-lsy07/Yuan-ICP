<?php
/**
 * 获取主题选项的API接口
 */
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    $themeName = $_GET['theme'] ?? '';
    
    if (empty($themeName)) {
        throw new Exception('主题名称不能为空');
    }
    
    // 获取主题选项定义
    $options = ThemeManager::getThemeOptions($themeName);
    
    // 获取当前选项值
    $currentValues = ThemeManager::getAllThemeOptions($themeName);
    
    // 返回JSON响应
    echo json_encode([
        'success' => true,
        'options' => $options,
        'currentValues' => $currentValues
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 返回错误响应
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

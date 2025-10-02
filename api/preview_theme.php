<?php
/**
 * 主题预览API接口
 */
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    
    // 获取请求数据
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['theme']) || !isset($input['options'])) {
        throw new Exception('无效的请求数据');
    }
    
    $themeName = $input['theme'];
    $options = $input['options'];
    
    // 验证主题是否存在
    $availableThemes = ThemeManager::getAvailableThemes();
    if (!isset($availableThemes[$themeName])) {
        throw new Exception('主题不存在');
    }
    
    // 临时保存预览选项到session
    $_SESSION['preview_theme'] = $themeName;
    $_SESSION['preview_options'] = $options;
    
    // 返回成功响应
    echo json_encode([
        'success' => true,
        'message' => '预览选项已更新'
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

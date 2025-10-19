<?php
/**
 * 主题预览API接口（仅限已登录管理员，含CSRF校验）
 */
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    // 必须是管理员登录
    require_login();

    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    
    // 获取请求数据
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    
    if (!$input || !isset($input['theme']) || !isset($input['options'])) {
        throw new Exception('无效的请求数据');
    }

    // CSRF 校验：支持请求头 X-CSRF-Token 或 JSON 内的 csrf_token 字段
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? null);
    if (!verify_csrf_token($csrfToken ?? '')) {
        throw new Exception('无效的请求，请刷新页面重试');
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

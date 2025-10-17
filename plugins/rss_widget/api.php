<?php
// plugins/rss_widget/api.php - 插件独立的API处理文件

// 引入系统核心启动文件
require_once __DIR__.'/../../includes/bootstrap.php';

// 设置响应头为JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // 安全检查：确保是已登录的管理员
    require_login();

    // 安全检查：确保是POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('无效的请求方法。');
    }
    
    // 安全检查：验证CSRF令牌
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new Exception('安全验证失败，请刷新页面重试。');
    }

    $db = db();
    
    // 准备要保存的设置项
    $settings_to_save = [
        'rss_widget_enabled' => isset($_POST['rss_widget_enabled']) && $_POST['rss_widget_enabled'] === '1' ? '1' : '0',
        'rss_widget_title'   => trim($_POST['rss_widget_title'] ?? '联盟站点最新文章'),
        'rss_widget_count'   => max(1, intval($_POST['rss_widget_count'] ?? 10)),
    ];
    
    // 使用事务确保数据一致性
    $db->beginTransaction();
    $stmt = $db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
    foreach ($settings_to_save as $key => $value) {
        $stmt->execute([$key, $value]);
    }
    $db->commit();

    // 返回成功的JSON响应
    echo json_encode(['success' => true, 'message' => '设置已成功保存！']);

} catch (Exception $e) {
    // 如果发生任何错误，返回失败的JSON响应
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400); // 设置HTTP状态码为400 Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

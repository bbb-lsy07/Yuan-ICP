<?php
// api/health.php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'version' => null,
    'installed' => false,
    'db_connected' => false,
    'active_theme' => null,
];

try {
    $response['version'] = get_system_version();
    $response['installed'] = is_installed();

    // 尝试数据库连接
    $db = db();
    $response['db_connected'] = $db instanceof PDO;

    // 获取当前主题
    $response['active_theme'] = ThemeManager::getActiveTheme();

    echo json_encode(['success' => true, 'health' => $response], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    $response['status'] = 'error';
    echo json_encode([
        'success' => false,
        'health' => $response,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

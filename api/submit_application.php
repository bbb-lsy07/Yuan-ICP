<?php
// api/submit_application.php
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 移除了 'before_application_submit' 钩子，因为检查点错误

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new Exception('无效的请求，请刷新页面重试');
    }

    $site_name = trim($_POST['site_name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');

    if (empty($site_name) || empty($domain) || empty($contact_name) || empty($contact_email)) {
        throw new Exception('网站名称、域名、联系人和邮箱不能为空');
    }
    if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('请输入有效的联系邮箱');
    }

    $_SESSION['application_data'] = [
        'site_name' => htmlspecialchars($site_name),
        'domain' => htmlspecialchars($domain),
        'description' => htmlspecialchars($description),
        'contact_name' => htmlspecialchars($contact_name),
        'contact_email' => htmlspecialchars($contact_email),
    ];

    echo json_encode([
        'success' => true,
        'message' => '信息已保存，正在跳转到选号页面...',
        'redirect' => 'select_number.php'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
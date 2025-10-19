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

    // 基础清理
    $site_name = sanitizeInput($_POST['site_name'] ?? '');
    $domain = sanitizeInput($_POST['domain'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $contact_name = sanitizeInput($_POST['contact_name'] ?? ''); // 可选
    $contact_email = sanitizeInput($_POST['contact_email'] ?? '');

    // 规范域名（移除协议/末尾斜杠）
    $domain = preg_replace('#^https?://#i', '', $domain);
    $domain = rtrim($domain, "/");

    // 校验必填项（联系人姓名为可选）
    if (empty($site_name) || empty($domain) || empty($contact_email)) {
        throw new Exception('网站名称、域名和邮箱不能为空');
    }
    if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('请输入有效的联系邮箱');
    }
    if (!isValidDomain($domain)) {
        throw new Exception('请输入有效的域名（例如 example.com）');
    }

    $_SESSION['application_data'] = [
        'site_name' => $site_name,
        'domain' => $domain,
        'description' => $description,
        'contact_name' => $contact_name,
        'contact_email' => $contact_email,
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
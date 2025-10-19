<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("无效的请求方法");
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) throw new Exception('无效的请求，请刷新页面重试');

    $id = intval($_POST['id'] ?? 0);
    
    // 验证一次性令牌
    if (!isset($_SESSION['verification_token'][$id])) {
         throw new Exception("验证超时，请重新开始。");
    }

    // 清理和校验输入
    $site_name = sanitizeInput($_POST['site_name'] ?? '');
    $domain = sanitizeInput($_POST['domain'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $contact_name = sanitizeInput($_POST['contact_name'] ?? '');

    // 规范域名（移除协议/末尾斜杠）
    $domain = preg_replace('#^https?://#i', '', $domain);
    $domain = rtrim($domain, "/");

    if (empty($site_name) || empty($domain) || empty($contact_name)) {
        throw new Exception("网站名称、域名和您的称呼不能为空。");
    }
    if (!isValidDomain($domain)) {
        throw new Exception('请输入有效的域名（例如 example.com）。');
    }

    $db = db();
    $stmt = $db->prepare(
        "UPDATE icp_applications 
         SET website_name = ?, domain = ?, description = ?, owner_name = ?, status = 'pending', is_resubmitted = 1, reviewed_at = NULL, reviewed_by = NULL, reject_reason = NULL
         WHERE id = ?"
    );
    $stmt->execute([$site_name, $domain, $description, $contact_name, $id]);

    // 清理会话
    unset($_SESSION['verification_step'][$id]);
    unset($_SESSION['verification_token'][$id]);
    
    echo json_encode(['success' => true, 'message' => '您的申请已更新并重新提交审核！', 'redirect' => '../result.php?application_id=' . $id]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

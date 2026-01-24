<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("无效的请求方法");

    $id = intval($_POST['id'] ?? 0);
    
    // 验证一次性令牌
    if (!isset($_SESSION['verification_token'][$id])) {
         throw new Exception("验证超时，请重新开始。");
    }

    $site_name = trim($_POST['site_name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');

    if (empty($site_name) || empty($domain) || empty($contact_name)) {
        throw new Exception("网站名称、域名和您的称呼不能为空。");
    }

    $db = db();
    $stmt = $db->prepare(
        "UPDATE icp_applications 
         SET website_name = ?, domain = ?, description = ?, owner_name = ?, status = 'pending', is_resubmitted = 1, reviewed_at = NULL, reviewed_by = NULL, reject_reason = NULL
         WHERE id = ?"
    );
    $stmt->execute([$site_name, $domain, $description, $contact_name, $id]);

    // 修改点：提交成功后清理验证 Session，但准备跳转到结果页，而不是留在详情页
    unset($_SESSION['verification_step'][$id]);
    unset($_SESSION['verification_token'][$id]);
    
    // 返回成功，并明确跳转到 result.php
    echo json_encode(['success' => true, 'message' => '您的申请已更新并重新提交审核！', 'redirect' => 'result.php?application_id=' . $id]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

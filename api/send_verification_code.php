<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("无效的请求方法");

    $id = intval($_POST['id'] ?? 0);
    if (!$id) throw new Exception("无效的申请ID");
    
    $db = db();
    $stmt = $db->prepare("SELECT owner_email FROM icp_applications WHERE id = ?");
    $stmt->execute([$id]);
    $email = $stmt->fetchColumn();

    if (!$email) throw new Exception("找不到申请记录");
    
    $code = random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10分钟有效期

    $stmt = $db->prepare("INSERT INTO email_verifications (application_id, email, code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $email, $code, $expires]);

    $subject = "您的备案申请管理验证码";
    $body = "<p>您正在管理您的备案申请。您的验证码是：<b>{$code}</b></p><p>该验证码10分钟内有效，请勿泄露给他人。</p>";
    
    if (send_email($email, '', $subject, $body)) {
        $_SESSION['verification_step'][$id] = 2; // 更新会话状态
        echo json_encode(['success' => true, 'message' => '验证码已发送，请注意查收。']);
    } else {
        throw new Exception("验证码邮件发送失败，请检查系统邮件设置或联系管理员。");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

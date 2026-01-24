<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("无效的请求方法");

    $id = intval($_POST['id'] ?? 0);
    $input_email = trim($_POST['email'] ?? ''); // 新增：要求前端传完整邮箱

    if (!$id) throw new Exception("无效的申请ID");
    
    // 60s Rate Limit
    if (isset($_SESSION['verification_lock'][$id]) && $_SESSION['verification_lock'][$id] > time()) {
        throw new Exception("请 " . ($_SESSION['verification_lock'][$id] - time()) . " 秒后再尝试发送验证码。");
    }
    
    $db = db();
    $stmt = $db->prepare("SELECT owner_email, owner_name FROM icp_applications WHERE id = ?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();

    if (!$app) throw new Exception("找不到申请记录");
    
    // 安全验证：输入的邮箱必须与数据库一致
    if (strtolower($input_email) !== strtolower($app['owner_email'])) {
        throw new Exception("填写的邮箱地址与备案记录不匹配，请重新确认。");
    }

    $email = $app['owner_email'];
    
    $code = random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10分钟有效期

    $stmt = $db->prepare("INSERT INTO email_verifications (application_id, email, code, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $email, $code, $expires]);

    $subject = "您的备案申请管理验证码";
    
    $mail_data = [
        'user_name' => '用户',
        'badge' => '验证码',
        'subject' => $subject,
        'body' => "您正在管理您的备案申请。请使用下方验证码完成身份验证：<br><br><div style='text-align: center; font-size: 24px; font-weight: bold; color: #3b82f6; background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>{$code}</div><p style='color: #ef4444; font-size: 14px;'>该验证码10分钟内有效，请勿泄露给他人。</p>"
    ];
    
    $html_body = format_email_modern($mail_data);
    
    if (send_email($email, '', $subject, $html_body)) {
        $_SESSION['verification_lock'][$id] = time() + 60; // Set 60s lock
        $_SESSION['verification_step'][$id] = 2; // 更新会话状态
        echo json_encode(['success' => true, 'message' => '验证码已发送，请注意查收。']);
    } else {
        throw new Exception("验证码邮件发送失败，请检查系统邮件设置或联系管理员。");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

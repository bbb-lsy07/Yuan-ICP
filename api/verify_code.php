<?php
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("无效的请求方法");

    $id = intval($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');

    if (empty($id) || empty($code)) {
        throw new Exception("请输入验证码。");
    }

    $db = db();
    $stmt = $db->prepare("SELECT * FROM email_verifications WHERE application_id = ? AND code = ? AND expires_at > ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$id, $code, date('Y-m-d H:i:s')]);
    $verification = $stmt->fetch();

    if ($verification) {
        $_SESSION['verification_step'][$id] = 3; // 验证成功
        $_SESSION['verification_token'][$id] = bin2hex(random_bytes(16));
        echo json_encode(['success' => true, 'message' => '验证成功！']);
    } else {
        throw new Exception("验证码错误或已过期。");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

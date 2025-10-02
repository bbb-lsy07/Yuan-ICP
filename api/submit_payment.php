<?php
// api/submit_payment.php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    if (!isset($_SESSION['application_id_pending_payment'])) {
        throw new Exception('无效的申请或会话已过期');
    }

    $application_id = $_SESSION['application_id_pending_payment'];
    $platform = trim($_POST['payment_platform'] ?? '');
    $transaction_id = trim($_POST['transaction_id'] ?? '');

    if (empty($platform) || empty($transaction_id)) {
        throw new Exception('请选择付款平台并填写订单号');
    }
    if (!in_array($platform, ['wechat', 'alipay'])) {
        throw new Exception('无效的付款平台');
    }

    $db = db();
    $stmt = $db->prepare(
        "UPDATE icp_applications 
         SET status = 'pending', payment_platform = ?, transaction_id = ? 
         WHERE id = ? AND status = 'pending_payment'"
    );
    $stmt->execute([$platform, $transaction_id, $application_id]);

    if ($stmt->rowCount() > 0) {
        unset($_SESSION['application_data']);
        unset($_SESSION['application_id_pending_payment']);
        
        echo json_encode([
            'success' => true,
            'message' => '付款信息提交成功！',
            'redirect' => 'result.php?application_id=' . $application_id
        ]);
    } else {
        throw new Exception('更新付款信息失败，请联系管理员');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

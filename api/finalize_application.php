<?php
// api/finalize_application.php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // V V V V V 在这里添加新的钩子 V V V V V
    // 在最终确定并写入数据库前运行检查
    PluginHooks::run('before_finalize_application', $_POST);
    // ^ ^ ^ ^ ^ 添加钩子结束 ^ ^ ^ ^ ^

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    if (!isset($_SESSION['application_data'])) {
        throw new Exception('会话已过期，请返回第一步重新申请');
    }

    $selected_number = trim($_POST['number'] ?? '');
    if (empty($selected_number)) {
        throw new Exception('请选择一个备案号');
    }

    $db = db();
    $app_data = $_SESSION['application_data'];
    $is_premium = check_if_number_is_premium($selected_number);
    $config = get_config();

    if ($is_premium && empty($config['wechat_payment_enabled']) && empty($config['alipay_payment_enabled'])) {
        throw new Exception('靓号需要赞助，但当前未配置任何有效的支付方式，请联系管理员。');
    }

    $status = $is_premium ? 'pending_payment' : 'pending';

    $db->beginTransaction();

    // **关键修复**: 在事务内检查号码是否已被占用
    $stmt_check = $db->prepare("SELECT id FROM icp_applications WHERE number = ?");
    $stmt_check->execute([$selected_number]);
    if ($stmt_check->fetch()) {
        throw new Exception('提交失败，此号码已被他人抢先选择，请刷新页面重选一个。');
    }
    
    // 插入申请记录
    $stmt = $db->prepare("
        INSERT INTO icp_applications (number, website_name, domain, description, owner_name, owner_email, status, ip_address, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $selected_number, 
        $app_data['site_name'], 
        $app_data['domain'], 
        $app_data['description'], 
        $app_data['contact_name'], 
        $app_data['contact_email'],
        $status,
        get_client_ip() // 获取并添加客户端IP
    ]);
    $application_id = $db->lastInsertId();

    if (!(bool)get_config('number_auto_generate', false)) {
        $stmt_update = $db->prepare("UPDATE selectable_numbers SET status = 'used', used_at = CURRENT_TIMESTAMP WHERE number = ? AND status = 'available'");
        $stmt_update->execute([$selected_number]);
    }

    $db->commit();
    
    if (!$is_premium) {
        unset($_SESSION['application_data']);
    } else {
        $_SESSION['application_id_pending_payment'] = $application_id;
    }

    // 构造响应
    $response = [
        'success' => true,
        'application_id' => $application_id,
        'requires_payment' => $is_premium,
        'message' => '申请已成功提交！'
    ];

    // 仅在不需要付款时才提供重定向URL
    if (!$is_premium) {
        $response['redirect'] = 'result.php?application_id=' . $application_id;
    }

    echo json_encode($response);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
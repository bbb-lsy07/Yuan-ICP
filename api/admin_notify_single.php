<?php
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json');
check_admin_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

try {
    $id = intval($_POST['app_id'] ?? 0);
    $type = $_POST['type'] ?? '';
    
    if (!$id || !$type) {
        throw new Exception('缺少必要参数');
    }
    
    $db = db();
    $stmt = $db->prepare("SELECT * FROM icp_applications WHERE id = ?");
    $stmt->execute([$id]);
    $app = $stmt->fetch();
    
    if (!$app) {
        throw new Exception('记录不存在');
    }
    
    $site_url = rtrim(get_config('site_url', ''), '/');
    $query_url = "{$site_url}/query.php?icp_number=" . urlencode($app['number']);
    $manage_url = "{$site_url}/details.php?id={$app['id']}";
    $code_snippet = "<a href='{$query_url}' target='_blank' rel='noopener'>{$app['number']}</a>";
    
    $mail_data = [
        'user_name' => $app['owner_name'],
        'badge' => '备案通知'
    ];
    
    // 根据预设类型填充内容
    switch($type) {
        case 'audit_passed':
            $mail_data['badge'] = '审核通过';
            $mail_data['subject'] = "【正式授权】您的虚拟备案号 {$app['number']} 已签发";
            $mail_data['body'] = "您的网站 <b>{$app['website_name']}</b> 申请的虚拟备案已通过专家人工审核。由于您的号码表现卓越，现已正式载入联盟名录。";
            $mail_data['code'] = $code_snippet;
            break;
            
        case 'fix_footer':
            $mail_data['badge'] = '待办提醒';
            $mail_data['subject'] = "行动请求：请在您的网站页脚悬挂备案标识";
            $mail_data['body'] = "我们注意到您的网站 <b>{$app['domain']}</b> 尚未悬挂备案标识。为了维护联盟生态的统一性，请在网页底部插入下方给出的超链接代码：";
            $mail_data['code'] = $code_snippet;
            break;
            
        case 'data_update':
            $mail_data['badge'] = '修正请求';
            $mail_data['subject'] = "备案信息修正提示：{$app['number']}";
            $mail_data['body'] = "您提交的备案信息需要进行微调。请点击下方按钮进入\"自助管理中心\"，使用验证码验证身份后，即可在线修改并重新提交。";
            $mail_data['btn_text'] = '立即进入管理中心';
            $mail_data['btn_url'] = $manage_url;
            break;
            
        case 'payment_reminder':
            $mail_data['badge'] = '付款提醒';
            $mail_data['subject'] = "靓号付款提醒：{$app['number']}";
            $mail_data['body'] = "您选择的靓号 <b>{$app['number']}</b> 需要完成赞助付款才能正式生效。请尽快完成付款流程，避免号码被其他用户选择。";
            $mail_data['btn_text'] = '立即完成付款';
            $mail_data['btn_url'] = $manage_url;
            break;
            
        case 'rejected':
            $mail_data['badge'] = '审核结果';
            $mail_data['subject'] = "备案申请审核结果：{$app['number']}";
            $reject_reason = $app['reject_reason'] ?: '未提供具体原因';
            $mail_data['body'] = "很抱歉，您的备案申请未能通过审核。<br><br><strong>驳回原因：</strong>{$reject_reason}<br><br>您可以根据反馈意见修改后重新提交申请。";
            $mail_data['btn_text'] = '重新提交申请';
            $mail_data['btn_url'] = $manage_url;
            break;
            
        default:
            throw new Exception('未知的通知类型');
    }
    
    $html_body = format_email_modern($mail_data);
    
    if (send_email($app['owner_email'], $app['owner_name'], $mail_data['subject'], $html_body)) {
        echo json_encode(['success' => true, 'message' => '邮件发送成功']);
    } else {
        throw new Exception('邮件发送失败');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
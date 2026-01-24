<?php
/**
 * 找回备案号 API
 * 通过邮箱发送该邮箱下所有备案号的列表
 */
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("无效的请求方法");
    }

    // --- 新增：简单的 IP 频率限制 ---
    $client_ip = get_client_ip();
    $limit_key = 'recover_limit_' . md5($client_ip);
    
    // 检查 Session 中的时间戳 (简单防刷)
    if (isset($_SESSION[$limit_key]) && (time() - $_SESSION[$limit_key] < 60)) {
        throw new Exception("请求过于频繁，请 1 分钟后再试。");
    }
    $_SESSION[$limit_key] = time();
    // --- 限制结束 ---

    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        throw new Exception("请输入邮箱地址");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("邮箱格式不正确");
    }
    
    $db = db();
    
    // 查询该邮箱下的所有备案申请
    $stmt = $db->prepare("SELECT number, website_name, domain, status, created_at 
                          FROM icp_applications 
                          WHERE owner_email = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$email]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($applications)) {
        throw new Exception("该邮箱下未找到任何备案记录");
    }
    
    // 构建邮件内容
    $site_name = get_config('site_name', 'Yuan-ICP');
    $subject = "【{$site_name}】您的备案号查询结果";
    
    // 构建表格内容
    $table_content = "";
    foreach ($applications as $app) {
        $status_text = [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已拒绝'
        ];
        $status_color = [
            'pending' => '#ff9800',
            'approved' => '#4caf50',
            'rejected' => '#f44336'
        ];
        $status = $app['status'];
        $table_content .= "<tr style='border-bottom: 1px solid #f1f5f9;'>";
        $table_content .= "<td style='padding: 12px 8px; font-weight: 600; color: #3b82f6;'>" . htmlspecialchars($app['number']) . "</td>";
        $table_content .= "<td style='padding: 12px 8px;'>" . htmlspecialchars($app['website_name']) . "</td>";
        $table_content .= "<td style='padding: 12px 8px; color: #6b7280;'>" . htmlspecialchars($app['domain']) . "</td>";
        $table_content .= "<td style='padding: 12px 8px;'><span style='color: " . ($status_color[$status] ?? '#333') . "; font-weight: 600;'>" . ($status_text[$status] ?? $status) . "</span></td>";
        $table_content .= "</tr>";
    }
    
    $mail_data = [
        'user_name' => '用户',
        'badge' => '备案查询',
        'subject' => $subject,
        'body' => "根据您提供的邮箱地址，我们找到了 <strong>" . count($applications) . "</strong> 条备案记录：<br><br>
        <table style='width: 100%; border-collapse: collapse; background: #f8fafc; border-radius: 8px; overflow: hidden;'>
            <thead>
                <tr style='background: #e2e8f0;'>
                    <th style='padding: 12px 8px; text-align: left; font-weight: 600; color: #374151;'>备案号</th>
                    <th style='padding: 12px 8px; text-align: left; font-weight: 600; color: #374151;'>网站名称</th>
                    <th style='padding: 12px 8px; text-align: left; font-weight: 600; color: #374151;'>域名</th>
                    <th style='padding: 12px 8px; text-align: left; font-weight: 600; color: #374151;'>状态</th>
                </tr>
            </thead>
            <tbody>
                {$table_content}
            </tbody>
        </table>
        <br><p style='color: #6b7280; font-size: 14px;'>💡 提示：您可以使用备案号或域名在查询页面进行详细查询。</p>"
    ];
    
    $html_body = format_email_modern($mail_data);
    
    // 发送邮件
    if (send_email($email, '', $subject, $html_body)) {
        echo json_encode([
            'success' => true, 
            'message' => '备案号列表已发送到您的邮箱，请查收。',
            'count' => count($applications)
        ]);
    } else {
        throw new Exception("邮件发送失败，请检查系统邮件设置或联系管理员。");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

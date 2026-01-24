<?php
/**
 * 发送测试邮件的API接口
 */
require_once __DIR__.'/../includes/bootstrap.php';

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    // 检查登录状态
    require_login();
    
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('只允许POST请求');
    }
    
    // 检查CSRF令牌
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new Exception('无效的请求，请刷新页面重试');
    }
    
    // 获取当前管理员信息
    $currentUser = current_user();
    if (!$currentUser) {
        throw new Exception('无法获取当前用户信息');
    }
    
    // 获取管理员邮箱（优先使用数据库中的邮箱，否则使用用户名）
    $adminEmail = $currentUser['email'] ?? $currentUser['username'] . '@example.com';
    
    // 验证邮箱格式
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('管理员邮箱格式无效，请先在用户设置中配置正确的邮箱地址');
    }
    
    // 获取系统配置
    $config = get_config();
    $siteName = $config['site_name'] ?? 'Yuan-ICP';
    
    // 准备测试邮件内容
    $subject = "【{$siteName}】邮件配置测试";
    
    $mail_data = [
        'user_name' => $currentUser['username'],
        'badge' => '配置测试',
        'subject' => $subject,
        'body' => "恭喜您！您的SMTP邮件配置已成功设置并正常工作。<br><br>
        <div style='background-color: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin: 20px 0;'>
            <h3 style='color: #1e40af; margin-top: 0; margin-bottom: 15px;'>📧 配置信息</h3>
            <ul style='margin: 0; color: #374151;'>
                <li><strong>SMTP服务器：</strong>{$config['smtp_host']}</li>
                <li><strong>SMTP端口：</strong>{$config['smtp_port']}</li>
                <li><strong>加密方式：</strong>{$config['smtp_secure']}</li>
                <li><strong>发件人：</strong>{$config['from_name']} &lt;{$config['from_email']}&gt;</li>
            </ul>
        </div>
        
        <p>现在您可以正常使用以下功能：</p>
        <ul style='color: #374151;'>
            <li>✅ 备案申请审核通知</li>
            <li>✅ 系统重要消息推送</li>
            <li>✅ 用户操作确认邮件</li>
            <li>✅ 场景化快捷通知</li>
        </ul>
        
        <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
            发送时间：" . date('Y-m-d H:i:s') . "
        </p>"
    ];
    
    $html_body = format_email_modern($mail_data);
    
    // 发送测试邮件
    $result = send_email($adminEmail, $currentUser['username'], $subject, $html_body);
    
    if ($result) {
        // 记录成功日志
        error_log("Test email sent successfully to admin: {$adminEmail}");
        
        echo json_encode([
            'success' => true,
            'message' => '测试邮件发送成功！请检查您的邮箱收件箱。',
            'email' => $adminEmail
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('邮件发送失败，请检查SMTP配置是否正确');
    }
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("Test email failed: " . $e->getMessage());
    
    // 返回错误响应
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() // 这里会将上面抛出的详细错误返回
    ], JSON_UNESCAPED_UNICODE);
}
?>

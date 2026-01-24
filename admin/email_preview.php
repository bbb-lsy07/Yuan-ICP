<?php
require_once __DIR__.'/../includes/bootstrap.php';
require_login();

// 演示不同类型的邮件模板
$preview_type = $_GET['type'] ?? 'audit_passed';

$mail_data = [];
switch($preview_type) {
    case 'audit_passed':
        $mail_data = [
            'user_name' => '张三',
            'badge' => '审核通过',
            'subject' => '【正式授权】您的虚拟备案号 Yuan-12345678 已签发',
            'body' => '您的网站 <b>我的个人博客</b> 申请的虚拟备案已通过专家人工审核。由于您的号码表现卓越，现已正式载入联盟名录。',
            'code' => '<a href="https://example.com/query.php?icp_number=Yuan-12345678" target="_blank" rel="noopener">Yuan-12345678</a>'
        ];
        break;
        
    case 'fix_footer':
        $mail_data = [
            'user_name' => '李四',
            'badge' => '待办提醒',
            'subject' => '行动请求：请在您的网站页脚悬挂备案标识',
            'body' => '我们注意到您的网站 <b>example.com</b> 尚未悬挂备案标识。为了维护联盟生态的统一性，请在网页底部插入下方给出的超链接代码：',
            'code' => '<a href="https://example.com/query.php?icp_number=Yuan-87654321" target="_blank" rel="noopener">Yuan-87654321</a>'
        ];
        break;
        
    case 'data_update':
        $mail_data = [
            'user_name' => '王五',
            'badge' => '修正请求',
            'subject' => '备案信息修正提示：Yuan-11223344',
            'body' => '您提交的备案信息需要进行微调。请点击下方按钮进入"自助管理中心"，使用验证码验证身份后，即可在线修改并重新提交。',
            'btn_text' => '立即进入管理中心',
            'btn_url' => 'https://example.com/details.php?id=123'
        ];
        break;
        
    case 'verification':
        $mail_data = [
            'user_name' => '用户',
            'badge' => '验证码',
            'subject' => '您的备案申请管理验证码',
            'body' => '您正在管理您的备案申请。请使用下方验证码完成身份验证：<br><br><div style="text-align: center; font-size: 24px; font-weight: bold; color: #3b82f6; background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;">123456</div><p style="color: #ef4444; font-size: 14px;">该验证码10分钟内有效，请勿泄露给他人。</p>'
        ];
        break;
}

$html_preview = format_email_modern($mail_data);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>邮件模板预览 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-envelope me-2"></i>邮件模板预览</h2>
                    <a href="applications.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 返回应用列表
                    </a>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">选择预览类型</h5>
                    </div>
                    <div class="card-body">
                        <div class="btn-group" role="group">
                            <a href="?type=audit_passed" class="btn <?php echo $preview_type === 'audit_passed' ? 'btn-primary' : 'btn-outline-primary'; ?>">审核通过</a>
                            <a href="?type=fix_footer" class="btn <?php echo $preview_type === 'fix_footer' ? 'btn-primary' : 'btn-outline-primary'; ?>">补挂代码</a>
                            <a href="?type=data_update" class="btn <?php echo $preview_type === 'data_update' ? 'btn-primary' : 'btn-outline-primary'; ?>">自助修改</a>
                            <a href="?type=verification" class="btn <?php echo $preview_type === 'verification' ? 'btn-primary' : 'btn-outline-primary'; ?>">验证码</a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">邮件预览效果</h5>
                        <small class="text-muted">Modern Light 风格邮件模板</small>
                    </div>
                    <div class="card-body p-0">
                        <iframe srcdoc="<?php echo htmlspecialchars($html_preview); ?>" 
                                style="width: 100%; height: 600px; border: none; background: #f8fafc;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
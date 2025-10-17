<?php extract($data); ?>
<div class="page-header">
    <h1>申请备案</h1>
    <p>请填写您的网站和联系信息以开始申请流程。</p>
</div>
<div class="main-card" style="max-width: 700px; margin: 0 auto;">
    <form id="icp-apply-form" method="post" action="api/submit_application.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
            <label for="site_name">网站名称</label>
            <input type="text" id="site_name" name="site_name" class="form-input" required placeholder="请输入您的网站名称">
        </div>
        <div class="form-group">
            <label for="domain">网站域名 (不含http://)</label>
            <input type="text" id="domain" name="domain" class="form-input" required placeholder="例如：example.com">
        </div>
        <div class="form-group">
            <label for="description">网站描述</label>
            <textarea id="description" name="description" class="form-textarea" placeholder="请简要描述您的网站内容和用途"></textarea>
        </div>
        <div class="form-group">
            <label for="contact_name">您的称呼</label>
            <input type="text" id="contact_name" name="contact_name" class="form-input" required placeholder="请输入您的姓名或昵称">
        </div>
        <div class="form-group">
            <label for="contact_email">您的邮箱</label>
            <input type="email" id="contact_email" name="contact_email" class="form-input" required placeholder="请输入您的邮箱地址">
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-arrow-right"></i> 下一步：选择号码
        </button>
    </form>
</div>
<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text">申请备案 - 步骤 1/2</h1>
</div>

<div class="form-container card-effect">
    <div class="step-indicator">
        <div class="step active"><div class="step-number">1</div><div class="step-title">填写信息</div></div>
        <div class="step"><div class="step-number">2</div><div class="step-title">选择号码</div></div>
    </div>

    <?php if ($error): ?>
        <p class="error" style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form class="neon-form" method="post" action="api/submit_application.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        
        <div class="form-section">
            <h3 class="section-title"><i class="fas fa-globe"></i> 网站信息</h3>
            <input type="text" name="site_name" class="search-input" placeholder="请输入网站名称" required>
            <input type="text" name="domain" class="search-input" placeholder="请输入网站域名（无需https://）" required>
            <textarea name="description" class="search-input" placeholder="请输入网站描述 (选填)"></textarea>
        </div>
        
        <div class="form-section">
            <h3 class="section-title"><i class="fas fa-user"></i> 联系信息</h3>
            <input type="text" name="contact_name" class="search-input" placeholder="您的称呼" required>
            <input type="email" name="contact_email" class="search-input" placeholder="您的邮箱 (用于接收审核结果)" required>
        </div>
        
        <button type="submit" class="glow-button primary large">
            <i class="fas fa-arrow-right"></i> 下一步
        </button>
    </form>
</div>
<script src="js/apply-form.js"></script>
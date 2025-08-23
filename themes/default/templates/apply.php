<?php
// 解压从 apply.php 控制器传递过来的变量
extract($data);
?>
<div class="container my-5">
    <div class="apply-form">
        <!-- 步骤指示器 -->
        <div class="step-indicator">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-title">填写信息</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-title">选择号码</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-title">完成申请</div>
            </div>
        </div>
        
        <div class="form-header">
            <h2>填写备案信息</h2>
            <p class="text-muted">请如实填写您的网站和联系人信息。</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div class="mb-4">
                <h5 class="mb-3"><i class="fas fa-globe me-2"></i>网站信息</h5>
                <div class="mb-3">
                    <label for="site_name" class="form-label">网站名称</label>
                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="domain" class="form-label">网站域名</label>
                    <div class="input-group">
                        <span class="input-group-text">https://</span>
                        <input type="text" class="form-control" id="domain" name="domain" placeholder="example.com" value="<?php echo htmlspecialchars($_POST['domain'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">网站描述 (选填)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mb-4">
                <h5 class="mb-3"><i class="fas fa-user me-2"></i>联系人信息</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact_name" class="form-label">联系人姓名 (选填)</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_email" class="form-label">联系人邮箱</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">下一步：选择号码</button>
            </div>
        </form>
    </div>
</div>
<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text">管理我的备案申请</h1>
    <p class="note">在这里，您可以查看申请状态、修改信息并重新提交。</p>
</div>

<div class="content card-effect" style="max-width: 700px;">
    <div id="message-container"></div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 步骤一：身份验证 -->
    <div id="step-1" style="<?php echo $step == 1 ? '' : 'display:none;'; ?>">
        <form id="verify-email-form" class="neon-form" method="POST" action="api/send_verification_code.php">
            <h3 style="text-align: center;">身份验证</h3>
            <p style="text-align: center;">为保护您的信息安全，我们需要向您的备案邮箱 <strong><?php echo htmlspecialchars($masked_email); ?></strong> 发送一个验证码。</p>
            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
            <button type="submit" class="glow-button primary"><i class="fas fa-paper-plane"></i> 发送验证码</button>
        </form>
    </div>

    <!-- 步骤二：输入验证码 -->
    <div id="step-2" style="<?php echo $step == 2 ? '' : 'display:none;'; ?>">
        <form id="submit-code-form" class="neon-form" method="POST" action="api/verify_code.php">
             <h3 style="text-align: center;">输入验证码</h3>
            <p style="text-align: center;">验证码已发送至 <strong><?php echo htmlspecialchars($masked_email); ?></strong>，请查收并输入。</p>
            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
            <input type="text" name="code" class="search-input" placeholder="6位验证码" required>
            <button type="submit" class="glow-button primary"><i class="fas fa-check"></i> 验证</button>
        </form>
    </div>
    
    <!-- 步骤三：修改信息 -->
    <div id="step-3" style="<?php echo $step == 3 ? '' : 'display:none;'; ?>">
        <form id="update-form" class="neon-form" method="POST" action="api/update_application.php">
            <h3 style="text-align: center;">修改备案信息</h3>
            <?php if($application['status'] === 'rejected' && !empty($application['reject_reason'])): ?>
                <div class="card-effect" style="background: rgba(239, 68, 68, 0.1); border-color: var(--danger-color);">
                    <strong style="color: var(--danger-color);"><i class="fas fa-exclamation-circle"></i> 驳回原因:</strong><br>
                    <?php echo nl2br(htmlspecialchars($application['reject_reason'])); ?>
                </div>
            <?php endif; ?>

            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
            <div class="form-section">
                <label>网站名称</label>
                <input type="text" name="site_name" class="search-input" value="<?php echo htmlspecialchars($application['website_name']); ?>" required>
            </div>
            <div class="form-section">
                <label>网站域名</label>
                <input type="text" name="domain" class="search-input" value="<?php echo htmlspecialchars($application['domain']); ?>" required>
            </div>
            <div class="form-section">
                <label>网站描述</label>
                <textarea name="description" class="search-input" rows="3"><?php echo htmlspecialchars($application['description']); ?></textarea>
            </div>
             <div class="form-section">
                <label>您的称呼</label>
                <input type="text" name="contact_name" class="search-input" value="<?php echo htmlspecialchars($application['owner_name']); ?>" required>
            </div>
            <div class="form-section">
                <label>您的邮箱 (无法修改)</label>
                <input type="email" name="contact_email" class="search-input" value="<?php echo htmlspecialchars($application['owner_email']); ?>" readonly>
            </div>
            <button type="submit" class="glow-button primary"><i class="fas fa-save"></i> 保存并重新提交审核</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('message-container');
    const step1Div = document.getElementById('step-1');
    const step2Div = document.getElementById('step-2');
    const step3Div = document.getElementById('step-3');

    const forms = {
        'verify-email-form': { div: step1Div, nextDiv: step2Div },
        'submit-code-form': { div: step2Div, nextDiv: step3Div },
        'update-form': { div: step3Div, nextDiv: null }
    };

    for (const formId in forms) {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
    }

    async function handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';
        showMessage('', 'clear');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            });
            const result = await response.json();

            if (result.success) {
                if (result.redirect) {
                    // 这是最后一步提交成功，可以显示消息并稍作延迟再跳转
                    showMessage(result.message, 'success');
                    setTimeout(() => { window.location.href = result.redirect; }, 500); // 延迟缩短为0.5秒
                } else {
                    // 对于发送验证码、验证验证码的步骤，直接刷新页面，无需提示和等待
                    window.location.reload();
                }
            } else {
                throw new Error(result.error || '发生未知错误');
            }
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    }

    function showMessage(message, type) {
        if (type === 'clear') {
            messageContainer.innerHTML = '';
            return;
        }
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        messageContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    }
});
</script>

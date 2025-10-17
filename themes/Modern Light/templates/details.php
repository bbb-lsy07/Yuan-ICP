<?php extract($data); ?>
<div class="page-header">
    <h1>管理我的备案申请</h1>
    <p>在这里，您可以查看申请状态、修改信息并重新提交。</p>
</div>

<div class="main-card" style="max-width: 700px; margin: 0 auto;">
    <div id="message-container"></div>
    <?php if (!empty($error)): ?>
        <div class="form-message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 步骤一：身份验证 -->
    <div id="step-1" style="<?php echo $step == 1 ? '' : 'display:none;'; ?>">
        <form id="verify-email-form" method="POST" action="/api/send_verification_code.php">
            <h3 style="text-align: center; margin-bottom: 1rem;">身份验证</h3>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">为保护您的信息安全，我们需要向您的备案邮箱 <strong><?php echo htmlspecialchars($masked_email); ?></strong> 发送一个验证码。</p>
            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-paper-plane"></i> 发送验证码</button>
        </form>
    </div>

    <!-- 步骤二：输入验证码 -->
    <div id="step-2" style="<?php echo $step == 2 ? '' : 'display:none;'; ?>">
        <form id="submit-code-form" method="POST" action="/api/verify_code.php">
            <h3 style="text-align: center; margin-bottom: 1rem;">输入验证码</h3>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem;">验证码已发送至 <strong><?php echo htmlspecialchars($masked_email); ?></strong>，请查收并输入。</p>
            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
            <div class="form-group">
                <input type="text" name="code" class="form-input" placeholder="6位验证码" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-check"></i> 验证</button>
        </form>
    </div>
    
    <!-- 步骤三：修改信息 -->
    <div id="step-3" style="<?php echo $step == 3 ? '' : 'display:none;'; ?>">
        <form id="update-form" method="POST" action="/api/update_application.php">
            <h3 style="text-align: center; margin-bottom: 1.5rem;">修改备案信息</h3>
            <?php if($application['status'] === 'rejected' && !empty($application['reject_reason'])): ?>
                <div style="margin-bottom: 1.5rem; padding: 1rem; background-color: rgba(239, 68, 68, 0.05); border-radius: var(--radius-md); border-left: 4px solid #dc2626;">
                    <strong style="color: #dc2626;"><i class="fas fa-exclamation-circle"></i> 驳回原因:</strong><br>
                    <p style="margin-top: 0.5rem; color: var(--text-secondary);"><?php echo nl2br(htmlspecialchars($application['reject_reason'])); ?></p>
                </div>
            <?php endif; ?>

            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
            <div class="form-group">
                <label for="site_name_update">网站名称</label>
                <input type="text" id="site_name_update" name="site_name" class="form-input" value="<?php echo htmlspecialchars($application['website_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="domain_update">网站域名</label>
                <input type="text" id="domain_update" name="domain" class="form-input" value="<?php echo htmlspecialchars($application['domain'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="description_update">网站描述</label>
                <textarea id="description_update" name="description" class="form-textarea" rows="3"><?php echo htmlspecialchars($application['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="contact_name_update">您的称呼</label>
                <input type="text" id="contact_name_update" name="contact_name" class="form-input" value="<?php echo htmlspecialchars($application['owner_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>您的邮箱 (无法修改)</label>
                <input type="email" class="form-input" value="<?php echo htmlspecialchars($application['owner_email'] ?? ''); ?>" readonly disabled>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-save"></i> 保存并重新提交审核</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 由于Swup.js的存在，我们需要确保这段脚本在每次页面浏览时都能执行
    // 将主要逻辑放入一个函数中
    function initDetailsPage() {
        const messageContainer = document.getElementById('message-container');
        const forms = document.querySelectorAll('#step-1 form, #step-2 form, #step-3 form');

        // 移除之前可能存在的事件监听器，避免重复绑定
        forms.forEach(form => {
            form.removeEventListener('submit', handleFormSubmit);
            form.addEventListener('submit', handleFormSubmit);
        });

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
                
                if (!response.ok) {
                    const errorResult = await response.json().catch(() => ({ error: '服务器响应异常' }));
                    throw new Error(errorResult.error || `HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    if (result.redirect) {
                        showMessage(result.message, 'success');
                        setTimeout(() => { window.location.href = result.redirect; }, 1500);
                    } else {
                        // 成功发送或验证验证码后，只需刷新页面即可进入下一步
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
             const existingMessage = document.querySelector('.form-message');
             if(existingMessage) existingMessage.remove();

            if (type === 'clear') {
                if (messageContainer) messageContainer.innerHTML = '';
                return;
            }
            const alertDiv = document.createElement('div');
            alertDiv.className = 'form-message';
            alertDiv.textContent = message;
            alertDiv.style.textAlign = 'center';
            alertDiv.style.fontWeight = 'bold';
            alertDiv.style.margin = '0 0 1rem 0';
            alertDiv.style.padding = '1rem';
            alertDiv.style.borderRadius = 'var(--radius-md)';
            alertDiv.style.border = '1px solid transparent';
            alertDiv.style.color = type === 'success' ? '#155724' : '#721c24';
            alertDiv.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            alertDiv.style.borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
            
            if (messageContainer) {
                messageContainer.appendChild(alertDiv);
            }
        }
    }
    
    // 首次加载执行
    initDetailsPage();
    
    // Swup页面切换后再次执行
    if (window.swup) {
        window.swup.hooks.on('page:view', initDetailsPage);
    }
});
</script>
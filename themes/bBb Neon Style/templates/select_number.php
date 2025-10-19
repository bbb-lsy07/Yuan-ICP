<?php extract($data); ?>

<!-- 付款弹窗 (Modal) -->
<div id="payment-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
    <div class="card-effect" style="width: 90%; max-width: 500px; text-align: center;">
        <h2 class="holographic-text">靓号赞助</h2>
        <p><?php echo htmlspecialchars($config['sponsorship_instructions'] ?? ''); ?></p>
        <p>赞助金额: <strong style="color:var(--accent-color); font-size: 1.5rem;"><?php echo htmlspecialchars($config['sponsorship_amount'] ?? '10'); ?></strong> 元</p>
        <div style="display:flex; justify-content:center; gap:20px; margin: 20px 0;">
            <?php if (file_exists(__DIR__ . '/../../../uploads/wechat_qr.png')): ?>
                <div>
                    <img src="/uploads/wechat_qr.png" style="width:150px; height:150px; border-radius:8px;">
                    <p>微信支付</p>
                </div>
            <?php endif; ?>
            <?php if (file_exists(__DIR__ . '/../../../uploads/alipay_qr.png')): ?>
                <div>
                    <img src="/uploads/alipay_qr.png" style="width:150px; height:150px; border-radius:8px;">
                    <p>支付宝</p>
                </div>
            <?php endif; ?>
        </div>
        <form id="payment-form" class="neon-form" style="border:none; box-shadow:none; padding:10px;">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <p><strong>完成赞助后，请在此处填写信息以便我们核对：</strong></p>
            <select name="payment_platform" class="search-input" required>
                <option value="">-- 请选择付款平台 --</option>
                <option value="wechat">微信</option>
                <option value="alipay">支付宝</option>
            </select>
            <input type="text" name="transaction_id" class="search-input" placeholder="请填写订单号/交易单号" required>
            <button type="submit" class="glow-button primary">我已赞助，提交审核</button>
        </form>
    </div>
</div>

<div class="header">
    <h1 class="holographic-text">选择备案号 - 步骤 2/2</h1>
</div>
<div class="form-container">
    <div class="step-indicator">
        <div class="step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-title">填写信息</div></div>
        <div class="step active"><div class="step-number">2</div><div class="step-title">选择号码</div></div>
    </div>
    <div class="content card-effect">
        <div id="number-grid-container" class="number-grid"><p>正在加载号码...</p></div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; gap: 20px; flex-wrap: wrap;">
            <button type="button" class="glow-button secondary" id="refresh-numbers"><i class="fas fa-sync-alt"></i> 换一批</button>
            <div id="selected-display" style="font-size: 1.2rem; min-width: 120px;">请选择一个号码</div>
            <button type="button" id="confirm-btn" class="glow-button primary" disabled><i class="fas fa-check"></i> 确认选择</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('number-grid-container');
    const refreshBtn = document.getElementById('refresh-numbers');
    const selectedDisplay = document.getElementById('selected-display');
    const confirmBtn = document.getElementById('confirm-btn');
    const paymentModal = document.getElementById('payment-modal');
    const paymentForm = document.getElementById('payment-form');
    let selectedNumber = null;

    async function fetchNumbers() {
        grid.innerHTML = '<p>正在加载号码...</p>';
        confirmBtn.disabled = true;
        selectedDisplay.textContent = '请选择一个号码';
        selectedNumber = null;

        try {
            const response = await fetch('api/get_numbers.php');
            const data = await response.json();
            grid.innerHTML = '';
            if (data.success && data.numbers.length > 0) {
                data.numbers.forEach(num => {
                    const card = document.createElement('div');
                    card.className = 'number-card card-effect';
                    card.dataset.number = num.number;
                    if (num.is_premium) {
                        card.classList.add('premium');
                        card.innerHTML = `<div class="number">${num.number}</div><div class="premium-badge" title="靓号"><i class="fas fa-gem"></i></div>`;
                    } else {
                        card.innerHTML = `<div class="number">${num.number}</div>`;
                    }
                    card.addEventListener('click', () => selectNumber(card));
                    grid.appendChild(card);
                });
            } else {
                grid.innerHTML = '<p>暂无可用号码。</p>';
            }
        } catch (e) {
            grid.innerHTML = '<p style="color:red;">加载号码失败，请重试。</p>';
        }
    }

    function selectNumber(card) {
        document.querySelectorAll('.number-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedNumber = card.dataset.number;
        selectedDisplay.textContent = `已选: ${selectedNumber}`;
        confirmBtn.disabled = false;
    }

    confirmBtn.addEventListener('click', async function() {
        if (!selectedNumber) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';

        const formData = new FormData();
        formData.append('number', selectedNumber);

        try {
            formData.append('csrf_token', '<?php echo csrf_token(); ?>');
            const response = await fetch('api/finalize_application.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                if (result.requires_payment) {
                    paymentModal.style.display = 'flex';
                } else {
                    window.location.href = 'result.php?application_id=' + result.application_id;
                }
            } else {
                showCustomAlert('提交失败: ' + result.error, 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check"></i> 确认选择';
            }
        } catch (error) {
            showCustomAlert('网络错误，请重试', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check"></i> 确认选择';
        }
    });

    paymentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';

        try {
            const response = await fetch('api/submit_payment.php', { method: 'POST', body: new FormData(this) });
            const result = await response.json();
            if (result.success) {
                showCustomAlert(result.message, 'success');
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1500);
            } else {
                showCustomAlert('提交失败: ' + result.error, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '我已赞助，提交审核';
            }
        } catch (error) {
            showCustomAlert('网络错误，请重试', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '我已赞助，提交审核';
        }
    });

    refreshBtn.addEventListener('click', fetchNumbers);
    fetchNumbers();
});

// 自定义弹窗函数
function showCustomAlert(message, type = 'info') {
    // 创建弹窗元素
    const alertOverlay = document.createElement('div');
    alertOverlay.className = 'custom-alert-overlay';
    alertOverlay.style.cssText = `
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        backdrop-filter: blur(5px);
    `;
    
    const alertBox = document.createElement('div');
    alertBox.style.cssText = `
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
        text-align: center;
        max-width: 400px;
        width: 90%;
        animation: slideInUp 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
    `;
    
    const iconMap = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    const colorMap = {
        'success': '#00ff88',
        'error': '#ff4757',
        'warning': '#ffa502',
        'info': '#3742fa'
    };
    
    alertBox.innerHTML = `
        <div style="font-size: 3rem; margin-bottom: 1rem; color: ${colorMap[type] || colorMap.info};">
            <i class="fas ${iconMap[type] || iconMap.info}"></i>
        </div>
        <p style="font-size: 1.1rem; color: #ffffff; margin-bottom: 1.5rem;">${message}</p>
        <button class="btn btn-primary" onclick="this.closest('.custom-alert-overlay').remove()" style="background: linear-gradient(45deg, #3742fa, #2f3542); border: none; padding: 0.75rem 2rem; border-radius: 0.5rem; color: white; font-weight: 600; cursor: pointer;">好的</button>
    `;
    
    alertOverlay.appendChild(alertBox);
    document.body.appendChild(alertOverlay);
    
    // 添加动画样式
    if (!document.querySelector('#custom-alert-styles')) {
        const style = document.createElement('style');
        style.id = 'custom-alert-styles';
        style.textContent = `
            @keyframes slideInUp { 
                from { opacity: 0; transform: translateY(30px); } 
                to { opacity: 1; transform: translateY(0); } 
            }
        `;
        document.head.appendChild(style);
    }
}
</script>
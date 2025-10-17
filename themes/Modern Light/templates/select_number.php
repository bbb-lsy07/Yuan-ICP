<?php 
extract($data); 
$wechat_enabled = !empty($config['wechat_payment_enabled']) && file_exists(__DIR__ . '/../../../uploads/wechat_qr.png');
$alipay_enabled = !empty($config['alipay_payment_enabled']) && file_exists(__DIR__ . '/../../../uploads/alipay_qr.png');
$payment_available = $wechat_enabled || $alipay_enabled;
?>

<!-- 靓号支付弹窗 -->
<div id="payment-modal" class="payment-modal-overlay">
    <div class="main-card payment-modal-content">
        <button id="close-modal-btn" class="close-modal-btn">&times;</button>
        <h2><i class="fas fa-gem"></i> 靓号赞助</h2>
        
        <?php if($payment_available): ?>
            <p class="payment-instructions"><?php echo htmlspecialchars($config['sponsorship_instructions'] ?? '感谢您的选择！请完成赞助以激活您的靓号。'); ?></p>
            <p class="payment-amount">赞助金额: <strong><?php echo htmlspecialchars($config['sponsorship_amount'] ?? '10.00'); ?></strong> 元</p>
            <div class="qr-codes">
                <?php if ($wechat_enabled): ?>
                <div class="qr-code-item">
                    <img src="/uploads/wechat_qr.png?t=<?php echo time(); ?>" alt="微信支付">
                    <p><i class="fab fa-weixin"></i> 微信支付</p>
                </div>
                <?php endif; ?>
                <?php if ($alipay_enabled): ?>
                <div class="qr-code-item">
                    <img src="/uploads/alipay_qr.png?t=<?php echo time(); ?>" alt="支付宝">
                    <p><i class="fab fa-alipay"></i> 支付宝</p>
                </div>
                <?php endif; ?>
            </div>
            <form id="payment-form">
                <p class="payment-form-title">完成赞助后，请填写信息以便我们核对：</p>
                <div class="form-group">
                    <select name="payment_platform" class="form-select" required>
                        <option value="">-- 请选择付款平台 --</option>
                        <?php if ($wechat_enabled): ?><option value="wechat">微信</option><?php endif; ?>
                        <?php if ($alipay_enabled): ?><option value="alipay">支付宝</option><?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="text" name="transaction_id" class="form-input" placeholder="请填写订单号/交易单号" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">我已赞助，提交审核</button>
            </form>
        <?php else: ?>
            <p class="payment-instructions">该靓号需要赞助，但管理员当前未开启任何支付方式。请联系网站管理员处理。</p>
            <button id="contact-admin-btn" type="button" class="btn btn-secondary" style="width: 100%;">好的，我明白了</button>
        <?php endif; ?>
    </div>
</div>

<div class="page-header">
    <h1>选择备案号 - 步骤 2/2</h1>
    <p>请从下方选择一个您心仪的备案号。</p>
</div>

<div class="main-card" style="max-width: 900px; margin: 0 auto;">
    <div class="number-controls">
        <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="search" id="search-input" class="form-input" placeholder="搜索你喜欢的数字或字母...">
        </div>
        <button type="button" class="btn btn-secondary" id="refresh-numbers">
            <i class="fas fa-sync-alt"></i> 换一批
        </button>
    </div>
    <div id="number-grid-container" class="number-grid"></div>
    <div id="load-more-container" style="text-align: center; margin-top: 1.5rem; display: none;">
        <button class="btn btn-secondary" id="load-more-btn">
             <i class="fas fa-chevron-down"></i> 加载更多
        </button>
    </div>
    <div class="confirmation-area">
        <div id="selected-display">请选择一个号码</div>
        <button type="button" id="confirm-btn" class="btn btn-primary" disabled>
            <i class="fas fa-check"></i> 确认选择
        </button>
    </div>
</div>

<!-- 自定义弹窗 -->
<div id="custom-alert" class="custom-alert-overlay" style="display: none;">
    <div class="custom-alert-box">
        <p id="custom-alert-message"></p>
        <button id="custom-alert-close" class="btn btn-primary">好的</button>
    </div>
</div>

<script>
// 将支付方式可用性传递给JavaScript
document.body.dataset.paymentAvailable = '<?php echo $payment_available ? 'true' : 'false'; ?>';
</script>
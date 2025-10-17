<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text">备案申请结果</h1>
</div>

<div class="content card-effect" style="max-width: 700px;">
    <?php if ($application['status'] === 'pending_payment'): ?>
        <h2><i class="fas fa-hourglass-half" style="color: #ffc107;"></i> 靓号申请待付款</h2>
        <p>您的靓号申请已创建，备案号为：<strong><?php echo htmlspecialchars($application['number']); ?></strong></p>
        <p>状态：<span style="color:#ffc107;">待完成赞助</span></p>
        <p>请尽快完成赞助并提交订单信息，否则申请可能会被系统自动取消。</p>
        <div class="alert" style="background: rgba(var(--neon-color-rgb), 0.1); border-color: var(--neon-color); color: var(--text-color);">如果您已关闭付款页面，请联系管理员并提供您的备案号 <strong><?php echo htmlspecialchars($application['number']); ?></strong> 来完成后续步骤。</div>
    <?php else: ?>
        <h2><i class="fas fa-check-circle" style="color: #2ecc71;"></i> 申请已提交</h2>
        <p>您的备案号是：<strong><?php echo htmlspecialchars($application['number']); ?></strong></p>
        <p>状态：<span style="color:#ffc107;">待审核</span> (通过后将在公示页面显示)</p>
        <p>请将以下代码添加到您的网站页脚 (点击代码框即可复制)：</p>
        <div class="code-container" onclick="copyCodeToClipboard(this)">
            <pre id="html_code_display"><?php
                $site_url = !empty($config['site_url']) ? rtrim($config['site_url'], '/') : '';
                echo htmlspecialchars('<a href="' . $site_url . '/query.php?icp_number=' . urlencode($application['number']) . '" target="_blank">' . htmlspecialchars($application['number']) . '</a>');
            ?></pre>
            <span class="copy-feedback"></span>
        </div>
        <p class="note">审核将在 24 小时内完成，请耐心等待。</p>
    <?php endif; ?>

    <div class="button-group" style="margin-top: 30px;">
        <a href="index.php" class="glow-button secondary page-transition-link"><i class="fas fa-home"></i> 返回首页</a>
        <a href="query.php?icp_number=<?php echo urlencode($application['number']); ?>" class="glow-button primary page-transition-link"><i class="fas fa-search"></i> 查询我的备案</a>
    </div>
</div>
<script>
function copyCodeToClipboard(container) {
    const textToCopy = container.querySelector('pre').innerText;
    const feedback = container.querySelector('.copy-feedback');
    navigator.clipboard.writeText(textToCopy).then(() => {
        feedback.textContent = '已复制!';
        container.classList.add('copied');
        setTimeout(() => { feedback.textContent = ''; container.classList.remove('copied'); }, 2000);
    });
}
</script>
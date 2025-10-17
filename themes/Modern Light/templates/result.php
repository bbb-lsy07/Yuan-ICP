<?php 
extract($data); 

// 状态文本映射
$status_map = [
    'pending' => ['text' => '待审核', 'color' => '#d97706', 'bg' => 'rgba(251, 191, 36, 0.1)', 'icon' => 'fas fa-clock'],
    'pending_payment' => ['text' => '待付款', 'color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)', 'icon' => 'fas fa-credit-card'],
    'approved' => ['text' => '已通过', 'color' => '#16a34a', 'bg' => 'rgba(34, 197, 94, 0.1)', 'icon' => 'fas fa-check-circle'],
    'rejected' => ['text' => '已驳回', 'color' => '#dc2626', 'bg' => 'rgba(239, 68, 68, 0.1)', 'icon' => 'fas fa-times-circle'],
];
$current_status = $status_map[$application['status']] ?? ['text' => '未知', 'color' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.1)', 'icon' => 'fas fa-question-circle'];
?>

<div class="page-header">
    <h1>申请结果</h1>
    <p>您的备案申请处理进度如下。</p>
</div>

<?php if ($application): ?>
    <div class="main-card" style="max-width: 700px; margin: 0 auto;">
        <!-- 动态状态头部 -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background-color: <?php echo $current_status['color']; ?>; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
                <i class="<?php echo $current_status['icon']; ?>"></i>
            </div>
            <h2 style="color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo $current_status['text']; ?></h2>
            <p style="color: var(--text-secondary);">
                <?php
                switch ($application['status']) {
                    case 'pending':
                    case 'pending_payment':
                        echo '您的申请已成功提交，请耐心等待审核。';
                        break;
                    case 'approved':
                        echo '恭喜！您的备案申请已通过审核。';
                        break;
                    case 'rejected':
                        echo '很遗憾，您的备案申请已被驳回。';
                        break;
                }
                ?>
            </p>
        </div>
        
        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--card-border-color);">
        
        <!-- 申请详情 -->
        <dl class="result-dl">
            <dt>备案号</dt><dd style="font-family: monospace; font-size: 1.125rem; font-weight: 600; color: var(--accent-color);"><?php echo htmlspecialchars($application['number']); ?></dd>
            <dt>网站名称</dt><dd><?php echo htmlspecialchars($application['website_name']); ?></dd>
            <dt>网站地址</dt><dd><a href="https://<?php echo htmlspecialchars($application['domain']); ?>" target="_blank" style="color: var(--accent-color);"><?php echo htmlspecialchars($application['domain']); ?></a></dd>
            <dt>申请时间</dt><dd><?php echo date('Y-m-d H:i:s', strtotime($application['created_at'])); ?></dd>
            <dt>当前状态</dt><dd>
                <span style="background-color: <?php echo $current_status['bg']; ?>; color: <?php echo $current_status['color']; ?>; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500;">
                    <?php echo $current_status['text']; ?>
                </span>
            </dd>
        </dl>
        
        <!-- 动态指引和代码框 -->
        <div class="result-guide" style="border-left-color: <?php echo $current_status['color']; ?>; background-color: <?php echo $current_status['bg']; ?>;">
            <h3><i class="<?php echo $current_status['icon']; ?>"></i> 
                <?php
                switch ($application['status']) {
                    case 'pending':
                    case 'pending_payment':
                        echo '下一步';
                        break;
                    case 'approved':
                        echo '网站集成指引';
                        break;
                    case 'rejected':
                        echo '驳回原因';
                        break;
                }
                ?>
            </h3>
            <p>
                <?php
                switch ($application['status']) {
                    case 'pending':
                    case 'pending_payment':
                        echo '您的申请正在审核中，我们会在1-3个工作日内完成审核。审核通过后，请将下方代码添加到您的网站页脚。';
                        break;
                    case 'approved':
                        echo '请将以下代码添加到您的网站页脚 (点击下方代码框即可复制)：';
                        break;
                    case 'rejected':
                        echo htmlspecialchars($application['reject_reason'] ?: '未提供具体原因，请联系管理员。');
                        break;
                }
                ?>
            </p>
            <?php // 只要不是被驳回，都显示代码框 ?>
            <?php if ($application['status'] !== 'rejected'): ?>
            <div class="code-block-container" onclick="copyCodeToClipboard(this)">
                <pre><?php
                    $site_url = !empty($config['site_url']) ? rtrim($config['site_url'], '/') : '';
                    echo htmlspecialchars('<a href="' . $site_url . '/query.php?icp_number=' . urlencode($application['number']) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($application['number']) . '</a>');
                ?></pre>
                <span class="copy-feedback"><i class="fas fa-copy"></i> 点击复制</span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 动态操作按钮 -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
            <?php if ($application['status'] === 'rejected'): ?>
                <a href="details.php?id=<?php echo $application['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> 修改信息并重新提交
                </a>
            <?php else: ?>
                <!-- 修改开始 -->
                <form action="query.php" method="GET" style="margin:0;">
                    <input type="hidden" name="icp_number" value="<?php echo htmlspecialchars($application['number']); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 刷新/查询状态
                    </button>
                </form>
                <!-- 修改结束 -->
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> 返回首页
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="main-card" style="max-width: 700px; margin: 0 auto; text-align: center;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background-color: #ef4444; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 style="color: var(--text-primary); margin-bottom: 0.5rem;">申请未找到</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">抱歉，未找到您要查看的申请信息。</p>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> 返回首页
        </a>
    </div>
<?php endif; ?>

<script>
function copyCodeToClipboard(container) {
    const textToCopy = container.querySelector('pre').innerText;
    const feedback = container.querySelector('.copy-feedback');
    
    navigator.clipboard.writeText(textToCopy).then(() => {
        feedback.innerHTML = '<i class="fas fa-check"></i> 已复制!';
        container.classList.add('copied');
        setTimeout(() => { 
            feedback.innerHTML = '<i class="fas fa-copy"></i> 点击复制'; 
            container.classList.remove('copied'); 
        }, 2000);
    }).catch(err => {
        feedback.innerHTML = '<i class="fas fa-times"></i> 复制失败!';
        console.error('Copy failed', err);
    });
}
</script>
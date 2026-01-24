<?php
// 解压从控制器传递过来的变量
extract($data);

// --- 手动修复：生成缺失的 $html_code 变量 ---
if (!isset($html_code) && isset($application['number'])) {
    $site_url = rtrim($config['site_url'] ?? '', '/');
    $query_url = $site_url . '/query.php?icp_number=' . urlencode($application['number']);
    $html_code = htmlspecialchars('<a href="' . $query_url . '" target="_blank">' . $application['number'] . '</a>');
}
// ------------------------------------------
?>
<div class="container my-5">
    <div class="result-container">
        <div class="text-center mb-4">
            <h2>备案申请结果</h2>
            <p class="text-muted">您的备案申请已提交成功</p>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">申请信息</h5>
                    <span class="status-badge status-<?php echo $application['status']; ?>">
                        <?php 
                        switch($application['status']) {
                            case 'pending': echo '审核中'; break;
                            case 'approved': echo '已通过'; break;
                            case 'rejected': echo '已驳回'; break;
                            default: echo $application['status'];
                        }
                        ?>
                    </span>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>网站名称:</strong> <?php echo htmlspecialchars($application['website_name']); ?></p>
                        <p><strong>网站域名:</strong> <?php echo htmlspecialchars($application['domain']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>备案号:</strong> <?php echo htmlspecialchars($application['number'] ?? '待分配'); ?></p>
                        <p><strong>申请时间:</strong> <?php echo date('Y-m-d H:i', strtotime($application['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($application['status'] === 'rejected' && !empty($application['reject_reason'])): ?>
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-circle me-2"></i>驳回原因</h5>
            <p><?php echo nl2br(htmlspecialchars($application['reject_reason'])); ?></p>
            <a href="apply.php" class="btn btn-outline-danger">重新申请</a>
        </div>
        <?php endif; ?>

        <?php if ($application['number']): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">请将以下代码放置到您的网站底部</h5>
                <div class="code-block">
                    <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-target="#html-code"><i class="far fa-copy me-1"></i>复制</button>
                    <div id="html-code"><?php echo $html_code; ?></div>
                </div>
                <div class="mt-3"><p class="text-muted small">* 根据规定，您需要在网站底部展示备案号及链接</p></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title mb-3">备案查询</h5>
                <p>您可以通过以下链接查询备案状态：</p>
                <a href="query.php?icp_number=<?php echo urlencode($application['number']); ?>" class="btn btn-primary">查询我的备案状态</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
    new ClipboardJS('.copy-btn');
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i>已复制';
            setTimeout(() => { this.innerHTML = originalText; }, 2000);
        });
    });
</script>
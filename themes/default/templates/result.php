<?php
// 解压从控制器传递过来的变量
extract($data);
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
                            case 'pending_payment': echo '待付款'; break;
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

        <?php if ($application['status'] === 'pending_payment'): ?>
        <div class="alert alert-warning">
            <h5 class="mb-2"><i class="fas fa-gem me-2"></i>靓号赞助说明</h5>
            <p class="mb-2"><?php echo nl2br(htmlspecialchars($config['sponsor_message'] ?? '该号码属于靓号，需要赞助支持。请根据下方指引完成赞助后提交订单号以便审核。')); ?></p>
            <div class="row g-3 mt-2">
                <?php if (file_exists(__DIR__ . '/../../../uploads/wechat_qr.png')): ?>
                <div class="col-md-6 text-center">
                    <img src="/uploads/wechat_qr.png" alt="微信支付" style="max-width:160px; border-radius:8px;">
                    <div class="text-muted small mt-2">微信支付</div>
                </div>
                <?php endif; ?>
                <?php if (file_exists(__DIR__ . '/../../../uploads/alipay_qr.png')): ?>
                <div class="col-md-6 text-center">
                    <img src="/uploads/alipay_qr.png" alt="支付宝" style="max-width:160px; border-radius:8px;">
                    <div class="text-muted small mt-2">支付宝</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">提交付款信息</h5>
                <form id="payment-form" class="row g-3">
                    <div class="col-md-4">
                        <select name="payment_platform" class="form-select" required>
                            <option value="">选择付款平台</option>
                            <option value="wechat">微信</option>
                            <option value="alipay">支付宝</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input type="text" name="transaction_id" class="form-control" placeholder="请输入订单号/交易单号" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">我已赞助，提交审核</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

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

    // 提交付款信息（仅在待付款状态下存在该表单）
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const original = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>提交中...';
            try {
                const resp = await fetch('api/submit_payment.php', { method: 'POST', body: new FormData(this) });
                const result = await resp.json();
                if (result.success) {
                    alert(result.message || '提交成功');
                    window.location.href = result.redirect;
                } else {
                    alert(result.error || '提交失败，请重试');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = original;
                }
            } catch (err) {
                alert('网络错误，请稍后重试');
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
            }
        });
    }
</script>
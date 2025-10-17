<?php 
extract($data); 
$is_premium = $is_premium ?? false; // 确保变量总是存在
?>
<div class="page-header">
    <h1>查询备案信息</h1>
    <p>请输入备案号或域名进行查询。</p>
</div>
<div class="main-card" style="max-width: 700px; margin: 0 auto;">
    <form action="query.php" method="GET" class="query-form-modern">
        <div class="query-input-group">
            <div class="form-group">
                <input type="text" name="icp_number" class="form-input" value="<?php echo htmlspecialchars($icp_number ?? ''); ?>" placeholder="请输入备案号">
            </div>
            <div class="separator">或</div>
            <div class="form-group">
                <input type="text" name="domain" class="form-input" value="<?php echo htmlspecialchars($domain ?? ''); ?>" placeholder="请输入网站域名">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> 查询
        </button>
    </form>
</div>

<?php if (!empty($error)): ?>
    <div class="main-card" style="max-width: 700px; margin: 2rem auto; border-left: 4px solid #ef4444; background-color: rgba(239, 68, 68, 0.05);">
        <div style="display: flex; align-items: center; gap: 0.5rem; color: #dc2626;">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
<?php elseif ($result): ?>
    <?php // 如果审核已通过，显示最终结果 ?>
    <?php if ($result['status'] === 'approved'): ?>
    <div class="main-card" style="max-width: 700px; margin: 2rem auto;">
        <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">
            <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> 查询结果
        </h2>
        <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--card-border-color);">
        <dl class="result-dl">
            <dt>备案号</dt><dd><?php echo htmlspecialchars($result['number']); ?></dd>
            <dt>网站名称</dt><dd><?php echo htmlspecialchars($result['website_name']); ?></dd>
            <dt>网站地址</dt><dd><a href="https://<?php echo htmlspecialchars($result['domain']); ?>" target="_blank"><?php echo htmlspecialchars($result['domain']); ?></a></dd>
            <dt>状态</dt><dd><span style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500;">已通过</span></dd>
            <dt>审核时间</dt><dd><?php echo date('Y-m-d H:i', strtotime($result['reviewed_at'])); ?></dd>
        </dl>
    </div>
    <?php // 如果是审核中或被拒绝，显示状态和操作 ?>
    <?php else: ?>
    <div class="main-card" style="max-width: 700px; margin: 2rem auto; text-align: center;">
        <h2 style="color: var(--text-primary); margin-bottom: 1rem;">备案状态查询</h2>
        <p style="color: var(--text-secondary);">备案号 <strong><?php echo htmlspecialchars($result['number']); ?></strong> 的当前状态为：</p>
        
        <?php if ($result['status'] === 'pending' || $result['status'] === 'pending_payment'): ?>
            <h3 style="color: #d97706; margin-top: 1rem;"><i class="fas fa-clock"></i> 审核中</h3>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">您的申请正在等待管理员审核，请耐心等待。</p>
        <?php elseif ($result['status'] === 'rejected'): ?>
            <h3 style="color: #dc2626; margin-top: 1rem;"><i class="fas fa-times-circle"></i> 已驳回</h3>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">您的申请已被驳回。您可以查看详情并修改后重新提交。</p>
            <a href="details.php?id=<?php echo $result['id']; ?>" class="btn btn-primary" style="margin-top: 1.5rem;">
                <i class="fas fa-edit"></i> 查看详情并修改
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
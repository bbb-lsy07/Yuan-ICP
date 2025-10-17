<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text">查询备案信息</h1>
</div>
<div class="search-box">
    <!-- 修改：移除包裹的 input-group-wrapper div -->
    <form action="query.php" method="GET" class="neon-form">
        <!-- 移除了外层的 .input-group-wrapper div -->
        <input type="text" name="icp_number" class="search-input" placeholder="请输入备案号" value="<?php echo htmlspecialchars($icp_number ?? ''); ?>">
        <span class="note">或</span>
        <input type="text" name="domain" class="search-input" placeholder="请输入网站域名" value="<?php echo htmlspecialchars($domain ?? ''); ?>">
        
        <button type="submit" class="glow-button primary">
            <i class="fas fa-search"></i> 查询
        </button>
    </form>
</div>
<?php if (!empty($error)): ?>
    <p class="error card-effect" style="max-width: 700px; margin: 20px auto;"><?php echo htmlspecialchars($error); ?></p>
<?php elseif ($result): ?>
    <?php if ($result['status'] === 'approved'): ?>
        <?php if ($is_premium): ?>
            <!-- 全新靓号卡片结构 -->
            <div class="content premium-result-card">
                <div class="content-inner">
                    <h2 class="holographic-text" style="font-size: 1.8rem; margin-bottom: 1.5rem;"><i class="fas fa-gem"></i> 靓号详情</h2>
                    <dl class="result-dl">
                        <dt>备案号</dt><dd class="premium-number-text"><?php echo htmlspecialchars($result['number']); ?></dd>
                        <dt>网站名称</dt><dd><?php echo htmlspecialchars($result['website_name']); ?></dd>
                        <dt>网站地址</dt><dd><a href="https://<?php echo htmlspecialchars($result['domain']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($result['domain']); ?></a></dd>
                        <dt>审核时间</dt><dd><?php echo date('Y-m-d H:i', strtotime($result['reviewed_at'])); ?></dd>
                    </dl>
                </div>
            </div>
        <?php else: ?>
            <!-- 全新普通号码卡片结构 -->
            <div class="content result-card normal-result-card">
                <h2 class="holographic-text" style="margin-bottom: 1.5rem;">备案详情</h2>
                <dl class="result-dl">
                    <dt>备案号</dt><dd><?php echo htmlspecialchars($result['number']); ?></dd>
                    <dt>网站名称</dt><dd><?php echo htmlspecialchars($result['website_name']); ?></dd>
                    <dt>网站地址</dt><dd><a href="https://<?php echo htmlspecialchars($result['domain']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($result['domain']); ?></a></dd>
                    <dt>审核时间</dt><dd><?php echo date('Y-m-d H:i', strtotime($result['reviewed_at'])); ?></dd>
                </dl>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- 状态查询卡片 -->
        <div class="content card-effect" style="max-width: 700px; margin: 20px auto; text-align: center;">
            <h2 class="holographic-text">备案状态查询</h2>
            <p>备案号 <strong><?php echo htmlspecialchars($result['number']); ?></strong> 的当前状态为：</p>
            <?php if ($result['status'] === 'pending' || $result['status'] === 'pending_payment'): ?>
                <h3 style="color: var(--accent-color);"><i class="fas fa-clock"></i> 审核中</h3>
                <p class="note">您的申请正在等待管理员审核，请耐心等待。</p>
            <?php elseif ($result['status'] === 'rejected'): ?>
                <h3 style="color: #f44336;"><i class="fas fa-times-circle"></i> 已驳回</h3>
                <p class="note">您的申请已被驳回。您可以查看详情并修改后重新提交。</p>
                <a href="details.php?id=<?php echo $result['id']; ?>" class="glow-button page-transition-link" style="margin-top: 1rem;">
                    <i class="fas fa-edit"></i> 查看详情并修改
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

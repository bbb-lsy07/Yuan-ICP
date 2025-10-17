<?php extract($data); ?>
<?php if ($announcement): ?>
    <div class="page-header">
        <h1><?php echo htmlspecialchars($announcement['title']); ?></h1>
        <p>
            <i class="fas fa-calendar"></i> 发布时间：<?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?>
            <?php if ($announcement['is_pinned']): ?>
                <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 8px;">置顶</span>
            <?php endif; ?>
        </p>
    </div>

    <div class="main-card" style="max-width: 800px; margin: 0 auto;">
        <div style="line-height: 1.8; color: var(--text-primary);">
            <?php echo $announcement['content']; ?>
        </div>
        
        <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--card-border-color);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <a href="announcements.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 返回公告列表
            </a>
            <div style="color: var(--text-secondary); font-size: 0.875rem;">
                最后更新：<?php echo date('Y-m-d H:i', strtotime($announcement['updated_at'])); ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="page-header">
        <h1>公告未找到</h1>
        <p>抱歉，您要查看的公告不存在或已被删除。</p>
    </div>
    
    <div class="main-card" style="max-width: 700px; margin: 0 auto; text-align: center;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--text-secondary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 style="color: var(--text-primary); margin-bottom: 0.5rem;">公告不存在</h2>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">请检查链接是否正确，或返回公告列表查看其他公告。</p>
        <a href="announcements.php" class="btn btn-primary">
            <i class="fas fa-list"></i> 查看所有公告
        </a>
    </div>
<?php endif; ?>
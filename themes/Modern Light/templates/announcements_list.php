<?php extract($data); ?>
<div class="page-header">
    <h1>所有公告</h1>
    <p>查看系统发布的所有公告信息。</p>
</div>

<?php if (empty($announcements)): ?>
    <div class="main-card" style="max-width: 700px; margin: 0 auto; text-align: center;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background-color: var(--text-secondary); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem;">
            <i class="fas fa-bullhorn"></i>
        </div>
        <h2 style="color: var(--text-primary); margin-bottom: 0.5rem;">暂无公告</h2>
        <p style="color: var(--text-secondary);">目前没有发布任何公告。</p>
    </div>
<?php else: ?>
    <div class="main-card" style="max-width: 900px; margin: 0 auto;">
        <ul class="announcement-list" style="list-style: none; padding: 0;">
            <?php foreach($announcements as $ann): ?>
            <li style="margin-bottom: 1rem;">
                <a href="announcement.php?id=<?php echo $ann['id']; ?>" style="display: block; text-decoration: none;">
                    <div style="padding: 1.5rem; background-color: rgba(255, 255, 255, 0.3); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: var(--radius-md); transition: all 0.2s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <h3 style="color: var(--text-primary); font-size: 1.125rem; font-weight: 600; margin: 0;">
                                <?php if ($ann['is_pinned']): ?>
                                    <span style="background: #f59e0b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-right: 8px;">置顶</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ann['title']); ?>
                            </h3>
                            <span style="font-size: 0.875rem; color: var(--text-secondary); white-space: nowrap;">
                                <?php echo date('Y-m-d', strtotime($ann['created_at'])); ?>
                            </span>
                        </div>
                        <?php if (!empty($ann['excerpt'])): ?>
                        <p style="color: var(--text-secondary); margin: 0; line-height: 1.5;">
                            <?php echo htmlspecialchars($ann['excerpt']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=<?php echo $pagination['current_page'] - 1; ?>" class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> 上一页
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="btn <?php echo $i === $pagination['current_page'] ? 'btn-primary' : 'btn-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="?page=<?php echo $pagination['current_page'] + 1; ?>" class="btn btn-secondary">
                    下一页 <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
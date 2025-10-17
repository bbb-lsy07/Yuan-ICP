<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text">公告公示</h1>
    <p class="note">查看所有系统公告。</p>
</div>

<div class="table-wrapper card-effect">
    <table>
        <thead>
            <tr>
                <th>标题</th>
                <th>发布时间</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($announcements)): ?>
                <tr>
                    <td colspan="2" style="text-align:center;">暂无公告</td>
                </tr>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <tr>
                        <td>
                            <?php if ($ann['is_pinned']): ?>
                                <span class="badge-pinned">置顶</span>
                            <?php endif; ?><a href="announcement.php?id=<?php echo $ann['id']; ?>" style="color:var(--text-color); text-decoration:none;" class="page-transition-link" title="<?php echo htmlspecialchars($ann['title']); ?>">
                                <?php echo htmlspecialchars($ann['title']); ?>
                            </a>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($ann['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav style="margin-top:20px; text-align:center;">
    <ul class="pagination" style="display:inline-flex; list-style:none; padding:0;">
        <?php if ($page > 1): ?>
            <li style="margin: 0 5px;"><a href="?page=<?php echo $page - 1; ?>" class="glow-button page-transition-link">上一页</a></li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li style="margin: 0 5px;"><a href="?page=<?php echo $i; ?>" class="glow-button page-transition-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li style="margin: 0 5px;"><a href="?page=<?php echo $page + 1; ?>" class="glow-button page-transition-link">下一页</a></li>
        <?php endif; ?>
    </ul>
    <style>
        .pagination a.active { 
            background: var(--neon-color) !important; 
            color: #000 !important; 
            box-shadow: 0 2px 8px rgba(var(--neon-color-rgb), 0.3) !important;
        }
        .pagination a:hover:not(.active) {
            background: rgba(var(--neon-color-rgb), 0.1) !important;
            border-color: var(--neon-color) !important;
        }
    </style>
</nav>
<?php endif; ?>
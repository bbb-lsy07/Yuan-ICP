<?php
// 从控制器解压变量
extract($data); 
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <h1 class="text-center mb-5">所有公告</h1>
            
            <?php if (empty($announcements)): ?>
                <div class="alert alert-info text-center">暂无公告内容。</div>
            <?php else: ?>
                <div class="list-group shadow-sm">
                    <?php foreach ($announcements as $ann): ?>
                        <a href="announcement.php?id=<?php echo $ann['id']; ?>" class="list-group-item list-group-item-action p-3">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?php if ($ann['is_pinned']): ?>
                                        <span class="badge bg-warning text-dark me-2">置顶</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </h5>
                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($ann['created_at'])); ?></small>
                            </div>
                            <p class="mb-1 text-muted"><?php echo mb_substr(strip_tags($ann['content']), 0, 100); ?>...</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 分页导航 -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">上一页</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">下一页</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

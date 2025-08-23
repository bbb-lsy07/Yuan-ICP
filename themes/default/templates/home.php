<?php extract($data); ?>

<!-- 英雄区域 -->
<section class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></h1>
        <p class="lead mb-5"><?php echo htmlspecialchars($config['seo_description'] ?? '一个开源、高度可定制化的虚拟ICP备案系统'); ?></p>
        <a href="apply.php" class="btn btn-light btn-lg px-4 me-2">立即申请</a>
        <a href="query.php" class="btn btn-outline-light btn-lg px-4">备案查询</a>
    </div>
</section>

<!-- 主要内容 -->
<div class="container my-5">
    <div class="row">
        <!-- 公告区域 -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-bullhorn me-2"></i>最新公告</h2>
                <a href="announcements.php" class="btn btn-outline-primary btn-sm">查看全部 &raquo;</a>
            </div>
            
            <?php if (empty($announcements)): ?>
                <div class="alert alert-info">暂无公告</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($announcements as $ann): ?>
                        <a href="announcement.php?id=<?php echo $ann['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?php if ($ann['is_pinned']): ?>
                                        <span class="badge bg-warning text-dark me-2">置顶</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </h5>
                                <small class="text-muted"><?php echo date('Y-m-d', strtotime($ann['created_at'])); ?></small>
                            </div>
                            <p class="mb-1 text-muted"><?php echo mb_substr(strip_tags($ann['content']), 0, 80); ?>...</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 备案查询 -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">备案查询</h4>
                    <form action="query.php" method="get">
                        <div class="mb-3">
                            <label for="icp_number" class="form-label">备案号</label>
                            <input type="text" class="form-control" id="icp_number" name="icp_number" placeholder="输入备案号">
                        </div>
                        <div class="mb-3">
                            <label for="domain" class="form-label">或域名</label>
                            <input type="text" class="form-control" id="domain" name="domain" placeholder="输入域名">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">查询</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h4 class="card-title mb-3">备案统计</h4>
                    <div class="row">
                        <div class="col-4">
                            <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                            <h3><?php echo $stats['total']; ?></h3>
                            <p class="text-muted">总备案</p>
                        </div>
                        <div class="col-4">
                            <div class="feature-icon"><i class="fas fa-check-circle"></i></div>
                            <h3><?php echo $stats['approved']; ?></h3>
                            <p class="text-muted">已通过</p>
                        </div>
                        <div class="col-4">
                            <div class="feature-icon"><i class="fas fa-clock"></i></div>
                            <h3><?php echo $stats['pending']; ?></h3>
                            <p class="text-muted">待审核</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
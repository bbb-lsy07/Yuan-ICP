<?php extract($data); ?>
<section class="hero-section animate-fade-in-up">
    <div class="hero-content">
        <h1 class="hero-title"><?php echo htmlspecialchars(get_theme_option('hero_title', $config['site_name'] ?? 'Yuan-ICP 虚拟备案系统')); ?></h1>
        <p class="hero-subtitle"><?php echo htmlspecialchars(get_theme_option('hero_subtitle', '虚拟备案系统')); ?></p>
        <p class="hero-description"><?php echo htmlspecialchars($config['seo_description'] ?? '一个开源、高度可定制化的虚拟ICP备案系统，为爱好者提供一个可爱的社区互动平台。'); ?></p>
        <div class="hero-actions">
            <a href="apply.php" class="btn btn-primary page-transition-link">
                <i class="fas fa-rocket"></i> 立即申请
            </a>
            <a href="query.php" class="btn btn-secondary page-transition-link">
                <i class="fas fa-search"></i> 查询备案
            </a>
        </div>
    </div>
    <?php if (get_theme_option('show_hero_avatar', '1') === '1'): ?>
    <div class="hero-avatar">
        <div class="avatar-wrapper">
            <img src="/img/oiips.jpeg" alt="Avatar">
        </div>
    </div>
    <?php endif; ?>
</section>

<section class="stats-grid animate-fade-in-up">
    <div class="stat-item">
        <div class="value"><?php echo (int)($stats['total'] ?? 0); ?></div>
        <div class="label">总备案数</div>
    </div>
    <div class="stat-item">
        <div class="value"><?php echo (int)($stats['approved'] ?? 0); ?></div>
        <div class="label">已通过</div>
    </div>
    <div class="stat-item">
        <div class="value"><?php echo (int)($stats['pending'] ?? 0); ?></div>
        <div class="label">待审核</div>
    </div>
</section>

<section class="announcement-grid animate-fade-in-up">
    <div class="main-card">
        <h2 class="section-title"><i class="fas fa-bullhorn"></i> 最新公告</h2>
        <?php if (empty($announcements)): ?>
            <p class="empty-state">暂无公告</p>
        <?php else: ?>
            <ul class="announcement-list">
                <?php foreach($announcements as $ann): ?>
                <li>
                    <a href="announcement.php?id=<?php echo $ann['id']; ?>" class="page-transition-link">
                        <div>
                            <span class="title">
                                <?php if ($ann['is_pinned']): ?>
                                    <span class="badge-pinned">置顶</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ann['title']); ?>
                            </span>
                            <span class="date"><?php echo date('Y-m-d', strtotime($ann['created_at'])); ?></span>
                        </div>
                        <i class="fas fa-chevron-right icon-arrow"></i>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="view-all-container">
                <a href="announcements.php" class="btn btn-secondary page-transition-link"><i class="fas fa-list"></i> 查看全部公告</a>
            </div>
        <?php endif; ?>
    </div>
    <div class="main-card">
        <h2 class="section-title"><i class="fas fa-info-circle"></i> 关于我们</h2>
        <p class="about-text">
            我们致力于打造一个开放、友好的社区。快来给您的网站添加一个专属的联盟 ICP 号吧！
        </p>
        <ul class="features-list">
            <li><i class="fas fa-check-circle"></i><span>完全免费使用</span></li>
            <li><i class="fas fa-shield-alt"></i><span>安全可靠</span></li>
            <li><i class="fas fa-users"></i><span>社区支持</span></li>
        </ul>
    </div>
</section>
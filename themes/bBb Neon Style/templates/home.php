<?php extract($data); ?>
<div class="header">
    <h1 class="holographic-text"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP 虚拟备案系统'); ?></h1>
    <p class="note"><?php echo htmlspecialchars($config['seo_description'] ?? '一个开源、高度可定制化的虚拟ICP备案系统，为爱好者提供一个可爱的社区互动平台。'); ?></p>
</div>

<!-- 关于板块 -->
<div class="about-section">
    <div class="about-card card-effect">
        <h3><i class="fas fa-question-circle neon-icon"></i> 什么是虚拟备案？</h3>
        <p>一个虚拟的网站备案系统，旨在为站长和爱好者提供一个有趣的社区互动平台，让您的网站拥有一个独特的、富有个性的身份标识。</p>
    </div>
    <div class="about-card card-effect">
        <h3><i class="fas fa-thumbs-up neon-icon"></i> 为什么加入我们？</h3>
        <p>加入我们，让您的网站拥有一个独特的备案号，既美观又有趣。这不仅仅是一个号码，更是您作为站长个性的独特展示。</p>
    </div>
    <div class="about-card card-effect">
        <h3><i class="fas fa-hands-helping neon-icon"></i> 我们有话说</h3>
        <p>欢迎所有喜欢虚拟备案的朋友！我们致力于打造一个开放、友好的社区。快来给您的网站添加一个专属的联盟 ICP 号吧！</p>
    </div>
</div>

<!-- 统计板块 -->
<div class="stats-section">
    <div class="stat-card card-effect">
        <div class="stat-icon"><i class="fas fa-globe-asia"></i></div>
        <div class="stat-number"><?php echo (int)($stats['total'] ?? 0); ?></div>
        <div class="stat-title">总备案数</div>
    </div>
    <div class="stat-card card-effect">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-number"><?php echo (int)($stats['approved'] ?? 0); ?></div>
        <div class="stat-title">已通过</div>
    </div>
    <div class="stat-card card-effect">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-number"><?php echo (int)($stats['pending'] ?? 0); ?></div>
        <div class="stat-title">待审核</div>
    </div>
</div>

<!-- 公告与操作 -->
<div class="main-content-grid">
    <div class="announcements-panel card-effect">
        <h2><i class="fas fa-bullhorn"></i> 最新公告</h2>
        <?php if (empty($announcements)): ?>
            <p>暂无公告</p>
        <?php else: ?>
            <ul class="announcement-list">
                <?php foreach($announcements as $ann): ?>
                    <li>
                        <a href="announcement.php?id=<?php echo $ann['id']; ?>">
                            <span>
                                <?php if ($ann['is_pinned']): ?>
                                    <span class="badge-pinned">置顶</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($ann['title']); ?>
                            </span>
                            <span class="date"><?php echo date('Y-m-d', strtotime($ann['created_at'])); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="announcements.php" class="view-all-link page-transition-link">查看全部 &raquo;</a>
        <?php endif; ?>
    </div>
    
    <div class="cta-panel card-effect">
        <h2>立即行动</h2>
        <p>准备好为你的网站增添一份独特的色彩了吗？立即加入我们，开启你的虚拟备案之旅。</p>
        <div class="button-container">
            <button class="glow-button primary large" onclick="location.href='apply.php'">
                <i class="fas fa-rocket"></i> 立即申请
            </button>
            <button class="glow-button secondary large" onclick="location.href='query.php'">
                <i class="fas fa-search"></i> 备案查询
            </button>
        </div>
    </div>
</div>
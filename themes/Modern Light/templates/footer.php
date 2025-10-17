<?php extract($data); ?>
    </main>
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-grid">
                <div class="footer-about">
                    <a href="/" class="logo-link">
                        <div class="logo-img"><img src="/img/oiips.jpeg" alt="Logo"></div>
                        <span class="site-name"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></span>
                    </a>
                    <p><?php echo htmlspecialchars($config['seo_description'] ?? '一个开源、高度可定制化的虚拟ICP备案系统。'); ?></p>
                </div>
                <div>
                    <h3 class="footer-heading">快速导航</h3>
                    <ul class="footer-nav-list">
                        <li><a href="index.php">首页</a></li>
                        <li><a href="apply.php">申请备案</a></li>
                        <li><a href="query.php">查询备案</a></li>
                        <li><a href="announcements.php">所有公告</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="footer-heading">联系方式</h3>
                    <ul class="footer-nav-list">
                        <li><i class="fas fa-envelope"></i> admin@example.com</li>
                        <li><i class="fas fa-map-marker-alt"></i> 中国</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                 <p>
                    <?php if (get_theme_option('show_footer_copyright', '1') == '1'): ?>
                        <span><?php echo htmlspecialchars($config['footer_copyright'] ?? ('Copyright © ' . date('Y') . ' ' . ($config['site_name'] ?? 'Yuan-ICP'))); ?></span>
                    <?php endif; ?>
                    
                    <?php if (get_theme_option('show_footer_icp', '1') == '1' && !empty($config['footer_icp_beian'])): ?>
                        <a href="<?php echo htmlspecialchars($config['footer_icp_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" style="margin: 0 5px;"><?php echo htmlspecialchars($config['footer_icp_beian']); ?></a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_option('show_footer_gongan', '1') == '1' && !empty($config['footer_gongan_beian'])): ?>
                        <a href="<?php echo htmlspecialchars($config['footer_gongan_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" style="margin: 0 5px;">
                            <?php echo htmlspecialchars($config['footer_gongan_beian']); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo get_theme_url(); ?>/js/main.js"></script>
</body>
</html>
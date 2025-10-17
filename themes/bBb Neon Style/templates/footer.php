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
            </div>
            <div class="footer-bottom">
                <p>
                    <?php if (get_theme_option('show_footer_copyright', '1') === '1'): ?>
                        <?php echo htmlspecialchars($config['footer_copyright'] ?? ('&copy; ' . date('Y') . ' ' . ($config['site_name'] ?? 'Yuan-ICP'))); ?>
                    <?php endif; ?>
                    
                    <?php if (get_theme_option('show_footer_icp', '1') === '1' && !empty($config['footer_icp_beian'])): ?>
                        <a href="<?php echo htmlspecialchars($config['footer_icp_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer" style="margin: 0 5px;"><?php echo htmlspecialchars($config['footer_icp_beian']); ?></a>
                    <?php endif; ?>
                    
                    <?php if (get_theme_option('show_footer_gongan', '1') === '1' && !empty($config['footer_gongan_beian'])): ?>
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
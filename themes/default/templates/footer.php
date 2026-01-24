    </div> <!-- 关闭在 header.php 中开始的 .main-content div -->
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container text-center">
            <?php
            // 从数据库或默认值解析页脚版权
            $footer_text = $config['footer_copyright'] ?? '© {year} {site_name}. 保留所有权利。';
            $footer_text = str_replace('{year}', date('Y'), $footer_text);
            $footer_text = str_replace('{site_name}', htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'), $footer_text);
            ?>
            <p class="mb-0"><?php echo $footer_text; ?></p>
            <?php if (($config['show_footer_icp'] ?? '1') === '1' && !empty($config['footer_icp_beian'])): ?>
                <a href="<?php echo htmlspecialchars($config['footer_icp_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($config['footer_icp_beian']); ?></a>
            <?php endif; ?>
            <?php if (($config['show_footer_gongan'] ?? '1') === '1' && !empty($config['footer_gongan_beian'])): ?>
                <a href="<?php echo htmlspecialchars($config['footer_gongan_link'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($config['footer_gongan_beian']); ?></a>
            <?php endif; ?>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php // 检查并输出页面特定的外部JS文件 ?>
    <?php if (!empty($page_scripts)): ?>
        <?php foreach ($page_scripts as $script_url): ?>
            <script src="<?php echo htmlspecialchars($script_url); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php // 检查并输出页面特定的内联JavaScript ?>
    <?php if (!empty($inline_script)): ?>
        <script>
            <?php echo $inline_script; ?>
        </script>
    <?php endif; ?>
</body>
</html>

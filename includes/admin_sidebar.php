<?php
// /includes/admin_sidebar.php - V4 (修复版)

// 获取当前页面信息，用于判断菜单激活状态
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPlugin = $_GET['plugin'] ?? null;

// 获取所有由插件注册的菜单项
$plugin_menus = EnhancedPluginHooks::getAdminMenus();

?>
<nav class="sidebar">
    <div class="sidebar-header">
        <h3>Yuan-ICP</h3>
    </div>
    <ul class="list-unstyled components">
        
        <!-- 核心系统菜单 -->
        <li class="<?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> 仪表盘</a>
        </li>

        <li class="<?php echo in_array($currentPage, ['applications.php', 'application_edit.php']) ? 'active' : ''; ?>">
            <a href="applications.php"><i class="fas fa-file-alt"></i> 备案管理</a>
        </li>

        <li class="<?php echo in_array($currentPage, ['announcements.php', 'announcement_edit.php']) ? 'active' : ''; ?>">
            <a href="announcements.php"><i class="fas fa-bullhorn"></i> 公告管理</a>
        </li>
        
        <li class="<?php echo ($currentPage === 'numbers.php') ? 'active' : ''; ?>">
            <a href="numbers.php"><i class="fas fa-list-ol"></i> 号码池管理</a>
        </li>

        <li class="<?php echo in_array($currentPage, ['plugins.php', 'themes.php', 'theme_options.php', 'debug_plugins.php', 'debug_themes.php']) ? 'active' : ''; ?>">
            <a href="#extensions" data-bs-toggle="collapse" aria-expanded="<?php echo in_array($currentPage, ['plugins.php', 'themes.php', 'theme_options.php', 'debug_plugins.php', 'debug_themes.php']) ? 'true' : 'false'; ?>" class="dropdown-toggle">
                <i class="fas fa-puzzle-piece"></i> 扩展管理
            </a>
            <ul class="collapse list-unstyled <?php echo in_array($currentPage, ['plugins.php', 'themes.php', 'theme_options.php', 'debug_plugins.php', 'debug_themes.php']) ? 'show' : ''; ?>" id="extensions">
                <li class="<?php echo in_array($currentPage, ['plugins.php', 'debug_plugins.php']) ? 'active' : ''; ?>">
                    <a href="plugins.php">插件管理</a>
                </li>
                <li class="<?php echo in_array($currentPage, ['themes.php', 'theme_options.php', 'debug_themes.php']) ? 'active' : ''; ?>">
                    <a href="themes.php">主题管理</a>
                </li>
            </ul>
        </li>

        <li class="<?php echo ($currentPage === 'backup.php') ? 'active' : ''; ?>">
            <a href="backup.php"><i class="fas fa-database"></i> 备份与恢复</a>
        </li>

        <li class="<?php echo ($currentPage === 'settings.php') ? 'active' : ''; ?>">
            <a href="settings.php"><i class="fas fa-cogs"></i> 系统设置</a>
        </li>
        
        <li class="<?php echo ($currentPage === 'logs.php') ? 'active' : ''; ?>">
            <a href="logs.php"><i class="fas fa-clipboard-list"></i> 系统日志</a>
        </li>

        <!-- +++ 新增代码开始 +++ -->
        <li class="<?php echo ($currentPage === 'update.php') ? 'active' : ''; ?>">
            <a href="update.php"><i class="fas fa-cloud-upload-alt"></i> 系统在线更新</a>
        </li>
        <!-- +++ 新增代码结束 +++ -->

        <!-- 插件菜单分隔与渲染 -->
        <?php if (!empty($plugin_menus)): ?>
            <li class="sidebar-divider" style="padding: 0 20px; margin: 1rem 0;">
                <hr style="border-top: 1px solid var(--border-color);">
            </li>
            
            <?php foreach ($plugin_menus as $menu): ?>
                <?php
                if (empty($menu['parent'])):
                    // *** 核心修复 ***
                    // 从菜单的URL中解析出 'plugin' 参数值
                    $menu_plugin_identifier = null;
                    $url_parts = parse_url($menu['url']);
                    if (isset($url_parts['query'])) {
                        parse_str($url_parts['query'], $query_params);
                        if (isset($query_params['plugin'])) {
                            $menu_plugin_identifier = $query_params['plugin'];
                        }
                    }
                    
                    // 通过比较URL参数来判断是否为活动页面
                    $is_plugin_page_active = ($currentPage === 'plugin_proxy.php' && $currentPlugin === $menu_plugin_identifier);
                ?>
                <li class="<?php echo $is_plugin_page_active ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($menu['url']); ?>">
                        <i class="<?php echo htmlspecialchars($menu['icon'] ?? 'fas fa-plug'); ?>"></i> <?php echo htmlspecialchars($menu['title']); ?>
                    </a>
                </li>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</nav>
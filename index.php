<?php
// 引入环境配置
require_once __DIR__.'/includes/Environment.php';

// 初始化环境配置
Environment::init();

// 检查安装状态，这是程序执行的第一件事
if (!file_exists(__DIR__.'/config/database.php') || filesize(__DIR__.'/config/database.php') < 10) {
    // 如果配置文件不存在或为空，立即重定向到安装程序
    header('Location: /install/step1.php');
    exit;
}

// 只有在确认已安装后，才加载核心系统
require_once __DIR__.'/includes/bootstrap.php';

try {
    $db = db();
    
    // 检查是否为预览模式
    $previewTheme = $_GET['preview_theme'] ?? null;
    if ($previewTheme && isset($_SESSION['preview_theme']) && $_SESSION['preview_theme'] === $previewTheme) {
        // 临时设置预览主题
        $originalTheme = ThemeManager::getActiveTheme();
        $_SESSION['original_theme'] = $originalTheme;
        
        // 设置预览主题选项
        if (isset($_SESSION['preview_options'])) {
            foreach ($_SESSION['preview_options'] as $key => $value) {
                ThemeManager::setThemeOption($key, $value, $previewTheme);
            }
        }
    }
    
    // 加载系统配置
    $config = get_config();

    // 【代码修改】查询最新的6条公告，置顶的优先显示
    $announcements = $db->query("
        SELECT id, title, content, created_at, is_pinned 
        FROM announcements 
        ORDER BY is_pinned DESC, created_at DESC 
        LIMIT 6
    ")->fetchAll();
    
    // 加载统计信息
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(status = 'approved') as approved,
            SUM(status = 'pending') as pending
        FROM icp_applications
    ")->fetch();

    // 准备传递给模板的数据
    $data = [
        'config' => $config,
        'announcements' => $announcements,
        'stats' => $stats,
        'page_title' => $config['site_name'] ?? 'Yuan-ICP',
        'active_page' => 'home'
    ];

    // 渲染页面
    ThemeManager::render('header', $data);
    ThemeManager::render('home', $data);
    ThemeManager::render('footer', $data);

} catch (Exception $e) {
    handle_error('系统初始化失败: ' . $e->getMessage());
}
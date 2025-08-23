<?php
// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__.'/data/error.log');

// 检查安装
if (!file_exists(__DIR__.'/config/database.php') || filesize(__DIR__.'/config/database.php') < 100) {
    header('Location: /install/step1.php');
    exit;
}

// 引入核心文件
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/theme_manager.php';

try {
    $db = db();
    
    // 加载系统配置
    $stmt_config = $db->query("SELECT config_key, config_value FROM system_config");
    $config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

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
    die('系统初始化失败: ' . htmlspecialchars($e->getMessage()));
}
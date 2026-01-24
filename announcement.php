<?php
// announcement.php

// 引入核心文件
require_once __DIR__.'/includes/bootstrap.php';

try {
    $db = db();

    // 获取URL中的公告ID
    $announcement_id = intval($_GET['id'] ?? 0);
    if ($announcement_id <= 0) {
        // 如果没有ID，重定向到首页
        header("Location: index.php");
        exit;
    }

    // 从数据库查询公告
    $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$announcement_id]);
    $announcement = $stmt->fetch();

    // 如果公告不存在，也重定向到首页
    if (!$announcement) {
        header("Location: index.php");
        exit;
    }
    
    // 加载系统配置，用于页眉和页脚
    $stmt_config = $db->query("SELECT config_key, config_value FROM system_config");
    $config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

    // 准备传递给模板的数据
    $data = [
        'config' => $config,
        'announcement' => $announcement,
        'page_title' => htmlspecialchars($announcement['title']) . ' - ' . ($config['site_name'] ?? 'Yuan-ICP'),
        'active_page' => 'announcements' // 用于导航栏高亮
    ];

    // 渲染页面
    ThemeManager::render('header', $data);
    ThemeManager::render('announcement', $data); // 使用新的announcement模板
    ThemeManager::render('footer', $data);

} catch (Exception $e) {
    // 简单的错误处理
    die('系统发生错误: ' . htmlspecialchars($e->getMessage()));
}

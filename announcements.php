<?php
// announcements.php

// 引入核心文件
require_once __DIR__.'/includes/bootstrap.php';

try {
    $db = db();

    // --- 分页逻辑 ---
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 10; // 每页显示10条公告
    $offset = ($page - 1) * $perPage;

    // 获取公告总数用于计算总页数
    $totalItems = $db->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
    $totalPages = ceil($totalItems / $perPage);

    // 查询当前页的公告数据，置顶的优先
    $stmt = $db->prepare("
        SELECT id, title, content, created_at, is_pinned 
        FROM announcements 
        ORDER BY is_pinned DESC, created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $announcements = $stmt->fetchAll();
    
    // 加载系统配置
    $stmt_config = $db->query("SELECT config_key, config_value FROM system_config");
    $config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

    // 准备传递给模板的数据
    $data = [
        'config' => $config,
        'announcements' => $announcements,
        'page' => $page,
        'totalPages' => $totalPages,
        'page_title' => '所有公告 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
        'active_page' => 'announcements'
    ];

    // 渲染页面
    ThemeManager::render('header', $data);
    ThemeManager::render('announcements_list', $data); // 使用新的列表模板
    ThemeManager::render('footer', $data);

} catch (Exception $e) {
    die('系统发生错误: ' . htmlspecialchars($e->getMessage()));
}

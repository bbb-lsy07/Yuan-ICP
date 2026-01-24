<?php
require_once __DIR__.'/includes/bootstrap.php';

// 检查会话中是否存在上一步的数据，如果没有则返回第一步
if (!isset($_SESSION['application_data'])) {
    header("Location: apply.php");
    exit;
}

$db = db();
$config = get_config();

// 准备传递给模板的数据
$data = [
    'config' => $config,
    'page_title' => '选择备案号 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'apply', // 修改为apply，让导航保持高亮
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('select_number', $data);
ThemeManager::render('footer', $data);

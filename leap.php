<?php
require_once __DIR__.'/includes/bootstrap.php';

$db = db();

$sites = $db->query("
    SELECT domain 
    FROM icp_applications 
    WHERE status = 'approved' 
    AND domain IS NOT NULL
    AND domain != ''
")->fetchAll(PDO::FETCH_COLUMN);

$target_site = 'index.php'; // 默认跳转回首页
if (!empty($sites)) {
    $random_index = array_rand($sites);
    $target_site = 'https://' . $sites[$random_index];
}

// 加载系统配置
$config = get_config();

// 准备数据
$data = [
    'target_site' => $target_site,
    'config' => $config,
    'page_title' => '网站迁跃中... - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'leap'
];

// 只渲染 leap 模板，不包含 header 和 footer
ThemeManager::render('leap', $data);
<?php
// 修正了文件引用路径
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/theme_manager.php';

$db = db();

// 获取所有已通过的备案网站
$sites = $db->query("
    SELECT domain 
    FROM icp_applications 
    WHERE status = 'approved' 
    AND domain IS NOT NULL
    AND domain != ''
")->fetchAll(PDO::FETCH_COLUMN);

// 随机选择一个网站
$target_site = 'index.php'; // 默认跳转回首页
if (!empty($sites)) {
    $random_index = array_rand($sites);
    $target_site = 'https://' . $sites[$random_index];
}

// 准备数据
$data = [
    'target_site' => $target_site,
    'page_title' => '网站迁跃中...',
    'page_scripts' => [ // 加载外部JS文件
        '/scripts/leap.js'
    ]
];

// 渲染页面 (注意：迁跃页面是全屏的，所以不加载 header 和 footer)
ThemeManager::render('leap', $data);
<?php
require_once __DIR__.'/includes/bootstrap.php';

$db = db();

// 验证申请ID
$application_id = intval($_GET['application_id'] ?? 0);
if ($application_id <= 0) {
    header("Location: apply.php");
    exit;
}

// 获取申请信息
$stmt = $db->prepare("SELECT * FROM icp_applications WHERE id = ?");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: apply.php");
    exit;
}

$config = get_config();

// 准备传递给模板的数据
$data = [
    'application' => $application,
    'page_title' => '备案结果 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'apply', // 修改为apply，让导航保持高亮
    'config' => $config
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('result', $data);
ThemeManager::render('footer', $data);

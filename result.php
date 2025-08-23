<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/theme_manager.php';

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

// 生成需要放置的HTML代码
$site_url = $db->query("SELECT config_value FROM system_config WHERE config_key = 'site_url'")->fetchColumn();
$html_code = htmlspecialchars('<a href="'.($site_url ?: 'https://icp.example.com').'/query.php?icp_number='.$application['number'].'" target="_blank">备案号: '.$application['number'].'</a>');

// 准备传递给模板的数据
$data = [
    'application' => $application,
    'html_code' => $html_code,
    'page_title' => '备案结果 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'result'
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('result', $data);
ThemeManager::render('footer', $data);

<?php
require_once __DIR__.'/includes/bootstrap.php';

$db = db();
$application_id = intval($_GET['id'] ?? 0);
$step = $_SESSION['verification_step'][$application_id] ?? 1;
$error = $_SESSION['verification_error'] ?? null;
unset($_SESSION['verification_error']);

$stmt = $db->prepare("SELECT * FROM icp_applications WHERE id = ?");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: query.php");
    exit;
}

// 屏蔽已通过的申请访问此页面
if ($application['status'] === 'approved') {
    header("Location: query.php?icp_number=" . urlencode($application['number']));
    exit;
}

$config = get_config();
$data = [
    'config' => $config,
    'application' => $application,
    'page_title' => '管理我的备案申请 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'query',
    'step' => $step,
    'error' => $error,
    'masked_email' => mask_email($application['owner_email'])
];

ThemeManager::render('header', $data);
ThemeManager::render('details', $data);
ThemeManager::render('footer', $data);

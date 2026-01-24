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

// 计算排队位置 (仅针对待审核状态)
if ($application['status'] === 'pending') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM icp_applications WHERE status = 'pending' AND id < ?");
    $stmt->execute([$application['id']]);
    $queue_position = $stmt->fetchColumn() + 1;
} else {
    $queue_position = 0;
}

// 获取统计数据 (仅针对已通过状态)
$stats = [];
if ($application['status'] === 'approved') {
    // 检查表是否存在
    $has_table = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plugin_stats_badge_logs'")->fetch();
    if ($has_table) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM plugin_stats_badge_logs WHERE application_id = ?");
        $stmt->execute([$application['id']]);
        $stats['total'] = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM plugin_stats_badge_logs WHERE application_id = ? AND date(requested_at) = date('now')");
        $stmt->execute([$application['id']]);
        $stats['today'] = $stmt->fetchColumn();
    } else {
        $stats = ['total' => 0, 'today' => 0];
    }
}

// 准备传递给模板的数据
$data = [
    'application' => $application,
    'queue_position' => $queue_position,
    'stats' => $stats,
    'page_title' => '备案结果 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'apply', // 修改为apply，让导航保持高亮
    'config' => $config
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('result', $data);
ThemeManager::render('footer', $data);

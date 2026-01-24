<?php
require_once __DIR__.'/includes/bootstrap.php';
$db = db();

// 1. 获取来源
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$from_host = parse_url($referer, PHP_URL_HOST);

// 2. 随机抽取一个已通过的站点（排除当前来源站，更有趣）
// 如果 $from_host 为空，则不进行排除（或者排除空字符串，效果一样）
$stmt = $db->prepare("SELECT id, domain, website_name FROM icp_applications WHERE status = 'approved' AND domain != ? ORDER BY RANDOM() LIMIT 1");
$stmt->execute([$from_host ?: '']);
$target = $stmt->fetch();

if (!$target) {
    // 兜底：如果没有其他站，就选自己 (或者任意一个已批准的)
    $target = $db->query("SELECT id, domain, website_name FROM icp_applications WHERE status = 'approved' LIMIT 1")->fetch();
}

// 3. 记录迁跃日志
if ($target) {
    // 尝试获取来源站的ID (如果来源站也是我们系统内的)
    $from_site_id = 0;
    if ($from_host) {
        $from_stmt = $db->prepare("SELECT id FROM icp_applications WHERE domain = ? LIMIT 1");
        $from_stmt->execute([$from_host]);
        $from_site = $from_stmt->fetch();
        if ($from_site) {
            $from_site_id = $from_site['id'];
        }
    }

    $log_stmt = $db->prepare("INSERT INTO plugin_leap_logs (from_site_id, to_site_id, from_domain, to_domain) VALUES (?, ?, ?, ?)");
    $log_stmt->execute([
        $from_site_id,
        $target['id'],
        $from_host ?: 'direct',
        $target['domain']
    ]);
}

$data = [
    'target' => $target,
    'target_site' => $target ? 'https://' . $target['domain'] : 'index.php', // 兼容旧主题
    'from_host' => $from_host, // 传递给模板
    'config' => get_config(),
    'page_title' => '星际迁跃中...'
];

ThemeManager::render('leap', $data);

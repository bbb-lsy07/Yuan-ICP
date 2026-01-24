<?php
/**
 * 获取随机探索数据 (Explore Blog World)
 */
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = db();
    
    // 获取 20 个随机的已通过站点
    // 注意：ORDER BY RANDOM() 在大数据量下可能稍慢，但对于一般ICP系统足够
    $stmt = $db->query("
        SELECT id, website_name, domain, owner_email, description, created_at 
        FROM icp_applications 
        WHERE status = 'approved' 
        ORDER BY RANDOM() 
        LIMIT 20
    ");
    
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = [];

    foreach ($sites as $site) {
        // 生成 Gravatar 头像链接
        // 使用 Cravatar 源以确保国内访问速度，也可以换回 gravatar.com
        $hash = md5(strtolower(trim($site['owner_email'])));
        $avatar = "https://cravatar.cn/avatar/{$hash}?s=100&d=identicon";

        $data[] = [
            'id' => $site['id'],
            'name' => $site['website_name'],
            'domain' => $site['domain'],
            'url' => 'https://' . $site['domain'], // 假设都是https
            'avatar' => $avatar,
            'desc' => $site['description'] ?: '这个博主很懒，什么都没写...',
            'join_date' => date('Y-m-d', strtotime($site['created_at'])),
            // 计算加入天数
            'days' => floor((time() - strtotime($site['created_at'])) / 86400)
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

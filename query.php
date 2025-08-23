<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/theme_manager.php'; // 引入主题管理器

$db = db();
$icp_number = trim($_GET['icp_number'] ?? '');
$domain = trim($_GET['domain'] ?? '');
$result = null;
$error = '';

// 处理查询
if (!empty($icp_number) || !empty($domain)) {
    $query = "SELECT a.*, u.username as reviewer 
              FROM icp_applications a
              LEFT JOIN admin_users u ON a.reviewed_by = u.id
              WHERE a.status = 'approved'";
    
    $params = [];
    
    if (!empty($icp_number)) {
        $query .= " AND a.number = ?";
        $params[] = $icp_number;
    }
    
    if (!empty($domain)) {
        $query .= " AND a.domain = ?";
        $params[] = $domain;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    if (!$result) {
        $error = '未找到匹配的备案信息';
    }
}

// 加载系统配置
$stmt_config = $db->query("SELECT config_key, config_value FROM system_config");
$config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

// 准备数据
$data = [
    'config' => $config,
    'icp_number' => $icp_number,
    'domain' => $domain,
    'result' => $result,
    'error' => $error,
    'page_title' => '备案查询 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'query'
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('query', $data);
ThemeManager::render('footer', $data);
?>

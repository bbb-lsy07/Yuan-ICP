<?php
require_once __DIR__.'/includes/bootstrap.php';

$db = db();
$icp_number = trim($_GET['icp_number'] ?? '');
$domain = trim($_GET['domain'] ?? '');
$result = null;
$error = '';
$is_premium = false; // 默认不是靓号

// 处理查询
if (!empty($icp_number) || !empty($domain)) {
    // 修改查询逻辑，不再限制 status = 'approved'
    $query = "SELECT a.*, u.username as reviewer 
              FROM icp_applications a
              LEFT JOIN admin_users u ON a.reviewed_by = u.id
              WHERE";
    
    $params = [];
    
    if (!empty($icp_number)) {
        $query .= " a.number = ?";
        $params[] = $icp_number;
    } elseif (!empty($domain)) {
        $query .= " a.domain = ?";
        $params[] = $domain;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    $is_premium = false; // 默认不是靓号
    if ($result) {
        // 如果状态不是已通过，则重定向到更详细的 result 页面
        if ($result['status'] !== 'approved') {
            header('Location: result.php?application_id=' . $result['id']);
            exit;
        }
        // 只有已通过的才继续在本页显示，并判断是否为靓号
        $is_premium = check_if_number_is_premium($result['number']);
    }
    
    if (!$result) {
        $error = '未找到匹配的备案信息，请检查输入是否正确。';
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
    'is_premium' => $is_premium, // <-- 将靓号状态传递给模板
    'page_title' => '备案查询 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'query'
];



// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('query', $data);
ThemeManager::render('footer', $data);
?>

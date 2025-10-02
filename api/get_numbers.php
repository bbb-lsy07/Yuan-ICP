<?php
// api/get_numbers.php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

header('Content-Type: application/json');

$db = db();
$is_auto_generate_enabled = (bool)get_config('number_auto_generate', false);

// 获取参数
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 20; // 每次加载20个

$numbers = [];
$hasMore = false;

try {
    if ($is_auto_generate_enabled) {
        // 模式一：自动生成 (不支持搜索和分页，每次返回随机号码)
        $generated_numbers = [];
        $attempts = 0;
        while (count($numbers) < $perPage && $attempts < 200) {
            $unique_num = generate_unique_icp_number();
            if ($unique_num && !in_array($unique_num, $generated_numbers)) {
                $numbers[] = [
                    'number' => $unique_num,
                    'is_premium' => is_premium_number($unique_num)
                ];
                $generated_numbers[] = $unique_num;
            }
            $attempts++;
        }
        $hasMore = true; // 自动生成模式下总有更多
    } else {
        // 模式二：从数据库号码池获取
        $where = "WHERE status = 'available'";
        $params = [];
        if (!empty($search)) {
            $where .= " AND number LIKE ?";
            $params[] = "%$search%";
        }

        // 获取总数
        $totalStmt = $db->prepare("SELECT COUNT(*) FROM selectable_numbers " . $where);
        $totalStmt->execute($params);
        $totalItems = $totalStmt->fetchColumn();

        // 获取当前页数据
        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare("SELECT number, is_premium FROM selectable_numbers " . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        
        $i = 1;
        foreach($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        
        $stmt->execute();
        $db_numbers = $stmt->fetchAll();

        foreach ($db_numbers as $num) {
            $numbers[] = [
                'number' => $num['number'],
                'is_premium' => (bool)$num['is_premium']
            ];
        }
        $hasMore = ($page * $perPage) < $totalItems;
    }

    echo json_encode(['success' => true, 'numbers' => $numbers, 'has_more' => $hasMore]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

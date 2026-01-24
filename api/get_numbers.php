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
    // 混合模式逻辑 (Hybrid Mode)
    // 1. 优先从数据库号码池获取
    $where = "WHERE status = 'available'";
    $params = [];
    if (!empty($search)) {
        $where .= " AND number LIKE ?";
        $params[] = "%$search%";
    }

    // 获取总数 (仅统计数据库中的)
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM selectable_numbers " . $where);
    $totalStmt->execute($params);
    $dbTotalItems = $totalStmt->fetchColumn();

    // 获取当前页数据
    $offset = ($page - 1) * $perPage;
    
    // 如果请求的页码超出了数据库范围，但启用了自动生成，则不查数据库
    $db_numbers = [];
    if ($offset < $dbTotalItems) {
        $stmt = $db->prepare("SELECT number, is_premium FROM selectable_numbers " . $where . " ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        
        $i = 1;
        foreach($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        
        $stmt->execute();
        $db_numbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($db_numbers as $num) {
        $numbers[] = [
            'number' => $num['number'],
            'is_premium' => (bool)$num['is_premium']
        ];
    }

    // 2. 如果启用了自动生成，且当前页没填满，则补充自动生成的号码
    if ($is_auto_generate_enabled && count($numbers) < $perPage) {
        // 计算需要补充多少个
        $needed = $perPage - count($numbers);
        $attempts = 0;
        
        // 自动生成的号码我们无法支持搜索过滤（太复杂），所以只在非搜索状态下补充，或者简单的随机生成
        // 这里保持简单，混合模式下搜索只搜库内，不搜生成的
        if (empty($search)) {
            while (count($numbers) < $perPage && $attempts < 200) {
                $unique_num = generate_unique_icp_number();
                
                // 确保不与已有的（库内的）重复
                $is_duplicate = false;
                foreach ($numbers as $n) {
                    if ($n['number'] === $unique_num) {
                        $is_duplicate = true;
                        break;
                    }
                }

                if ($unique_num && !$is_duplicate) {
                    $numbers[] = [
                        'number' => $unique_num,
                        'is_premium' => is_premium_number($unique_num)
                    ];
                }
                $attempts++;
            }
        }
    }
    
    // 判断是否有更多
    if ($is_auto_generate_enabled && empty($search)) {
        $hasMore = true; // 自动生成模式下总是无限的
    } else {
        $hasMore = ($page * $perPage) < $dbTotalItems;
    }

    echo json_encode(['success' => true, 'numbers' => $numbers, 'has_more' => $hasMore]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
/**
 * 获取公告列表的API接口
 * 支持分页和搜索
 */
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    // 获取查询参数
    $page = max(1, intval($_GET['page'] ?? 1));
    $search = trim($_GET['search'] ?? '');
    $perPage = intval($_GET['per_page'] ?? 10);
    
    // 限制每页最大数量
    $perPage = min($perPage, 100);
    
    // 构建基础查询
    $db = db();
    $query = "SELECT * FROM announcements";
    $where = [];
    $params = [];
    
    // 添加搜索条件
    if (!empty($search)) {
        $where[] = "(title LIKE ? OR content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // 组合WHERE条件
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    // 获取总数用于分页
    $countQuery = "SELECT COUNT(*) FROM ($query) as total";
    $total = $db->prepare($countQuery);
    $total->execute($params);
    $totalItems = $total->fetchColumn();
    
    // 计算分页
    $totalPages = ceil($totalItems / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // 获取当前页数据
    $query .= " ORDER BY is_pinned DESC, created_at DESC LIMIT $offset, $perPage";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll();
    
    // 处理数据，添加额外信息
    foreach ($announcements as &$ann) {
        $ann['created_at_formatted'] = date('Y-m-d H:i', strtotime($ann['created_at']));
        $ann['content_preview'] = mb_substr(strip_tags($ann['content']), 0, 50) . '...';
    }
    
    // 返回JSON响应
    echo json_encode([
        'success' => true,
        'data' => [
            'announcements' => $announcements,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $perPage,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("API Error in get_announcements.php: " . $e->getMessage());
    
    // 返回错误响应
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '服务器内部错误',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

<?php
/**
 * 获取备案申请列表的API接口
 * 支持分页、搜索和状态筛选
 */
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

// 检查查看申请权限
if (!has_permission('application.view')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => '您没有权限查看申请列表'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    // 获取查询参数
    $status = $_GET['status'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));
    $search = trim($_GET['search'] ?? '');
    $perPage = intval($_GET['per_page'] ?? 15);
    
    // 限制每页最大数量
    $perPage = min($perPage, 100);
    
    // 构建基础查询
    $db = db();
    $query = "SELECT a.*, u.username as reviewer FROM icp_applications a
              LEFT JOIN admin_users u ON a.reviewed_by = u.id";
    $where = [];
    $params = [];
    
    // 添加状态筛选
    if (in_array($status, ['pending', 'approved', 'rejected'])) {
        $where[] = "a.status = ?";
        $params[] = $status;
    }
    
    // 添加搜索条件
    if (!empty($search)) {
        $where[] = "(a.website_name LIKE ? OR a.domain LIKE ? OR a.number LIKE ?)";
        $params[] = "%$search%";
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
    $query .= " ORDER BY a.created_at DESC LIMIT $offset, $perPage";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();
    
    // 处理数据，添加额外信息
    foreach ($applications as &$app) {
        $app['is_premium'] = check_if_number_is_premium($app['number']);
        $app['created_at_formatted'] = date('Y-m-d H:i', strtotime($app['created_at']));
        $app['status_text'] = $app['status'] === 'approved' ? '已通过' : 
                             ($app['status'] === 'pending' ? '待审核' : '已驳回');
        $app['status_class'] = $app['status'] === 'approved' ? 'success' : 
                              ($app['status'] === 'pending' ? 'warning' : 'danger');
    }
    
    // 返回JSON响应
    echo json_encode([
        'success' => true,
        'data' => [
            'applications' => $applications,
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
    error_log("API Error in get_applications.php: " . $e->getMessage());
    
    // 返回错误响应
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '服务器内部错误',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

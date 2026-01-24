<?php
/**
 * 获取仪表盘统计数据的API接口
 */
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

try {
    $appManager = new ApplicationManager();
    
    // 获取基础统计数据
    $stats = $appManager->getStats();
    
    // 获取近7日申请量趋势
    $trendData = getApplicationTrend(7);
    
    // 获取备案状态分布
    $statusDistribution = getStatusDistribution();
    
    // 返回JSON响应
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'trend' => $trendData,
            'statusDistribution' => $statusDistribution
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 返回错误响应
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 获取申请量趋势数据
 */
function getApplicationTrend($days = 7) {
    $db = db();
    
    // 生成日期范围
    $dates = [];
    $counts = [];
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dates[] = $date;
        
        // 查询该日期的申请数量
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM icp_applications 
            WHERE DATE(created_at) = ?
        ");
        $stmt->execute([$date]);
        $result = $stmt->fetch();
        $counts[] = (int)$result['count'];
    }
    
    return [
        'labels' => $dates,
        'data' => $counts
    ];
}

/**
 * 获取状态分布数据
 */
function getStatusDistribution() {
    $db = db();
    
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM icp_applications 
        GROUP BY status
    ");
    
    $results = $stmt->fetchAll();
    
    $labels = [];
    $data = [];
    $colors = [];
    
    foreach ($results as $row) {
        $status = $row['status'];
        $count = (int)$row['count'];
        
        // 状态中文名称
        $statusNames = [
            'pending' => '待审核',
            'approved' => '已通过',
            'rejected' => '已驳回'
        ];
        
        // 状态颜色
        $statusColors = [
            'pending' => '#ffc107',
            'approved' => '#28a745',
            'rejected' => '#dc3545'
        ];
        
        $labels[] = $statusNames[$status] ?? $status;
        $data[] = $count;
        $colors[] = $statusColors[$status] ?? '#6c757d';
    }
    
    return [
        'labels' => $labels,
        'data' => $data,
        'colors' => $colors
    ];
}
?>

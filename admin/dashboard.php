<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

// 检查登录状态
require_login();

// 缓存5分钟
$cacheTime = 300;
$cacheDir = __DIR__.'/../cache'; // 定义 cache 目录
$cacheFile = $cacheDir.'/dashboard_stats.cache'; // 定义 cache 文件路径

// 检查并创建缓存目录
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
$db = db();

if (!file_exists($cacheFile) || (time() - filemtime($cacheFile)) > $cacheTime) {
    // 缓存失效或不存在，从数据库查询
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(status = 'pending') as pending,
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected
        FROM icp_applications
    ")->fetch();
    file_put_contents($cacheFile, json_encode($stats));
} else {
    // 从缓存读取
    $stats = json_decode(file_get_contents($cacheFile), true);
}

// 获取最近5条备案申请
$recentApps = $db->query("
    SELECT a.*, u.username as reviewer 
    FROM icp_applications a
    LEFT JOIN admin_users u ON a.reviewed_by = u.id
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 仪表盘</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar-header {
            padding: 20px;
            background: #212529;
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            display: block;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            color: white;
            background: #495057;
        }
        .sidebar .active a {
            color: white;
            background: #007bff;
        }
        .main-content {
            padding: 20px;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-card.total { background: #20c997; }
        .stat-card.pending { background: #fd7e14; }
        .stat-card.approved { background: #28a745; }
        .stat-card.rejected { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- 主内容区 -->
            <div class="col-md-10 main-content">
                <h2 class="mb-4">仪表盘</h2>
                
                <!-- 统计卡片 -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card total">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>总申请数</h5>
                                    <h2><?php echo $stats['total']; ?></h2>
                                </div>
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card pending">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>待审核</h5>
                                    <h2><?php echo $stats['pending']; ?></h2>
                                </div>
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card approved">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>已通过</h5>
                                    <h2><?php echo $stats['approved']; ?></h2>
                                </div>
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card rejected">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5>已驳回</h5>
                                    <h2><?php echo $stats['rejected']; ?></h2>
                                </div>
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 最近申请 -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">最近备案申请</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>备案号</th>
                                        <th>网站名称</th>
                                        <th>域名</th>
                                        <th>状态</th>
                                        <th>申请时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentApps as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['number']); ?></td>
                                        <td><?php echo htmlspecialchars($app['website_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['domain']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $app['status'] === 'approved' ? 'success' : 
                                                     ($app['status'] === 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo $app['status'] === 'approved' ? '已通过' : 
                                                      ($app['status'] === 'pending' ? '待审核' : '已驳回'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?></td>
                                        <td>
                                            <a href="applications.php?search=<?php echo urlencode($app['number']); ?>" class="btn btn-sm btn-primary">查看</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

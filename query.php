<?php
require_once __DIR__.'/includes/functions.php';

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
        $query .= " AND a.icp_number = ?";
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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>备案查询 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        .query-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .result-card {
            border-left: 4px solid #4a6cf7;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Yuan-ICP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apply.php">申请备案</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="query.php">备案查询</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leap.php">网站迁跃</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="query-container">
            <h2 class="mb-4 text-center">备案查询</h2>
            
            <!-- 查询表单 -->
            <form class="mb-5">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="icp_number" class="form-label">备案号</label>
                        <input type="text" class="form-control" id="icp_number" name="icp_number" 
                            value="<?php echo htmlspecialchars($icp_number); ?>" placeholder="输入备案号">
                    </div>
                    <div class="col-md-6">
                        <label for="domain" class="form-label">或域名</label>
                        <input type="text" class="form-control" id="domain" name="domain" 
                            value="<?php echo htmlspecialchars($domain); ?>" placeholder="输入域名">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100">查询</button>
                    </div>
                </div>
            </form>
            
            <!-- 查询结果 -->
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($result): ?>
                <div class="card result-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">备案信息</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>备案号:</strong> <?php echo htmlspecialchars($result['icp_number']); ?></p>
                                <p><strong>网站名称:</strong> <?php echo htmlspecialchars($result['site_name']); ?></p>
                                <p><strong>域名:</strong> <?php echo htmlspecialchars($result['domain']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>审核人:</strong> <?php echo htmlspecialchars($result['reviewer'] ?? '系统'); ?></p>
                                <p><strong>审核时间:</strong> <?php echo date('Y-m-d H:i', strtotime($result['reviewed_at'])); ?></p>
                                <p><strong>网站描述:</strong> <?php echo htmlspecialchars($result['description']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">© <?php echo date('Y'); ?> Yuan-ICP. 保留所有权利。</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

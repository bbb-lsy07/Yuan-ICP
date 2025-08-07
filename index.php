<?php
// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__.'/data/error.log');

// 检查是否已安装
$installFile = __DIR__.'/install/step1.php';
$configFile = __DIR__.'/config/database.php';

if (!file_exists($configFile) || filesize($configFile) < 100) {
    if (file_exists($installFile)) {
        header('Location: /install/step1.php');
        exit;
    }
    die('系统未安装且安装向导不存在，请检查安装文件');
}

require_once __DIR__.'/includes/functions.php';

try {
    // 加载系统配置
    $db = db();
    $stmt = $db->query("SELECT config_key, config_value FROM system_config");
    $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 加载最新公告
    $announcements = $db->query("
        SELECT id, title, content, created_at 
        FROM announcements 
        WHERE is_pinned = 1
        ORDER BY created_at DESC 
        LIMIT 3
    ")->fetchAll();
} catch (Exception $e) {
    die('系统初始化失败: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($config['seo_description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($config['seo_keywords'] ?? ''); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }
        .announcement-card {
            transition: transform 0.3s;
        }
        .announcement-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #6e8efb;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apply.php">申请备案</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="query.php">备案查询</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="leap.php">网站迁跃</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></h1>
            <p class="lead mb-5"><?php echo htmlspecialchars($config['seo_description'] ?? '一个开源、高度可定制化的虚拟ICP备案系统'); ?></p>
            <a href="apply.php" class="btn btn-light btn-lg px-4 me-2">立即申请</a>
            <a href="query.php" class="btn btn-outline-light btn-lg px-4">备案查询</a>
        </div>
    </section>

    <!-- 主要内容 -->
    <div class="container my-5">
        <div class="row">
            <!-- 公告区域 -->
            <div class="col-lg-8">
                <h2 class="mb-4"><i class="fas fa-bullhorn me-2"></i>最新公告</h2>
                
                <?php if (empty($announcements)): ?>
                    <div class="alert alert-info">暂无公告</div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($announcements as $ann): ?>
                        <div class="col-md-6">
                            <div class="card announcement-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($ann['title']); ?></h5>
                                    <p class="card-text text-muted small">
                                        <?php echo date('Y-m-d', strtotime($ann['created_at'])); ?>
                                    </p>
                                    <p class="card-text">
                                        <?php echo mb_substr(strip_tags($ann['content']), 0, 100); ?>...
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- 备案查询 -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">备案查询</h4>
                        <form action="query.php" method="get">
                            <div class="mb-3">
                                <label for="icp_number" class="form-label">备案号</label>
                                <input type="text" class="form-control" id="icp_number" name="icp_number" placeholder="输入备案号">
                            </div>
                            <div class="mb-3">
                                <label for="domain" class="form-label">或域名</label>
                                <input type="text" class="form-control" id="domain" name="domain" placeholder="输入域名">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">查询</button>
                        </form>
                    </div>
                </div>
                
                <!-- 统计信息 -->
                <div class="card mt-4">
                    <div class="card-body text-center">
                        <h4 class="card-title mb-3">备案统计</h4>
                        <?php
                        $stats = $db->query("
                            SELECT 
                                COUNT(*) as total,
                                SUM(status = 'approved') as approved,
                                SUM(status = 'pending') as pending
                            FROM icp_applications
                        ")->fetch();
                        ?>
                        <div class="row">
                            <div class="col-4">
                                <div class="feature-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h3><?php echo $stats['total']; ?></h3>
                                <p class="text-muted">总备案</p>
                            </div>
                            <div class="col-4">
                                <div class="feature-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3><?php echo $stats['approved']; ?></h3>
                                <p class="text-muted">已通过</p>
                            </div>
                            <div class="col-4">
                                <div class="feature-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h3><?php echo $stats['pending']; ?></h3>
                                <p class="text-muted">待审核</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?></h5>
                    <p><?php echo htmlspecialchars($config['seo_description'] ?? '一个开源、高度可定制化的虚拟ICP备案系统'); ?></p>
                </div>
                <div class="col-md-3">
                    <h5>快速链接</h5>
                    <ul class="list-unstyled">
                        <li><a href="apply.php" class="text-white">申请备案</a></li>
                        <li><a href="query.php" class="text-white">备案查询</a></li>
                        <li><a href="leap.php" class="text-white">网站迁跃</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>关于我们</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">项目介绍</a></li>
                        <li><a href="#" class="text-white">开源协议</a></li>
                        <li><a href="#" class="text-white">联系我们</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($config['site_name'] ?? 'Yuan-ICP'); ?>. 保留所有权利。</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

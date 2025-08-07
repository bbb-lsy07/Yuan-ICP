<?php
require_once __DIR__.'/includes/functions.php';

$db = db();

// 验证申请ID
$application_id = intval($_GET['application_id'] ?? 0);
if ($application_id <= 0) {
    header("Location: apply.php");
    exit;
}

// 获取申请信息
$stmt = $db->prepare("SELECT * FROM icp_applications WHERE id = ?");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    header("Location: apply.php");
    exit;
}

// 生成需要放置的HTML代码
$site_url = $db->query("SELECT config_value FROM system_config WHERE config_key = 'site_url'")->fetchColumn();
$html_code = htmlspecialchars('<a href="'.($site_url ?: 'https://icp.example.com').'/query.php?icp_number='.$application['icp_number'].'" target="_blank">备案号: '.$application['icp_number'].'</a>');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>备案结果 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        .result-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            font-family: monospace;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Yuan-ICP</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="result-container">
            <div class="text-center mb-4">
                <h2>备案申请结果</h2>
                <p class="text-muted">您的备案申请已提交成功</p>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">申请信息</h5>
                        <span class="status-badge status-<?php echo $application['status']; ?>">
                            <?php 
                            switch($application['status']) {
                                case 'pending': echo '审核中'; break;
                                case 'approved': echo '已通过'; break;
                                case 'rejected': echo '已驳回'; break;
                                default: echo $application['status'];
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>网站名称:</strong> <?php echo htmlspecialchars($application['site_name']); ?></p>
                            <p><strong>网站域名:</strong> <?php echo htmlspecialchars($application['domain']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>备案号:</strong> <?php echo htmlspecialchars($application['icp_number'] ?? '待分配'); ?></p>
                            <p><strong>申请时间:</strong> <?php echo date('Y-m-d H:i', strtotime($application['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($application['status'] === 'rejected' && !empty($application['reject_reason'])): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-circle me-2"></i>驳回原因</h5>
                <p><?php echo nl2br(htmlspecialchars($application['reject_reason'])); ?></p>
                <a href="apply.php" class="btn btn-outline-danger">重新申请</a>
            </div>
            <?php endif; ?>

            <?php if ($application['icp_number']): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">请将以下代码放置到您的网站底部</h5>
                    <div class="code-block">
                        <button class="btn btn-sm btn-outline-primary copy-btn" data-clipboard-target="#html-code">
                            <i class="far fa-copy me-1"></i>复制
                        </button>
                        <div id="html-code"><?php echo $html_code; ?></div>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted small">* 根据规定，您需要在网站底部展示备案号及链接</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-3">备案查询</h5>
                    <p>您可以通过以下链接查询备案状态：</p>
                    <a href="query.php?icp_number=<?php echo urlencode($application['icp_number']); ?>" class="btn btn-primary">
                        查询我的备案状态
                    </a>
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
    <script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
    <script>
        // 初始化复制按钮
        new ClipboardJS('.copy-btn');
        
        // 显示复制成功提示
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-1"></i>已复制';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    </script>
</body>
</html>

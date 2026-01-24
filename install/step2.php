<?php
// install/step2.php - 优化版
session_start();

if (!isset($_SESSION['install_step1_passed']) || !$_SESSION['install_step1_passed']) {
    header('Location: step1.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 安装向导 - 信息配置</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .installer-container { max-width: 800px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="card shadow-sm">
            <div class="card-header text-center bg-white py-3">
                <h2 class="mb-0">Yuan-ICP 安装向导 (2/3)</h2>
                <p class="text-muted mb-0">信息配置</p>
            </div>
            <div class="card-body p-4">
                <form method="post" action="step3.php" enctype="multipart/form-data">
                    <input type="hidden" name="db_type" value="sqlite">
                    
                    <fieldset class="mb-4">
                        <legend class="h5 mb-3"><i class="fas fa-database text-primary"></i> 数据库设置</legend>
                        <div class="mb-3">
                            <label for="db_file" class="form-label">数据库文件路径</label>
                            <input type="text" class="form-control" id="db_file" name="db_file" value="data/database.sqlite" required>
                            <div class="form-text">数据库将保存于项目根目录下的此路径。请确保 <code>/data</code> 目录可写。</div>
                        </div>
                        <div class="mb-3">
                            <label for="sqlite_backup" class="form-label">从备份恢复 (可选)</label>
                            <input type="file" id="sqlite_backup" name="sqlite_backup" class="form-control" accept=".db,.sqlite,.sqlite3">
                            <div class="form-text text-muted">如果您有旧的 <code>database.sqlite</code> 备份文件，可在此上传以恢复数据。这将跳过下方管理员账户的创建。</div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="h5 mb-3"><i class="fas fa-sitemap text-primary"></i> 站点信息</legend>
                        <div class="mb-3">
                            <label for="site_name" class="form-label">站点名称</label>
                            <input type="text" class="form-control" id="site_name" name="site_name" value="Yuan-ICP" required>
                        </div>
                        <div class="mb-3">
                            <label for="site_url" class="form-label">站点URL</label>
                            <input type="url" class="form-control" id="site_url" name="site_url" value="<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']); ?>" required>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="h5 mb-3"><i class="fas fa-user-shield text-primary"></i> 管理员账户</legend>
                        <div class="mb-3">
                            <label for="admin_user" class="form-label">管理员用户名</label>
                            <input type="text" class="form-control" id="admin_user" name="admin_user" value="admin" required>
                        </div>
                        <div class="mb-3">
                            <label for="admin_pass" class="form-label">管理员密码</label>
                            <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                        </div>
                         <div class="mb-3">
                            <label for="admin_email" class="form-label">管理员邮箱</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="admin@example.com">
                        </div>
                    </fieldset>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="step1.php" class="btn btn-secondary">&laquo; 返回上一步</a>
                        <button type="submit" class="btn btn-primary">开始安装 &raquo;</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

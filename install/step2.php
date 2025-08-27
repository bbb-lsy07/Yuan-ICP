<?php
// 安装向导 - 第二步：信息配置
session_start();

// 检查是否已完成第一步
if (!isset($_SESSION['install_step1_passed']) || !$_SESSION['install_step1_passed']) {
    header('Location: step1.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证并存储表单数据
    $_SESSION['install_config'] = [
        'db_type' => $_POST['db_type'] ?? '',
        'db_host' => $_POST['db_host'] ?? '',
        'db_port' => $_POST['db_port'] ?? '',
        'db_name' => $_POST['db_name'] ?? '',
        'db_user' => $_POST['db_user'] ?? '',
        'db_pass' => $_POST['db_pass'] ?? '',
        'db_file' => $_POST['db_file'] ?? '',
        'site_name' => $_POST['site_name'] ?? '',
        'site_url' => $_POST['site_url'] ?? '',
        'admin_user' => $_POST['admin_user'] ?? '',
        'admin_pass' => $_POST['admin_pass'] ?? '',
        'admin_email' => $_POST['admin_email'] ?? ''
    ];

    // 简单验证
    $errors = [];
    if (empty($_SESSION['install_config']['site_name'])) {
        $errors[] = '请输入站点名称';
    }
    if (empty($_SESSION['install_config']['site_url'])) {
        $errors[] = '请输入站点URL';
    }
    if (empty($_SESSION['install_config']['admin_user'])) {
        $errors[] = '请输入管理员用户名';
    }
    if (empty($_SESSION['install_config']['admin_pass'])) {
        $errors[] = '请输入管理员密码';
    }

    // 数据库验证
    switch ($_SESSION['install_config']['db_type']) {
        case 'mysql':
        case 'pgsql':
            if (empty($_SESSION['install_config']['db_host'])) {
                $errors[] = '请输入数据库主机';
            }
            if (empty($_SESSION['install_config']['db_name'])) {
                $errors[] = '请输入数据库名称';
            }
            if (empty($_SESSION['install_config']['db_user'])) {
                $errors[] = '请输入数据库用户名';
            }
            break;
        case 'sqlite':
            if (empty($_SESSION['install_config']['db_file'])) {
                $errors[] = '请输入SQLite数据库文件路径';
            }
            break;
        default:
            $errors[] = '请选择数据库类型';
    }

    // 如果没有错误，跳转到下一步
    if (empty($errors)) {
        header('Location: step3.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 安装向导 - 信息配置</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"], input[type="email"], select {
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;
        }
        .db-fields { display: none; margin-left: 20px; border-left: 3px solid #eee; padding-left: 15px; }
        .error { color: red; margin-bottom: 15px; }
        .btn-group { display: flex; justify-content: space-between; margin-top: 20px; }
        .btn { 
            padding: 10px 20px; 
            text-align: center; 
            background: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-back { background: #6c757d; }
        .btn:disabled { background: #cccccc; }
    </style>
    <script>
        function showDbFields() {
            // 隐藏所有数据库字段
            document.querySelectorAll('.db-fields').forEach(el => {
                el.style.display = 'none';
            });
            
            // 显示选中的数据库字段
            const dbType = document.getElementById('db_type').value;
            if (dbType) {
                document.getElementById(dbType + '-fields').style.display = 'block';
            }
        }
        
        // 页面加载时显示正确的数据库字段
        window.onload = function() {
            showDbFields();
        };
    </script>
</head>
<body>
    <h1>Yuan-ICP 安装向导 - 信息配置</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" action="step3.php" enctype="multipart/form-data">
        <h2>站点信息</h2>
        <div class="form-group">
            <label for="site_name">站点名称</label>
            <input type="text" id="site_name" name="site_name" 
                   value="<?php echo htmlspecialchars($_SESSION['install_config']['site_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="site_url">站点URL</label>
            <input type="text" id="site_url" name="site_url" 
                   value="<?php echo htmlspecialchars($_SESSION['install_config']['site_url'] ?? ''); ?>" required>
        </div>
        
        <h2>数据库配置</h2>
        <div class="form-group">
            <label for="db_type">数据库类型</label>
            <select id="db_type" name="db_type" onchange="showDbFields()" required>
                <option value="">-- 请选择 --</option>
                <option value="mysql" <?php echo ($_SESSION['install_config']['db_type'] ?? '') === 'mysql' ? 'selected' : ''; ?>>MySQL</option>
                <option value="pgsql" <?php echo ($_SESSION['install_config']['db_type'] ?? '') === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
                <option value="sqlite" <?php echo ($_SESSION['install_config']['db_type'] ?? '') === 'sqlite' ? 'selected' : ''; ?>>SQLite</option>
            </select>
        </div>
        
        <!-- MySQL/PostgreSQL 字段 -->
        <div id="mysql-fields" class="db-fields">
            <div class="form-group">
                <label for="db_host">数据库主机</label>
                <input type="text" id="db_host" name="db_host" 
                       value="<?php echo htmlspecialchars($_SESSION['install_config']['db_host'] ?? 'localhost'); ?>">
            </div>
            <div class="form-group">
                <label for="db_port">数据库端口</label>
                <input type="text" id="db_port" name="db_port" 
                       value="<?php echo htmlspecialchars($_SESSION['install_config']['db_port'] ?? '3306'); ?>">
            </div>
            <div class="form-group">
                <label for="db_name">数据库名称</label>
                <input type="text" id="db_name" name="db_name" 
                       value="<?php echo htmlspecialchars($_SESSION['install_config']['db_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="db_user">数据库用户名</label>
                <input type="text" id="db_user" name="db_user" 
                       value="<?php echo htmlspecialchars($_SESSION['install_config']['db_user'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="db_pass">数据库密码</label>
                <input type="password" id="db_pass" name="db_pass" 
                       value="<?php echo htmlspecialchars($_SESSION['install_config']['db_pass'] ?? ''); ?>">
            </div>
        </div>
        
        <!-- SQLite 字段 -->
        <div id="sqlite-fields" class="db-fields">
            <div class="form-group">
                <label for="db_file">数据库文件路径 (相对于项目根目录)</label>
                <input type="text" id="db_file" name="db_file"
                       value="<?php echo htmlspecialchars($_SESSION['install_config']['db_file'] ?? 'data/sqlite.db'); ?>">
            </div>
            <!-- Restore from backup section -->
            <div class="form-group" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                <label for="sqlite_backup">从备份文件恢复 (可选)</label>
                <input type="file" id="sqlite_backup" name="sqlite_backup" class="form-control" accept=".db, .sqlite, .sqlite3">
                <div class="form-text text-muted" style="font-size: 0.875em;">如果您有一个 "sqlite.db" 备份文件，可在此上传直接恢复数据。这将跳过数据库初始化和管理员创建步骤。</div>
            </div>
        </div>
        
        <h2>管理员账户</h2>
        <div class="form-group">
            <label for="admin_user">管理员用户名</label>
            <input type="text" id="admin_user" name="admin_user" 
                   value="<?php echo htmlspecialchars($_SESSION['install_config']['admin_user'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="admin_pass">管理员密码</label>
            <input type="password" id="admin_pass" name="admin_pass" required>
        </div>
        <div class="form-group">
            <label for="admin_email">管理员邮箱</label>
            <input type="email" id="admin_email" name="admin_email" 
                   value="<?php echo htmlspecialchars($_SESSION['install_config']['admin_email'] ?? ''); ?>">
        </div>
        
        <div class="btn-group">
            <a href="step1.php" class="btn btn-back">&laquo; 上一步</a>
            <button type="submit" class="btn">下一步 &raquo;</button>
        </div>
    </form>
</body>
</html>

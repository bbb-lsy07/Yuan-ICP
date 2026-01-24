<?php
// install/step3.php - 优化版
session_start();

if (!isset($_SESSION['install_step1_passed']) || !$_SESSION['install_step1_passed']) {
    header('Location: step1.php');
    exit;
}

$errors = [];
$restored_from_backup = false;
$project_root = dirname(__DIR__); // 项目根目录

try {
    // 1. 从 POST 获取配置信息
    $config = [
        'db_type' => $_POST['db_type'] ?? 'sqlite',
        'db_file' => $_POST['db_file'] ?? 'data/database.sqlite',
        'site_name' => $_POST['site_name'] ?? 'Yuan-ICP',
        'site_url' => $_POST['site_url'] ?? '',
        'admin_user' => $_POST['admin_user'] ?? '',
        'admin_pass' => $_POST['admin_pass'] ?? '',
        'admin_email' => $_POST['admin_email'] ?? ''
    ];

    // 2. 处理数据库路径
    $db_path_relative = ltrim($config['db_file'], '/');
    $db_path_absolute = $project_root . '/' . $db_path_relative;
    $db_dir = dirname($db_path_absolute);

    // 确保数据库目录存在且可写
    if (!is_dir($db_dir)) {
        if (!@mkdir($db_dir, 0755, true)) {
            throw new Exception('无法自动创建数据库目录: <code>' . htmlspecialchars($db_dir) . '</code>，请手动创建并赋予写入权限。');
        }
    }
    if (!is_writable($db_dir)) {
        throw new Exception('数据库目录 <code>' . htmlspecialchars($db_dir) . '</code> 不可写，请检查权限。');
    }

    // 3. 检查是否从 SQLite 备份恢复
    if (isset($_FILES['sqlite_backup']) && $_FILES['sqlite_backup']['error'] === UPLOAD_ERR_OK) {
        $tmp_path = $_FILES['sqlite_backup']['tmp_name'];
        // 简单验证上传的文件
        try {
            $pdo_test = new PDO('sqlite:' . $tmp_path);
            $pdo_test->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_users'");
            if (!$pdo_test) throw new Exception();
        } catch (Exception $e) {
            throw new Exception('上传的文件不是一个有效的 Yuan-ICP 数据库备份。');
        }
        $pdo_test = null;

        if (!move_uploaded_file($tmp_path, $db_path_absolute)) {
            throw new Exception('恢复数据库失败。请检查 <code>/data</code> 目录的写入权限。');
        }
        $restored_from_backup = true;
    }

    // 4. 生成数据库配置文件
    $config_dir = $project_root . '/config';
    if (!is_dir($config_dir)) @mkdir($config_dir, 0755, true);
    
    $dbConfigContent = "<?php\n// 由Yuan-ICP安装程序自动生成\nreturn [\n    'driver' => 'sqlite',\n    'database' => '" . addslashes($db_path_absolute) . "',\n];\n";
    if (file_put_contents($config_dir . '/database.php', $dbConfigContent) === false) {
        throw new Exception('无法写入数据库配置文件 <code>config/database.php</code>，请检查 <code>/config</code> 目录的写入权限。');
    }

    // 5. 如果不是从备份恢复，则初始化新数据库
    if (!$restored_from_backup) {
        if (empty($config['admin_user']) || empty($config['admin_pass'])) {
            throw new Exception("管理员用户名和密码不能为空。");
        }
        
        // 如果文件已存在，先删除，确保是全新安装
        if (file_exists($db_path_absolute)) {
            unlink($db_path_absolute);
        }

        $db = new PDO("sqlite:" . $db_path_absolute);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 执行 SQL 初始化脚本
        $sql = file_get_contents(__DIR__ . '/database.sql');
        $db->exec($sql);

        // 更新站点信息
        $stmt_config = $db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
        $stmt_config->execute(['site_name', $config['site_name']]);
        $stmt_config->execute(['site_url', $config['site_url']]);

        // 更新管理员账户
        $stmt = $db->prepare("UPDATE admin_users SET username = ?, password = ?, email = ? WHERE username = 'admin'");
        $stmt->execute([
            $config['admin_user'],
            password_hash($config['admin_pass'], PASSWORD_DEFAULT),
            $config['admin_email']
        ]);
    }

    // 标记安装成功
    $_SESSION['install_complete'] = true;
    $_SESSION['restored_from_backup'] = $restored_from_backup;
    
    // 创建安装锁文件
    file_put_contents($project_root . '/config/install.lock', date('Y-m-d H:i:s'));

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 安装向导 - 完成</title>
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
                <h2 class="mb-0">Yuan-ICP 安装向导 (3/3)</h2>
                <p class="text-muted mb-0">安装完成</p>
            </div>
            <div class="card-body p-4 text-center">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> 安装失败</h4>
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-1"><?php echo $error; // echo a raw html string here ?></p>
                        <?php endforeach; ?>
                        <hr>
                        <a href="step2.php" class="btn btn-danger">&laquo; 返回修改配置</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading"><i class="fas fa-check-circle"></i> 安装成功！</h4>
                        <?php if ($restored_from_backup): ?>
                            <p>Yuan-ICP 已成功从您的备份文件恢复安装。</p>
                            <p class="mb-0">请使用您备份文件中的管理员账户和密码登录。</p>
                        <?php else: ?>
                            <p>Yuan-ICP 已成功安装到您的服务器！</p>
                            <p class="mb-0">您的管理员用户名为：<strong><?php echo htmlspecialchars($config['admin_user']); ?></strong></p>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-warning">
                        <h4 class="alert-heading"><i class="fas fa-shield-alt"></i> 重要安全提示</h4>
                        <p class="mb-0">为了您的网站安全，请 **立即删除** 或 **重命名** 服务器上的 <code>/install</code> 目录！</p>
                    </div>
                    <div class="mt-4">
                        <a href="../" class="btn btn-secondary"><i class="fas fa-home"></i> 访问网站首页</a>
                        <a href="../admin/" class="btn btn-primary"><i class="fas fa-user-shield"></i> 登录后台</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

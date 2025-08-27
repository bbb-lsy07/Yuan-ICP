<?php
session_start();

if (!isset($_SESSION['install_step1_passed']) || !$_SESSION['install_step1_passed']) {
    header('Location: step1.php');
    exit;
}

$errors = [];
$restored_from_backup = false;
$project_root = dirname(__DIR__); // 项目根目录

try {
    // 从 POST 获取配置信息
    $config = [
        'db_type' => $_POST['db_type'] ?? 'sqlite',
        'db_file' => $_POST['db_file'] ?? 'data/sqlite.db',
        'admin_user' => $_POST['admin_user'] ?? '',
        'admin_pass' => $_POST['admin_pass'] ?? '',
        'admin_email' => $_POST['admin_email'] ?? ''
    ];

    $db_path_absolute = $project_root . '/' . ltrim($config['db_file'], '/');
    $db_dir = dirname($db_path_absolute);

    // 检查是否从 SQLite 备份恢复
    if ($config['db_type'] === 'sqlite' && isset($_FILES['sqlite_backup']) && $_FILES['sqlite_backup']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($db_dir)) {
            if (!mkdir($db_dir, 0755, true)) {
                throw new Exception('无法创建数据目录: ' . htmlspecialchars($db_dir));
            }
        }

        $tmp_path = $_FILES['sqlite_backup']['tmp_name'];
        try {
            $pdo_test = new PDO('sqlite:' . $tmp_path);
            $pdo_test->query("SELECT name FROM sqlite_master WHERE type='table' LIMIT 1");
        } catch (Exception $e) {
            throw new Exception('上传的文件似乎不是一个有效的 SQLite 数据库。');
        }
        $pdo_test = null;

        if (!move_uploaded_file($tmp_path, $db_path_absolute)) {
            throw new Exception('移动上传的数据库备份文件失败。请检查 `data` 目录的写入权限。');
        }

        $restored_from_backup = true;
    }

    // --- 生成数据库配置文件 ---
    $dbConfigContent = "<?php\nreturn [\n    'driver' => 'sqlite',\n    'database' => '" . addslashes($db_path_absolute) . "',\n];\n";

    // 确保 config 目录存在
    if (!is_dir($project_root . '/config')) {
        mkdir($project_root . '/config', 0755, true);
    }

    if (!file_put_contents($project_root . '/config/database.php', $dbConfigContent)) {
        throw new Exception('无法写入数据库配置文件 `config/database.php`。请检查权限。');
    }

    // --- 如果不是从备份恢复，则初始化数据库和管理员 ---
    if (!$restored_from_backup) {
        if (empty($config['admin_user']) || empty($config['admin_pass'])) {
            throw new Exception("管理员用户名和密码不能为空。");
        }

        $db = new PDO("sqlite:" . $db_path_absolute);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = file_get_contents(__DIR__ . '/database.sql');
        $db->exec($sql);

        $stmt = $db->prepare("UPDATE admin_users SET username = ?, password = ?, email = ? WHERE username = 'admin'");
        $stmt->execute([
            $config['admin_user'],
            password_hash($config['admin_pass'], PASSWORD_DEFAULT),
            $config['admin_email']
        ]);
    }

    $_SESSION[$restored_from_backup ? 'install_complete_from_backup' : 'install_complete'] = true;

} catch (Exception $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 安装向导 - 完成安装</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; text-align: center; }
        .success { color: green; margin: 20px 0; padding: 15px; background: #f0fff0; border: 1px solid #dff0d8; }
        .error { color: red; margin: 20px 0; padding: 15px; background: #fff0f0; border: 1px solid #f0d8d8; }
        .warning { color: #8a6d3b; margin: 20px 0; padding: 15px; background: #fcf8e3; border: 1px solid #faebcc; }
        .btn { display: block; width: 200px; padding: 10px; margin: 20px auto; text-align: center; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Yuan-ICP 安装向导 - 完成安装</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <h3>安装过程中出现错误：</h3>
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
            <p>请检查错误信息并<a href="step2.php">返回上一步</a>修改配置。</p>
        </div>
    <?php elseif (isset($_SESSION['install_complete_from_backup'])): ?>
        <div class="success">
            <h3>从备份恢复安装成功！</h3>
            <p>Yuan-ICP 已成功使用您的备份文件完成安装。</p>
            <p>您现在可以 <a href="../admin/login.php">登录后台</a>，请使用您备份文件中的管理员账户信息登录。</p>
        </div>
        <div class="warning">
            <h3>安全提示</h3>
            <p>为了系统安全，请立即删除或重命名 install 目录。</p>
        </div>
        <a href="../" class="btn">访问网站首页</a>
    <?php elseif (isset($_SESSION['install_complete'])): ?>
        <div class="success">
            <h3>安装成功！</h3>
            <p>Yuan-ICP 已成功安装到您的服务器。</p>
            <p>您现在可以<a href="../admin/login.php">登录后台</a>开始使用系统。</p>
        </div>
        <div class="warning">
            <h3>安全提示</h3>
            <p>为了系统安全，请立即删除或重命名 install 目录。</p>
        </div>
        <a href="../" class="btn">访问网站首页</a>
    <?php endif; ?>
</body>
</html>

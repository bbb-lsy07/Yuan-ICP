<?php
// 安装向导 - 第三步：完成安装
session_start();

// 检查是否已完成前两步
if (!isset($_SESSION['install_step1_passed']) || !$_SESSION['install_step1_passed'] || 
    !isset($_SESSION['install_config'])) {
    header('Location: step1.php');
    exit;
}

// 初始化错误数组
$errors = [];

try {
    // 生成数据库配置文件
    $dbConfig = "<?php\n";
    $dbConfig .= "return [\n";
    $dbConfig .= "    'driver' => '".$_SESSION['install_config']['db_type']."',\n";
    
    switch ($_SESSION['install_config']['db_type']) {
        case 'mysql':
        case 'pgsql':
            $dbConfig .= "    'host' => '".$_SESSION['install_config']['db_host']."',\n";
            $dbConfig .= "    'port' => '".($_SESSION['install_config']['db_port'] ?? '3306')."',\n";
            $dbConfig .= "    'database' => '".$_SESSION['install_config']['db_name']."',\n";
            $dbConfig .= "    'username' => '".$_SESSION['install_config']['db_user']."',\n";
            $dbConfig .= "    'password' => '".$_SESSION['install_config']['db_pass']."',\n";
            break;
        case 'sqlite':
            $dbConfig .= "    'database' => __DIR__.'/../../".$_SESSION['install_config']['db_file']."',\n";
            break;
    }
    
    $dbConfig .= "    'charset' => 'utf8mb4',\n";
    $dbConfig .= "    'collation' => 'utf8mb4_unicode_ci',\n";
    $dbConfig .= "    'prefix' => '',\n";
    $dbConfig .= "];\n";
    
    // 写入数据库配置文件
    if (!file_put_contents(dirname(__DIR__).'/config/database.php', $dbConfig)) {
        throw new Exception('无法写入数据库配置文件');
    }
    
    // 生成应用配置文件
    $appConfig = "<?php\n";
    $appConfig .= "return [\n";
    $appConfig .= "    'site_name' => '".addslashes($_SESSION['install_config']['site_name'])."',\n";
    $appConfig .= "    'site_url' => '".rtrim($_SESSION['install_config']['site_url'], '/')."',\n";
    $appConfig .= "    'timezone' => 'Asia/Shanghai',\n";
    $appConfig .= "    'debug' => false,\n";
    $appConfig .= "];\n";
    
    if (!file_put_contents(dirname(__DIR__).'/config/app.php', $appConfig)) {
        throw new Exception('无法写入应用配置文件');
    }
    
    // 初始化数据库
    $db = null;
    switch ($_SESSION['install_config']['db_type']) {
        case 'mysql':
            $dsn = "mysql:host={$_SESSION['install_config']['db_host']};port={$_SESSION['install_config']['db_port']};charset=utf8mb4";
            $db = new PDO($dsn, $_SESSION['install_config']['db_user'], $_SESSION['install_config']['db_pass']);
            $db->exec("CREATE DATABASE IF NOT EXISTS `{$_SESSION['install_config']['db_name']}`");
            $db->exec("USE `{$_SESSION['install_config']['db_name']}`");
            break;
        case 'pgsql':
            $dsn = "pgsql:host={$_SESSION['install_config']['db_host']};port={$_SESSION['install_config']['db_port']}";
            $db = new PDO($dsn, $_SESSION['install_config']['db_user'], $_SESSION['install_config']['db_pass']);
            $db->exec("CREATE DATABASE \"{$_SESSION['install_config']['db_name']}\"");
            $db = new PDO("pgsql:host={$_SESSION['install_config']['db_host']};port={$_SESSION['install_config']['db_port']};dbname={$_SESSION['install_config']['db_name']}", 
                         $_SESSION['install_config']['db_user'], $_SESSION['install_config']['db_pass']);
            break;
        case 'sqlite':
            $dbFile = dirname(__DIR__).'/'.$_SESSION['install_config']['db_file'];
            $db = new PDO("sqlite:$dbFile");
            break;
    }
    
    // 设置PDO属性
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建表结构
    $sql = file_get_contents(__DIR__.'/database.sql');
    $db->exec($sql);
    
    // 创建管理员账户
    $stmt = $db->prepare("INSERT INTO admin_users (username, password, email, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['install_config']['admin_user'],
        password_hash($_SESSION['install_config']['admin_pass'], PASSWORD_DEFAULT),
        $_SESSION['install_config']['admin_email'],
        date('Y-m-d H:i:s')
    ]);
    
    // 标记安装完成
    $_SESSION['install_complete'] = true;
    
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
        .btn { 
            display: block; 
            width: 200px; 
            padding: 10px; 
            margin: 20px auto; 
            text-align: center; 
            background: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px;
        }
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
    <?php elseif (isset($_SESSION['install_complete']) && $_SESSION['install_complete']): ?>
        <div class="success">
            <h3>安装成功！</h3>
            <p>Yuan-ICP 已成功安装到您的服务器。</p>
            <p>您现在可以<a href="../admin/login.php">登录后台</a>开始使用系统。</p>
        </div>
        
        <div class="warning">
            <h3>安全提示</h3>
            <p>为了系统安全，请立即删除或重命名 install 目录。</p>
            <p>您可以使用以下命令删除安装目录：</p>
            <pre>rm -rf <?php echo htmlspecialchars(dirname(__DIR__).'/install'); ?></pre>
        </div>
        
        <a href="../" class="btn">访问网站首页</a>
    <?php endif; ?>
</body>
</html>

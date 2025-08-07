<?php
// 安装向导 - 第一步：环境检查
session_start();

// 检查函数定义
function check_php_version() {
    return version_compare(PHP_VERSION, '7.4.0', '>=');
}

function check_pdo_mysql() {
    return extension_loaded('pdo_mysql');
}

function check_pdo_pgsql() {
    return extension_loaded('pdo_pgsql'); 
}

function check_pdo_sqlite() {
    return extension_loaded('pdo_sqlite');
}

function check_any_pdo() {
    return check_pdo_mysql() || check_pdo_pgsql() || check_pdo_sqlite();
}

function check_gd() {
    return extension_loaded('gd');
}

function check_curl() {
    return extension_loaded('curl');
}

function check_config_writable() {
    return is_writable(dirname(__DIR__).'/config');
}

function check_uploads_writable() {
    return is_writable(dirname(__DIR__).'/uploads');
}

// 检查项数组
$checks = [
    'php_version' => [
        'name' => 'PHP版本 >= 7.4',
        'check' => 'check_php_version'
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL支持',
        'check' => 'check_pdo_mysql'
    ],
    'pdo_pgsql' => [
        'name' => 'PDO PostgreSQL支持',
        'check' => 'check_pdo_pgsql'
    ],
    'pdo_sqlite' => [
        'name' => 'PDO SQLite支持',
        'check' => 'check_pdo_sqlite'
    ],
    'gd' => [
        'name' => 'GD库',
        'check' => 'check_gd'
    ],
    'curl' => [
        'name' => 'cURL扩展',
        'check' => 'check_curl'
    ],
    'config_writable' => [
        'name' => 'config目录可写',
        'check' => 'check_config_writable'
    ],
    'uploads_writable' => [
        'name' => 'uploads目录可写',
        'check' => 'check_uploads_writable'
    ]
];

// 执行所有检查
$all_passed = true;
$pdo_available = check_any_pdo();

foreach ($checks as &$check) {
    // 获取实际检查结果
    $actual_passed = call_user_func($check['check']);
    
    // 特殊处理PDO检查 - 显示实际状态但检查功能可用性
    if (in_array($check['name'], ['PDO MySQL支持', 'PDO PostgreSQL支持', 'PDO SQLite支持'])) {
        $check['passed'] = $actual_passed; // 显示实际状态
        if (!$pdo_available) { // 但如果没有可用的PDO驱动则阻止继续
            $all_passed = false;
        }
    } else {
        $check['passed'] = $actual_passed;
        if (!$actual_passed) {
            $all_passed = false;
        }
    }
}
unset($check);

// 存储检查结果到session
$_SESSION['install_checks'] = $checks;
$_SESSION['install_step1_passed'] = $all_passed;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 安装向导 - 环境检查</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .passed { color: green; }
        .failed { color: red; }
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
        .btn:disabled { background: #cccccc; }
    </style>
</head>
<body>
    <h1>Yuan-ICP 安装向导 - 环境检查</h1>
    
    <table>
        <thead>
            <tr>
                <th>检查项</th>
                <th>状态</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checks as $check): ?>
            <tr>
                <td><?php echo htmlspecialchars($check['name']); ?></td>
                <td class="<?php echo $check['passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo $check['passed'] ? '✓ 通过' : '✗ 失败'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($all_passed): ?>
        <a href="step2.php" class="btn">下一步 &raquo;</a>
    <?php else: ?>
        <button class="btn" disabled>请解决所有问题后再继续</button>
    <?php endif; ?>
</body>
</html>

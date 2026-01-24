<?php
// install/step1.php - 优化版
session_start();

// 检查是否已安装（防止重复安装）
if (file_exists(__DIR__ . '/../config/install.lock')) {
    die('<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>系统已安装</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="alert alert-warning" role="alert">
                        <h4 class="alert-heading">系统已安装</h4>
                        <p>系统已经安装完成。如需重新安装，请手动删除 <code>config/install.lock</code> 文件。</p>
                        <hr>
                        <p class="mb-0"><a href="../" class="btn btn-primary">返回首页</a></p>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>');
}

// --- 检查函数定义 ---
function check_php_version() {
    return version_compare(PHP_VERSION, '7.4.0', '>=');
}

function check_pdo_sqlite() {
    return extension_loaded('pdo_sqlite');
}

function check_gd() {
    return extension_loaded('gd');
}

function check_curl() {
    return extension_loaded('curl');
}

function check_data_writable() {
    $path = dirname(__DIR__) . '/data';
    if (!is_dir($path)) {
        // 尝试自动创建目录
        @mkdir($path, 0755, true);
    }
    return is_writable($path);
}

function check_uploads_writable() {
    $path = dirname(__DIR__) . '/uploads';
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    return is_writable($path);
}


// --- 检查项数组 (精简后) ---
$checks = [
    'php_version' => [
        'name' => 'PHP 版本 >= 7.4',
        'check' => 'check_php_version',
        'success' => '您的 PHP 版本符合要求。',
        'fail' => '您的 PHP 版本过低，请升级到 PHP 7.4 或更高版本。'
    ],
    'pdo_sqlite' => [
        'name' => 'PDO SQLite 支持',
        'check' => 'check_pdo_sqlite',
        'success' => '您的 PHP 已开启 PDO SQLite 扩展，可以支持本系统。',
        'fail' => '系统核心依赖 PDO SQLite 扩展，请在您的 php.ini 文件中启用 extension=pdo_sqlite。'
    ],
    'gd' => [
        'name' => 'GD 库支持',
        'check' => 'check_gd',
        'success' => 'GD 库已启用，支持图片处理功能。',
        'fail' => '缺少 GD 库，未来部分插件（如验证码）可能无法正常工作。请在 php.ini 中启用 extension=gd。'
    ],
    'curl' => [
        'name' => 'cURL 扩展',
        'check' => 'check_curl',
        'success' => 'cURL 扩展已启用，支持远程请求功能。',
        'fail' => '缺少 cURL 扩展，部分插件（如远程更新检查）可能无法工作。请在 php.ini 中启用 extension=curl。'
    ],
    'data_writable' => [
        'name' => '/data 目录可写',
        'check' => 'check_data_writable',
        'success' => '数据库目录拥有写入权限，可以创建和读写数据库文件。',
        'fail' => '数据库目录 /data 不可写！请设置该目录及其父目录的写入权限。例如，在Linux服务器上执行：<code>chmod -R 755 data</code>'
    ],
    'uploads_writable' => [
        'name' => '/uploads 目录可写',
        'check' => 'check_uploads_writable',
        'success' => '上传目录拥有写入权限，支持文件上传功能。',
        'fail' => '上传目录 /uploads 不可写！请设置该目录及其父目录的写入权限。例如，在Linux服务器上执行：<code>chmod -R 755 uploads</code>'
    ]
];

// 执行检查并计算最终结果
$all_passed = true;
foreach ($checks as &$item) {
    $item['passed'] = call_user_func($item['check']);
    if (!$item['passed']) {
        $all_passed = false;
    }
}
unset($item);

$_SESSION['install_step1_passed'] = $all_passed;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 安装向导 - 环境检查</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .installer-container { max-width: 800px; margin: 50px auto; }
        .list-group-item { display: flex; justify-content: space-between; align-items: center; }
        .status-icon { font-size: 1.2rem; }
        .text-success { color: #198754 !important; }
        .text-danger { color: #dc3545 !important; }
        .help-text { font-size: 0.9em; color: #6c757d; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="card shadow-sm">
            <div class="card-header text-center bg-white py-3">
                <h2 class="mb-0">Yuan-ICP 安装向导 (1/3)</h2>
                <p class="text-muted mb-0">环境检查</p>
            </div>
            <div class="card-body p-4">
                <ul class="list-group">
                    <?php foreach ($checks as $check): ?>
                        <li class="list-group-item">
                            <div>
                                <strong><?php echo $check['name']; ?></strong>
                                <div class="help-text">
                                    <?php echo $check['passed'] ? $check['success'] : $check['fail']; ?>
                                </div>
                            </div>
                            <?php if ($check['passed']): ?>
                                <span class="status-icon text-success"><i class="fas fa-check-circle"></i></span>
                            <?php else: ?>
                                <span class="status-icon text-danger"><i class="fas fa-times-circle"></i></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="d-grid mt-4">
                    <?php if ($all_passed): ?>
                        <a href="step2.php" class="btn btn-primary btn-lg">一切就绪，下一步 &raquo;</a>
                    <?php else: ?>
                        <button class="btn btn-danger btn-lg" disabled>请解决所有失败项后再继续</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
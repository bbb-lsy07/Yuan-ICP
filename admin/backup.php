<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

require_login();

$db = db();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
$is_sqlite = ($driver === 'sqlite');

$message = '';
$error = '';
$db_path = '';

if ($is_sqlite) {
    try {
        $config = require __DIR__.'/../config/database.php';
        if (isset($config['database'])) {
            $db_path = $config['database'];
        }
    } catch (Exception $e) {
        $error = "无法加载数据库配置文件。";
    }
}

// Handle Export Action
if (isset($_GET['action']) && $_GET['action'] === 'export' && $is_sqlite) {
    if ($db_path && file_exists($db_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="sqlite.db"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($db_path));
        flush();
        readfile($db_path);
        exit;
    } else {
        header('Location: backup.php?error=' . urlencode('数据库文件在路径 ' . htmlspecialchars($db_path) . ' 未找到！'));
        exit;
    }
}

// Handle Import (Restore) Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_sqlite) {
    if (isset($_FILES['sqlite_restore_file']) && $_FILES['sqlite_restore_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = $_FILES['sqlite_restore_file']['tmp_name'];
        try {
            $pdo_check = new PDO('sqlite:' . $uploaded_file);
            $pdo_check->query("SELECT name FROM sqlite_master WHERE type='table' LIMIT 1");
        } catch (Exception $e) {
            $error = "上传的文件不是一个有效的SQLite数据库。";
            $pdo_check = null;
        }

        if ($pdo_check) {
            if (!move_uploaded_file($uploaded_file, $db_path)) {
                $error = "恢复数据库失败。无法将上传的文件移动到目标位置。";
                if (ini_get('open_basedir')) {
                    $error .= " 您的服务器开启了 open_basedir 限制，这很可能是失败的原因。请联系您的服务器管理员，修改PHP配置中的 `upload_tmp_dir` 指向一个在 `open_basedir` 允许路径下的目录（例如: " . dirname(__DIR__) . "/tmp）。";
                } else {
                    $error .= " 请检查 `data` 目录及其父目录的写入权限。";
                }
            } else {
                $message = "数据库已成功从备份恢复！页面将在3秒后刷新以应用更改。";
                header("Refresh:3");
            }
        }
    } else {
        $error = "文件上传失败或没有选择文件。错误代码: " . ($_FILES['sqlite_restore_file']['error'] ?? 'N/A');
    }
}

if (isset($_GET['error'])) {
    $error = htmlspecialchars(urldecode($_GET['error']));
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库备份与恢复 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 main-content">
                <h2 class="mb-4">数据库备份与恢复</h2>
                
                <?php if (!$is_sqlite): ?>
                    <div class="alert alert-warning">
                        <h4>功能不可用</h4>
                        <p>此功能仅在您使用 <strong>SQLite</strong> 数据库时可用。您当前的数据库类型是 <strong><?php echo htmlspecialchars($driver); ?></strong>。</p>
                    </div>
                <?php else: ?>
                    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header"><h5 class="mb-0">导出数据库</h5></div>
                        <div class="card-body">
                            <p class="card-text">点击下面的按钮下载当前数据库的完整备份文件 (<code>sqlite.db</code>)。请妥善保管此文件。</p>
                            <a href="backup.php?action=export" class="btn btn-primary"><i class="fas fa-download me-2"></i>导出数据库备份</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">从备份文件恢复</h5></div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> 警告！</h4>
                                <p>此操作将使用您上传的文件<strong>完全覆盖</strong>当前数据库。所有现有数据都将丢失且无法恢复。在继续之前，请务必先导出现有数据库作为备份。</p>
                            </div>
                            <form method="post" enctype="multipart/form-data" onsubmit="return confirm('您确定要用上传的文件覆盖当前数据库吗？此操作无法撤销！');">
                                <div class="mb-3">
                                    <label for="sqlite_restore_file" class="form-label">选择 <code>sqlite.db</code> 备份文件</label>
                                    <input class="form-control" type="file" id="sqlite_restore_file" name="sqlite_restore_file" accept=".db,.sqlite,.sqlite3" required>
                                </div>
                                <button type="submit" class="btn btn-danger"><i class="fas fa-upload me-2"></i>导入并恢复</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
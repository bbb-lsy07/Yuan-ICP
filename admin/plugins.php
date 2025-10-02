<?php
require_once __DIR__.'/../includes/bootstrap.php';

check_admin_auth();

$message = '';
$error = '';

// --- 辅助函数：递归删除目录 ---
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object))
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

// --- 统一处理POST请求，并在操作后重定向 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 确保会话能被写入
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_write_close();
        session_start();
    }
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = '无效的请求，请刷新重试。';
    } else {
        $action = $_POST['action'] ?? '';
        $identifier = $_POST['identifier'] ?? '';

        try {
            if (empty($identifier) && $action !== 'upload') {
                throw new Exception('无效的插件标识符');
            }

            switch ($action) {
                case 'enable':
                    PluginManager::activate($identifier);
                    $_SESSION['flash_message'] = '插件已成功启用。';
                    break;
                case 'disable':
                    PluginManager::deactivate($identifier);
                    $_SESSION['flash_message'] = '插件已成功禁用。';
                    break;
                case 'delete':
                    PluginManager::uninstall($identifier);
                    $_SESSION['flash_message'] = '插件已成功卸载并删除。';
                    break;
                case 'upload':
                    if (isset($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__.'/../uploads/plugins/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                        $zipFile = $uploadDir . uniqid() . '_' . basename($_FILES['plugin_zip']['name']);
                        
                        if (move_uploaded_file($_FILES['plugin_zip']['tmp_name'], $zipFile)) {
                            $zip = new ZipArchive;
                            if ($zip->open($zipFile) === TRUE) {
                                $tempDir = $uploadDir . 'tmp_' . uniqid();
                                $zip->extractTo($tempDir);
                                $zip->close();
                                unlink($zipFile);

                                $files = scandir($tempDir);
                                $subDirs = array_filter($files, fn($f) => $f !== '.' && $f !== '..' && is_dir($tempDir . '/' . $f));
                                $sourcePath = (count($subDirs) === 1) ? $tempDir . '/' . reset($subDirs) : $tempDir;

                                $mainFilePath = $sourcePath . '/plugin.php';
                                if (!file_exists($mainFilePath)) {
                                    rrmdir($tempDir);
                                    throw new Exception('安装失败：压缩包内未找到有效的 plugin.php 文件。');
                                }
                                
                                $content = file_get_contents($mainFilePath);
                                if (!preg_match("/'identifier'\s*=>\s*'([^']*)'/", $content, $matches)) {
                                    rrmdir($tempDir);
                                    throw new Exception('安装失败：无法从 plugin.php 文件中解析出 identifier。');
                                }
                                $real_identifier = $matches[1];
                                $finalDir = __DIR__.'/../plugins/' . $real_identifier;

                                if (is_dir($finalDir)) {
                                    PluginManager::deactivate($real_identifier);
                                    rrmdir($finalDir);
                                    $_SESSION['flash_message'] = "插件 '{$real_identifier}' 已成功更新。";
                                } else {
                                    $_SESSION['flash_message'] = "插件 '{$real_identifier}' 已成功安装。";
                                }

                                rename($sourcePath, $finalDir);
                                if ($sourcePath !== $tempDir && is_dir($tempDir)) rrmdir($tempDir);

                                PluginManager::installFromIdentifier($real_identifier);

                            } else {
                                if (file_exists($zipFile)) unlink($zipFile);
                                throw new Exception('无法打开上传的ZIP文件。');
                            }
                        } else {
                            throw new Exception('文件上传失败，请检查 uploads 目录权限。');
                        }
                    } else {
                        throw new Exception('没有文件被上传或上传出错。');
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    header("Location: plugins.php");
    exit;
}

// --- 在页面顶部处理消息显示 ---
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// --- 插件发现与同步机制 ---
try {
    $db = db();
    $plugins_dir = __DIR__ . '/../plugins/';
    $installed_identifiers = array_column($db->query("SELECT identifier FROM plugins")->fetchAll(), 'identifier');
    $filesystem_plugins = array_diff(scandir($plugins_dir), ['.', '..', 'index.php']);

    // 检查文件系统中有，但数据库中没有的插件 (自动注册)
    foreach ($filesystem_plugins as $plugin_identifier) {
        if (is_dir($plugins_dir . $plugin_identifier) && !in_array($plugin_identifier, $installed_identifiers)) {
            PluginManager::installFromIdentifier($plugin_identifier);
        }
    }
    // 检查数据库中有，但文件系统中没有的插件 (自动移除)
    $missing_plugins = array_diff($installed_identifiers, $filesystem_plugins);
    if (!empty($missing_plugins)) {
        $placeholders = implode(',', array_fill(0, count($missing_plugins), '?'));
        $stmt = $db->prepare("DELETE FROM plugins WHERE identifier IN ($placeholders)");
        $stmt->execute($missing_plugins);
    }
} catch (Exception $e) {
    $error .= " 插件同步失败: " . $e->getMessage();
}

$plugins = db()->query("SELECT * FROM plugins ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插件管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- 主内容区 -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">插件管理</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-1"></i>上传插件
                        </button>
                    </div>
                </div>
                
                <!-- 子导航 -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="plugins.php">
                            <i class="fas fa-list me-1"></i>插件列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="debug_plugins.php">
                            <i class="fas fa-bug me-1"></i>插件调试
                        </a>
                    </li>
                </ul>
                
                <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($plugins as $plugin): ?>
                    <div class="col">
                        <div class="card plugin-card <?php echo $plugin['is_active'] == 0 ? 'plugin-disabled' : ''; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?php echo htmlspecialchars($plugin['name']); ?></h5>
                                    <span class="badge bg-<?php echo $plugin['is_active'] == 1 ? 'success' : 'secondary'; ?>">
                                        <?php echo $plugin['is_active'] == 1 ? '已启用' : '已禁用'; ?>
                                    </span>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">版本: <?php echo htmlspecialchars($plugin['version']); ?></h6>
                                <p class="card-text"><?php echo htmlspecialchars($plugin['description']); ?></p>
                                
                                <div class="btn-group w-100">
                                    <?php if ($plugin['is_active'] == 1): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="disable">
                                        <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-toggle-off me-1"></i>禁用
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="enable">
                                        <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-toggle-on me-1"></i>启用
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <!-- 只有已禁用的插件才能删除 -->
                                    <?php if ($plugin['is_active'] == 0): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('警告：这将彻底删除插件的数据和文件，操作不可逆，确定要删除吗？')">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash me-1"></i>删除
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 上传插件模态框 -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">上传插件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pluginZip" class="form-label">选择插件ZIP文件</label>
                            <input class="form-control" type="file" id="pluginZip" name="plugin_zip" accept=".zip" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">上传并安装</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

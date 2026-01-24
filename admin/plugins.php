<?php
require_once __DIR__.'/../includes/bootstrap.php';

check_admin_auth();

// 检查插件管理权限
if (!has_permission('system.plugins')) {
    handle_error('您没有权限管理插件', true, 403);
}

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
                                // 安全检查：Zip Slip 漏洞防护
                                for ($i = 0; $i < $zip->numFiles; $i++) {
                                    $filename = $zip->getNameIndex($i);
                                    // 检查文件名是否包含目录遍历字符
                                    if (strpos($filename, '../') !== false || 
                                        strpos($filename, '..\\') !== false || 
                                        strpos($filename, '/') === 0 ||
                                        strpos($filename, '\\') === 0) {
                                        $zip->close();
                                        unlink($zipFile);
                                        throw new Exception('安全警告：Zip 文件包含非法路径，已终止操作。');
                                    }
                                }
                                
                                // 安全检查通过后，解压到临时目录
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

                                $pluginInfo = include $mainFilePath;
                                if (!is_array($pluginInfo) || !isset($pluginInfo['identifier'])) {
                                    rrmdir($tempDir);
                                    throw new Exception('安装失败：plugin.php 未返回有效的插件信息。');
                                }

                                $targetDir = __DIR__.'/../plugins/' . $pluginInfo['identifier'];
                                if (is_dir($targetDir)) {
                                    rrmdir($tempDir);
                                    throw new Exception('安装失败：同名插件已存在。');
                                }

                                rename($sourcePath, $targetDir);
                                rrmdir($tempDir);
                                
                                $_SESSION['flash_message'] = '插件安装成功！';
                            } else {
                                throw new Exception('无法打开 Zip 文件。');
                            }
                        } else {
                            throw new Exception('文件上传失败。');
                        }
                    } else {
                         throw new Exception('请选择要上传的文件。');
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    
    // PRG 模式：重定向以防止表单重复提交
    header('Location: plugins.php');
    exit;
}

// 获取 Flash 消息
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// 获取插件列表
$plugins = PluginManager::getAllPlugins();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>扩展管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .plugin-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .plugin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        .plugin-card.active {
            border-top: 4px solid #1cc88a;
        }
        .plugin-card.inactive {
            border-top: 4px solid #858796;
        }
        .plugin-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 24px;
            margin-right: 15px;
        }
        .bg-icon-active {
            background-color: rgba(28, 200, 138, 0.1);
            color: #1cc88a;
        }
        .bg-icon-inactive {
            background-color: rgba(133, 135, 150, 0.1);
            color: #858796;
        }
        .plugin-meta {
            font-size: 0.85rem;
            color: #858796;
        }
        .card-actions {
            border-top: 1px solid #e3e6f0;
            padding-top: 15px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">扩展管理</h2>
                    <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload fa-sm text-white-50 me-1"></i> 上传插件
                    </button>
                </div>

                <!-- 选项卡导航 -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="plugins.php">
                            <i class="fas fa-puzzle-piece me-1"></i>已安装插件
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="debug_plugins.php">
                            <i class="fas fa-bug me-1"></i>插件调试
                        </a>
                    </li>
                </ul>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php if (empty($plugins)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <img src="assets/img/undraw_empty.svg" alt="Empty" style="max-width: 200px; opacity: 0.5;" class="mb-4">
                                <p class="text-gray-500 mb-0">暂无已安装的插件</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($plugins as $plugin): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card plugin-card h-100 <?php echo $plugin['is_active'] ? 'active' : 'inactive'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="plugin-icon <?php echo $plugin['is_active'] ? 'bg-icon-active' : 'bg-icon-inactive'; ?>">
                                                <i class="fas fa-puzzle-piece"></i>
                                            </div>
                                            <div>
                                                <h5 class="card-title font-weight-bold mb-1 text-gray-800">
                                                    <?php echo htmlspecialchars($plugin['name']); ?>
                                                </h5>
                                                <div class="plugin-meta">
                                                    <span class="me-2"><i class="fas fa-code-branch me-1"></i>v<?php echo htmlspecialchars($plugin['version']); ?></span>
                                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($plugin['author']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="card-text text-gray-600 small mb-0" style="height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                            <?php echo htmlspecialchars($plugin['description']); ?>
                                        </p>
                                        
                                        <div class="card-actions">
                                            <?php if ($plugin['is_active']): ?>
                                                <!-- 如果插件有管理页面，显示管理按钮 -->
                                                <div>
                                                    <a href="plugin_proxy.php?plugin=<?php echo htmlspecialchars($plugin['identifier']); ?>" class="btn btn-info btn-sm me-2">
                                                        <i class="fas fa-cog"></i> 管理
                                                    </a>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <input type="hidden" name="action" value="disable">
                                                        <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                                        <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                                                            <i class="fas fa-toggle-on me-1"></i> 启用中
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <input type="hidden" name="action" value="enable">
                                                    <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm font-weight-bold">
                                                        <i class="fas fa-toggle-off me-1"></i> 已禁用
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo htmlspecialchars($plugin['identifier']); ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">确认卸载</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            确定要卸载并删除插件 <strong><?php echo htmlspecialchars($plugin['name']); ?></strong> 吗？此操作不可恢复。
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                            <form method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($plugin['identifier']); ?>">
                                                <button type="submit" class="btn btn-danger">确认卸载</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">上传插件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="upload">
                        <div class="mb-3">
                            <label for="plugin_zip" class="form-label">选择插件压缩包 (.zip)</label>
                            <input type="file" class="form-control" id="plugin_zip" name="plugin_zip" accept=".zip" required>
                            <div class="form-text">请确保压缩包根目录包含 plugin.php 文件。</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">开始上传</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/toast.js"></script>
</body>
</html>
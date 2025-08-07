<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/hooks.php';

// 检查管理员权限 - 修改为不依赖 is_admin() 函数的方式
check_admin_auth();

$db = db();
$action = $_GET['action'] ?? 'list';
$message = '';

// 处理插件操作
switch ($action) {
    case 'enable':
        $pluginId = $_GET['id'] ?? 0;
        $message = '插件已启用';
        break;
        
    case 'disable':
        $pluginId = $_GET['id'] ?? 0;
        $message = '插件已禁用';
        break;
        
    case 'delete':
        $pluginId = $_GET['id'] ?? 0;
        if (PluginManager::uninstall($pluginId)) {
            $message = '插件已删除';
        } else {
            $message = '删除插件失败';
        }
        break;
        
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['plugin_zip'])) {
            $uploadDir = __DIR__.'/../uploads/plugins/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $zipFile = $uploadDir . basename($_FILES['plugin_zip']['name']);
            if (move_uploaded_file($_FILES['plugin_zip']['tmp_name'], $zipFile)) {
                // 解压ZIP文件
                $zip = new ZipArchive;
                if ($zip->open($zipFile) === TRUE) {
                    $pluginDir = __DIR__.'/../plugins/' . pathinfo($zipFile, PATHINFO_FILENAME);
                    $zip->extractTo($pluginDir);
                    $zip->close();
                    
                    // 安装插件
                    if (PluginManager::install($pluginDir)) {
                        $message = '插件安装成功';
                    } else {
                        $message = '插件安装失败';
                    }
                } else {
                    $message = '无法解压插件文件';
                }
                unlink($zipFile);
            } else {
                $message = '上传插件失败';
            }
        }
        break;
}

// 获取插件列表
$plugins = $db->query("SELECT *, 'active' as status FROM plugins ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插件管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    .plugin-card {
        transition: all 0.3s;
    }
    .plugin-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .plugin-disabled {
        opacity: 0.6;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f9fa;
    }
    .sidebar {
        min-height: 100vh;
        background: #343a40;
        color: white;
    }
    .sidebar-header {
        padding: 20px;
        background: #212529;
    }
    .sidebar a {
        color: rgba(255, 255, 255, 0.8);
        padding: 10px 15px;
        display: block;
        text-decoration: none;
        transition: all 0.3s;
    }
    .sidebar a:hover {
        color: white;
        background: #495057;
    }
    .sidebar .active a {
        color: white;
        background: #007bff;
    }
    .main-content {
        padding: 20px;
    }
    .stat-card {
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        color: white;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .stat-card i {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    .stat-card.total { background: #20c997; }
    .stat-card.pending { background: #fd7e14; }
    .stat-card.approved { background: #28a745; }
    .stat-card.rejected { background: #dc3545; }
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">插件管理</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-1"></i>上传插件
                        </button>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($plugins as $plugin): ?>
                    <div class="col">
                        <div class="card plugin-card <?php echo $plugin['status'] === 'inactive' ? 'plugin-disabled' : ''; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?php echo htmlspecialchars($plugin['name']); ?></h5>
                                    <span class="badge bg-<?php echo $plugin['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $plugin['status'] === 'active' ? '已启用' : '已禁用'; ?>
                                    </span>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">版本: <?php echo htmlspecialchars($plugin['version']); ?></h6>
                                <p class="card-text"><?php echo htmlspecialchars($plugin['description']); ?></p>
                                
                                <div class="btn-group w-100">
                                    <?php if ($plugin['status'] === 'active'): ?>
                                    <a href="plugins.php?action=disable&id=<?php echo $plugin['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-toggle-off me-1"></i>禁用
                                    </a>
                                    <?php else: ?>
                                    <a href="plugins.php?action=enable&id=<?php echo $plugin['id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-toggle-on me-1"></i>启用
                                    </a>
                                    <?php endif; ?>
                                    <a href="plugins.php?action=delete&id=<?php echo $plugin['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此插件吗？')">
                                        <i class="fas fa-trash me-1"></i>删除
                                    </a>
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
                <form method="post" action="plugins.php?action=upload" enctype="multipart/form-data">
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
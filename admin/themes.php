<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/theme_manager.php';

// 检查管理员权限
check_admin_auth();

$action = $_GET['action'] ?? 'list';
$message = '';

// 处理主题操作
switch ($action) {
    case 'activate':
        $themeName = $_GET['theme'] ?? '';
        if (ThemeManager::activateTheme($themeName)) {
            $message = '主题已激活';
        } else {
            $message = '激活主题失败';
        }
        break;
        
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['theme_zip'])) {
            $uploadDir = __DIR__.'/../uploads/themes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $zipFile = $uploadDir . basename($_FILES['theme_zip']['name']);
            if (move_uploaded_file($_FILES['theme_zip']['tmp_name'], $zipFile)) {
                if (ThemeManager::installTheme($zipFile)) {
                    $message = '主题安装成功';
                } else {
                    $message = '主题安装失败';
                }
                unlink($zipFile);
            } else {
                $message = '上传主题失败';
            }
        }
        break;
        
    case 'delete':
        $themeName = $_GET['theme'] ?? '';
        if (ThemeManager::removeTheme($themeName)) {
            $message = '主题已删除';
        } else {
            $message = '删除主题失败';
        }
        break;
}

// 获取所有主题
$themes = ThemeManager::getAvailableThemes();
$activeTheme = ThemeManager::getActiveTheme();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>主题管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    .theme-card {
        transition: all 0.3s;
        height: 100%;
    }
    .theme-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .theme-active {
        border: 2px solid #4a6cf7;
    }
    .theme-screenshot {
        height: 200px;
        background-color: #f8f9fa;
        background-size: cover;
        background-position: center;
        margin-bottom: 1rem;
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
    <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">主题管理</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-1"></i>上传主题
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($themes as $themeName => $theme): ?>
            <div class="col">
                <div class="card theme-card <?php echo $themeName === $activeTheme ? 'theme-active' : ''; ?>">
                    <div class="theme-screenshot" style="background-image: url('<?php echo $theme['screenshot'] ?? 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22300%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_18945b7b7e7%20text%20%7B%20fill%3A%23AAAAAA%3Bfont-weight%3Abold%3Bfont-family%3AArial%2C%20Helvetica%2C%20Open%20Sans%2C%20sans-serif%2C%20monospace%3Bfont-size%3A15pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_18945b7b7e7%22%3E%3Crect%20width%3D%22300%22%20height%3D%22200%22%20fill%3D%22%23EEEEEE%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%22110%22%20y%3D%22107%22%3E'.urlencode(is_string($theme['name']) ? $theme['name'] : 'No Screenshot').'%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E'; ?>')"></div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($theme['name']); ?>
                            <?php if ($themeName === $activeTheme): ?>
                            <span class="badge bg-success">当前主题</span>
                            <?php endif; ?>
                        </h5>
                        <p class="card-text">
                            <small class="text-muted">版本: <?php echo htmlspecialchars($theme['version'] ?? '1.0.0'); ?></small><br>
                            <?php echo htmlspecialchars($theme['description'] ?? ''); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between">
                            <?php if ($themeName !== $activeTheme): ?>
                            <a href="themes.php?action=activate&theme=<?php echo urlencode($themeName); ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-check me-1"></i>启用
                            </a>
                            <?php else: ?>
                            <span class="btn btn-sm btn-success disabled">
                                <i class="fas fa-check-circle me-1"></i>已启用
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($themeName !== 'default' && $themeName !== $activeTheme): ?>
                            <a href="themes.php?action=delete&theme=<?php echo urlencode($themeName); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此主题吗？')">
                                <i class="fas fa-trash me-1"></i>删除
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- 上传主题模态框 -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">上传主题</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="themes.php?action=upload" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="themeZip" class="form-label">选择主题ZIP文件</label>
                            <input class="form-control" type="file" id="themeZip" name="theme_zip" accept=".zip" required>
                            <div class="form-text">请上传包含theme.json文件的主题包</div>
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

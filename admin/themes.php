<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查管理员权限
check_admin_auth();

// 检查主题管理权限
if (!has_permission('system.themes')) {
    handle_error('您没有权限管理主题', true, 403);
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
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = '无效的请求，请刷新重试。';
    } else {
        $action = $_POST['action'] ?? '';
        $themeName = $_POST['theme'] ?? '';

        try {
            if (empty($themeName) && $action !== 'upload') {
                throw new Exception('无效的主题名称');
            }

            switch ($action) {
                case 'activate':
                    if (ThemeManager::activateTheme($themeName)) {
                        $_SESSION['flash_message'] = '主题已成功激活。';
                    } else {
                        throw new Exception('激活主题失败');
                    }
                    break;
                    
                case 'upload':
                    if (isset($_FILES['theme_zip']) && $_FILES['theme_zip']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__.'/../uploads/themes/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                        $zipFile = $uploadDir . uniqid() . '_' . basename($_FILES['theme_zip']['name']);
                        
                        if (move_uploaded_file($_FILES['theme_zip']['tmp_name'], $zipFile)) {
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
                                
                                // 1. 解压到临时目录（安全检查通过后）
                                $tempDir = $uploadDir . 'tmp_' . uniqid();
                                $zip->extractTo($tempDir);
                                $zip->close();
                                unlink($zipFile); // 删除上传的zip包

                                // 2. 查找主题主目录和 theme.json
                                $themeFiles = scandir($tempDir);
                                $themeSubDir = '';
                                foreach ($themeFiles as $file) {
                                    if ($file !== '.' && $file !== '..' && is_dir($tempDir . '/' . $file)) {
                                        $themeSubDir = $file;
                                        break;
                                    }
                                }
                                
                                $themeJsonPath = $tempDir . '/' . ($themeSubDir ? $themeSubDir . '/' : '') . 'theme.json';

                                if (!file_exists($themeJsonPath)) {
                                    rrmdir($tempDir);
                                    throw new Exception('安装失败：压缩包内未找到有效的 theme.json 文件。');
                                }
                                
                                // 3. 获取主题信息
                                $themeInfo = json_decode(file_get_contents($themeJsonPath), true);
                                if (!$themeInfo || empty($themeInfo['name'])) {
                                    rrmdir($tempDir);
                                    throw new Exception('安装失败：theme.json 文件格式不正确或缺少必要字段。');
                                }

                                $themeName = basename($themeSubDir ?: pathinfo($_FILES['theme_zip']['name'], PATHINFO_FILENAME));
                                $finalDir = __DIR__.'/../themes/' . $themeName;

                                if (is_dir($finalDir)) {
                                    rrmdir($tempDir);
                                    throw new Exception("安装失败：主题 '{$themeName}' 已存在。");
                                }

                                // 4. 移动到最终目录
                                $sourcePath = $tempDir . '/' . ($themeSubDir ?: '');
                                rename($sourcePath, $finalDir);
                                rrmdir($tempDir);

                                $_SESSION['flash_message'] = '主题上传并安装成功！';

                            } else {
                                unlink($zipFile);
                                throw new Exception('无法打开上传的ZIP文件。');
                            }
                        } else {
                            throw new Exception('文件上传失败，请检查目录权限。');
                        }
                    } else {
                        throw new Exception('没有文件被上传或上传出错。');
                    }
                    break;
                    
                case 'delete':
                    if (ThemeManager::removeTheme($themeName)) {
                        $_SESSION['flash_message'] = '主题已成功删除。';
                    } else {
                        throw new Exception('删除主题失败');
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    // 统一重定向
    header("Location: themes.php");
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
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
    
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">主题管理</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-upload me-1"></i>上传主题
                        </button>
                    </div>
                </div>
                
                <!-- 子导航 -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link active" href="themes.php">
                            <i class="fas fa-palette me-1"></i>主题列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="debug_themes.php">
                            <i class="fas fa-bug me-1"></i>主题调试
                        </a>
                    </li>
                </ul>
                
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($themes as $themeName => $theme): ?>
                    <div class="col">
                        <div class="card theme-card h-100 <?php echo $themeName === $activeTheme ? 'theme-active' : ''; ?>">
                             <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title"><?php echo htmlspecialchars($theme['name']); ?></h5>
                                    <?php if ($themeName === $activeTheme): ?>
                                        <span class="badge bg-success">当前主题</span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted">版本: <?php echo htmlspecialchars($theme['version'] ?? '1.0.0'); ?></h6>
                                <p class="card-text flex-grow-1"><?php echo htmlspecialchars($theme['description'] ?? ''); ?></p>
                                
                                <div class="btn-group w-100 mt-auto">
                                    <?php if ($themeName !== $activeTheme): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <input type="hidden" name="theme" value="<?php echo htmlspecialchars($themeName); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-check me-1"></i>启用
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="btn btn-sm btn-success disabled">
                                        <i class="fas fa-check-circle me-1"></i>已启用
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($theme['options'])): ?>
                                    <a href="theme_options.php?theme=<?php echo urlencode($themeName); ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-cog me-1"></i>选项
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($themeName !== 'default' && $themeName !== $activeTheme): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('确定要删除此主题吗？')">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="theme" value="<?php echo htmlspecialchars($themeName); ?>">
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

    <!-- 上传主题模态框 -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">上传主题</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
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

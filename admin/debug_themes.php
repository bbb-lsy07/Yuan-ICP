<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查管理员权限
check_admin_auth();

$message = '';
$error = '';

// 主题系统不需要特殊的修复操作，因为主题存储在文件系统中

// 获取主题系统信息
try {
    $pdo = db();
    
    // 检查相关表是否存在
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='system_config'");
    $system_config_exists = $stmt->fetch() !== false;
    
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='theme_options'");
    $theme_options_exists = $stmt->fetch() !== false;
    
    // 获取当前激活的主题
    $active_theme = 'default';
    if ($system_config_exists) {
        $stmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'active_theme'");
        $result = $stmt->fetch();
        $active_theme = $result ? $result['config_value'] : 'default';
    }
    
    // 获取主题选项数据
    $theme_options_data = [];
    if ($theme_options_exists) {
        $stmt = $pdo->query("SELECT * FROM theme_options");
        $theme_options_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 获取文件系统中的主题
    $themes = ThemeManager::getAvailableThemes();
    
} catch (Exception $e) {
    $error = '获取主题信息失败: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>主题表调试 - Yuan-ICP</title>
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
                </div>
                
                <!-- 子导航 -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="themes.php">
                            <i class="fas fa-palette me-1"></i>主题列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="debug_themes.php">
                            <i class="fas fa-bug me-1"></i>主题调试
                        </a>
                    </li>
                </ul>
                
                <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="row d-flex">
                    <div class="col-md-8 d-flex">
                        <div class="card mb-4 w-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>主题系统信息</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <strong>当前主题:</strong> 
                                            <span class="badge bg-success"><?php echo htmlspecialchars($active_theme); ?></span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>可用主题:</strong> <?php echo count($themes); ?>
                                        </div>
                                        <div class="mb-3">
                                            <strong>system_config表:</strong> 
                                            <span class="<?php echo $system_config_exists ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $system_config_exists ? '存在' : '不存在'; ?>
                                            </span>
                                        </div>
                                        <div class="mb-3">
                                            <strong>theme_options表:</strong> 
                                            <span class="<?php echo $theme_options_exists ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $theme_options_exists ? '存在' : '不存在'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <strong>可用主题列表:</strong>
                                        <div class="mt-2">
                                            <?php foreach ($themes as $themeName => $theme): ?>
                                                <span class="badge <?php echo $themeName === $active_theme ? 'bg-success' : 'bg-primary'; ?> me-1 mb-1">
                                                    <?php echo htmlspecialchars($themeName); ?>
                                                    <?php if ($themeName === $active_theme): ?>
                                                        <i class="fas fa-check"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex">
                        <div class="card mb-4 w-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>系统维护</h5>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <p class="mb-3">主题系统使用文件系统存储主题，数据库仅存储配置信息。</p>
                                <div class="mt-auto">
                                    <a href="themes.php" class="btn btn-primary w-100 mb-2">
                                        <i class="fas fa-palette me-2"></i>管理主题
                                    </a>
                                    <a href="theme_options.php?theme=<?php echo urlencode($active_theme); ?>" class="btn btn-outline-info w-100">
                                        <i class="fas fa-cog me-2"></i>主题选项
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($theme_options_data)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>主题选项数据 (<?php echo count($theme_options_data); ?> 条记录)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>主题名称</th>
                                        <th>选项键</th>
                                        <th>选项值</th>
                                        <th>更新时间</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($theme_options_data as $row): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($row['theme_name']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['option_key']); ?></td>
                                            <td>
                                                <code><?php echo htmlspecialchars($row['config_value']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>主题选项数据</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">暂无主题选项数据</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

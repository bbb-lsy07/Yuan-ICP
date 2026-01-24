<?php
require_once __DIR__.'/../includes/bootstrap.php';

require_login();

$db = db();
$message = '';
$error = '';

// 处理强制修复
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'fix_plugins_table') {
        try {
            $messages = [];
            
            // 检查当前表结构
            $plugins_table_exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plugins'")->fetch();
            if ($plugins_table_exists) {
                $plugins_columns_info = $db->query("PRAGMA table_info(plugins);")->fetchAll();
                $plugins_columns = array_column($plugins_columns_info, 'name');
                $messages[] = "当前 plugins 表字段: " . implode(', ', $plugins_columns);
                
                // 备份现有数据
                $existing_data = $db->query("SELECT * FROM plugins")->fetchAll();
                $messages[] = "备份了 " . count($existing_data) . " 条记录";
                
                // 删除旧表
                $db->exec("DROP TABLE IF EXISTS plugins");
                $messages[] = "删除旧表成功";
                
                // 创建新表
                $db->exec("CREATE TABLE plugins (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(100) NOT NULL,
                    identifier VARCHAR(50) NOT NULL UNIQUE,
                    version VARCHAR(20),
                    description TEXT,
                    author VARCHAR(100),
                    is_active BOOLEAN DEFAULT 0,
                    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $messages[] = "创建新表成功";
                
                // 恢复数据
                if (!empty($existing_data)) {
                    $stmt = $db->prepare("INSERT INTO plugins (name, identifier, version, description, author, is_active, installed_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($existing_data as $row) {
                        $identifier = $row['identifier'] ?? $row['name'] ?? 'unknown_' . time();
                        $stmt->execute([
                            $row['name'] ?? '',
                            $identifier,
                            $row['version'] ?? '',
                            $row['description'] ?? '',
                            $row['author'] ?? '',
                            isset($row['is_active']) ? $row['is_active'] : (isset($row['status']) ? $row['status'] : 0),
                            $row['installed_at'] ?? date('Y-m-d H:i:s')
                        ]);
                    }
                    $messages[] = "恢复 " . count($existing_data) . " 条记录成功";
                }
                
                $message = "修复完成：<br>" . implode('<br>', $messages);
            } else {
                // 如果表不存在，创建它
                $db->exec("CREATE TABLE plugins (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(100) NOT NULL,
                    identifier VARCHAR(50) NOT NULL UNIQUE,
                    version VARCHAR(20),
                    description TEXT,
                    author VARCHAR(100),
                    is_active BOOLEAN DEFAULT 0,
                    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $message = "plugins 表不存在，已创建新表。";
            }
        } catch (Exception $e) {
            $error = "修复失败: " . $e->getMessage();
        }
    }
}

// 获取当前表结构信息
$plugins_table_exists = false;
$plugins_columns = [];
$plugins_data = [];

if ($db) {
    $plugins_table_exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plugins'")->fetch();
    if ($plugins_table_exists) {
        $plugins_columns_info = $db->query("PRAGMA table_info(plugins);")->fetchAll();
        $plugins_columns = array_column($plugins_columns_info, 'name');
        $plugins_data = $db->query("SELECT * FROM plugins")->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插件表调试 - Yuan-ICP</title>
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
                    <h1 class="h2">插件管理</h1>
                </div>
                
                <!-- 子导航 -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link" href="plugins.php">
                            <i class="fas fa-list me-1"></i>插件列表
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="debug_plugins.php">
                            <i class="fas fa-bug me-1"></i>插件调试
                        </a>
                    </li>
                </ul>
                
                <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="row d-flex">
                    <div class="col-md-8 d-flex">
                        <div class="card mb-4 w-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>表结构信息</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($plugins_table_exists): ?>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <strong>表状态:</strong> <span class="text-success">存在</span>
                                            </div>
                                            <div class="mb-3">
                                                <strong>字段数量:</strong> <?php echo count($plugins_columns); ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>记录数量:</strong> <?php echo count($plugins_data); ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>错误字段:</strong> 
                                                <span class="<?php echo in_array('plugin_id', $plugins_columns) ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo in_array('plugin_id', $plugins_columns) ? '是' : '否'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <strong>字段列表:</strong>
                                            <div class="mt-2">
                                                <?php foreach ($plugins_columns as $column): ?>
                                                    <span class="badge <?php echo $column === 'plugin_id' ? 'bg-danger' : 'bg-primary'; ?> me-1 mb-1">
                                                        <?php echo htmlspecialchars($column); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>plugins 表不存在</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex">
                        <div class="card mb-4 w-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tools me-2"></i>修复操作</h5>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <p class="mb-3">此工具将强制重建 plugins 表，修复所有结构问题。</p>
                                <form method="post" onsubmit="return confirm('确定要修复 plugins 表吗？这将重建表结构。');" class="mt-auto">
                                    <button type="submit" name="action" value="fix_plugins_table" class="btn btn-danger w-100">
                                        <i class="fas fa-tools me-2"></i>强制修复 plugins 表
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($plugins_data)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>当前数据 (<?php echo count($plugins_data); ?> 条记录)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <?php foreach ($plugins_columns as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plugins_data as $row): ?>
                                        <tr>
                                            <?php foreach ($plugins_columns as $column): ?>
                                                <td>
                                                    <?php 
                                                    $value = $row[$column] ?? '';
                                                    if ($column === 'is_active' || $column === 'status'): 
                                                        $isActive = $value == 1 || $value === '1' || $value === true;
                                                    ?>
                                                        <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo $isActive ? '激活' : '未激活'; ?>
                                                        </span>
                                                    <?php elseif ($column === 'identifier'): ?>
                                                        <span class="badge bg-primary">
                                                            <?php echo htmlspecialchars($value); ?>
                                                        </span>
                                                    <?php elseif ($column === 'version' && $value): ?>
                                                        <span class="badge bg-info">
                                                            v<?php echo htmlspecialchars($value); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($value); ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__.'/../includes/bootstrap.php';

require_login();

$db = db();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
$is_sqlite = ($driver === 'sqlite');

$message = '';
$error = '';
$db_path = '';

if ($is_sqlite) {
    // 获取数据库文件路径
    $config = config('database');
    $db_path = $config['database'] ?? '';
}

// 处理导出
if (isset($_GET['action']) && $_GET['action'] === 'export' && $is_sqlite) {
    if ($db_path && file_exists($db_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($db_path).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($db_path));
        flush();
        readfile($db_path);
        exit;
    } else {
        $error = '数据库文件未找到！';
    }
}

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_sqlite) {
    $action = $_POST['action'] ?? '';

    // 处理导入/恢复
    if ($action === 'import') {
        if (isset($_FILES['sqlite_restore_file']) && $_FILES['sqlite_restore_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_file = $_FILES['sqlite_restore_file']['tmp_name'];
            try {
                // 简单验证上传的文件是否为 SQLite 数据库
                new PDO('sqlite:' . $uploaded_file);
            } catch (Exception $e) {
                $error = "上传的文件不是一个有效的SQLite数据库。";
            }

            if (!$error) {
                if (!move_uploaded_file($uploaded_file, $db_path)) {
                    $error = "恢复数据库失败。请检查 `data` 目录及其父目录的写入权限。";
                } else {
                    $message = "数据库已成功从备份恢复！页面将在3秒后刷新。";
                    header("Refresh:3");
                }
            }
        } else {
            $error = "文件上传失败或没有选择文件。";
        }
    }
    
    // 处理强制重建 plugins 表
    elseif ($action === 'force_rebuild_plugins') {
        try {
            $repair_messages = [];
            
            // 备份现有数据
            $existing_data = $db->query("SELECT * FROM plugins")->fetchAll();
            $repair_messages[] = "备份了 " . count($existing_data) . " 条插件记录。";
            
            // 删除旧表
            $db->exec("DROP TABLE IF EXISTS plugins");
            $repair_messages[] = "成功删除旧的 plugins 表。";
            
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
            $repair_messages[] = "成功创建新的 plugins 表结构。";
            
            // 恢复数据（如果有的话）
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
                $repair_messages[] = "成功恢复 " . count($existing_data) . " 条插件记录。";
            }
            
            $message = "强制重建 plugins 表完成：<br>" . implode('<br>', $repair_messages);
        } catch (Exception $e) {
            $error = "强制重建 plugins 表失败: " . $e->getMessage();
        }
    }
    
    // 处理一键修复
    elseif ($action === 'repair_db') {
        try {
            $repair_messages = [];
            
            // 使用统一的表结构定义函数
            $schema = get_core_schema();

            // 检查并创建所有核心表
            foreach($schema as $tableName => $createQuery) {
                $table_exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tableName}'")->fetch();
                if (!$table_exists) {
                    $db->exec($createQuery);
                    $repair_messages[] = "成功创建 `{$tableName}` 表。";
                }
            }
            
            // 检查 icp_applications 表的列
            $columns_info = $db->query("PRAGMA table_info(icp_applications);")->fetchAll();
            $columns = array_column($columns_info, 'name');

            if (!in_array('payment_platform', $columns)) {
                $db->exec("ALTER TABLE icp_applications ADD COLUMN payment_platform VARCHAR(50) NULL;");
                $repair_messages[] = "成功添加 `payment_platform` 字段到 `icp_applications` 表。";
            }
            if (!in_array('transaction_id', $columns)) {
                $db->exec("ALTER TABLE icp_applications ADD COLUMN transaction_id VARCHAR(255) NULL;");
                $repair_messages[] = "成功添加 `transaction_id` 字段到 `icp_applications` 表。";
            }
            if (!in_array('is_resubmitted', $columns)) {
                $db->exec("ALTER TABLE icp_applications ADD COLUMN is_resubmitted BOOLEAN DEFAULT 0;");
                $repair_messages[] = "成功添加 `is_resubmitted` 字段到 `icp_applications` 表。";
            }

            // 检查 plugins 表的列
            $plugins_table_exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plugins'")->fetch();
            if ($plugins_table_exists) {
                $plugins_columns_info = $db->query("PRAGMA table_info(plugins);")->fetchAll();
                $plugins_columns = array_column($plugins_columns_info, 'name');

                // 调试信息：显示当前表结构
                $repair_messages[] = "当前 plugins 表字段: " . implode(', ', $plugins_columns);

                // 检查是否有错误的字段名需要修复
                $needs_rebuild = false;
                if (in_array('plugin_id', $plugins_columns) && !in_array('id', $plugins_columns)) {
                    $needs_rebuild = true;
                    $repair_messages[] = "检测到错误的字段名 `plugin_id`，需要重建表结构。";
                }
                if (in_array('status', $plugins_columns) && !in_array('is_active', $plugins_columns)) {
                    $needs_rebuild = true;
                    $repair_messages[] = "检测到错误的字段名 `status`，需要重建表结构。";
                }
                
                // 如果表存在但缺少必需字段，也强制重建
                $required_fields = ['id', 'name', 'identifier', 'version', 'description', 'author', 'is_active', 'installed_at'];
                $missing_fields = array_diff($required_fields, $plugins_columns);
                if (!empty($missing_fields)) {
                    $needs_rebuild = true;
                    $repair_messages[] = "检测到缺失字段: " . implode(', ', $missing_fields) . "，需要重建表结构。";
                }
                
                // 如果检测到任何问题，总是显示详细信息
                if ($needs_rebuild) {
                    $repair_messages[] = "开始重建 plugins 表...";
                } else {
                    $repair_messages[] = "plugins 表结构正常，无需修复。";
                }

                // 如果表结构有问题，重建表
                if ($needs_rebuild) {
                    try {
                        // 备份现有数据
                        $existing_data = $db->query("SELECT * FROM plugins")->fetchAll();
                        
                        // 删除旧表
                        $db->exec("DROP TABLE plugins");
                        
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
                        
                        // 恢复数据（如果有的话）
                        if (!empty($existing_data)) {
                            $stmt = $db->prepare("INSERT INTO plugins (name, identifier, version, description, author, is_active, installed_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            foreach ($existing_data as $row) {
                                $identifier = $row['identifier'] ?? $row['name'] ?? 'unknown';
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
                        }
                        
                        $repair_messages[] = "成功重建 `plugins` 表结构并恢复数据。";
                    } catch (Exception $e) {
                        $repair_messages[] = "重建 `plugins` 表时出错: " . $e->getMessage();
                    }
                } else {
                    // 如果表结构正确，只添加缺失的字段
                    if (!in_array('identifier', $plugins_columns)) {
                        $db->exec("ALTER TABLE plugins ADD COLUMN identifier VARCHAR(50) NULL;");
                        $repair_messages[] = "成功添加 `identifier` 字段到 `plugins` 表。";
                    }
                    if (!in_array('version', $plugins_columns)) {
                        $db->exec("ALTER TABLE plugins ADD COLUMN version VARCHAR(20) NULL;");
                        $repair_messages[] = "成功添加 `version` 字段到 `plugins` 表。";
                    }
                    if (!in_array('description', $plugins_columns)) {
                        $db->exec("ALTER TABLE plugins ADD COLUMN description TEXT NULL;");
                        $repair_messages[] = "成功添加 `description` 字段到 `plugins` 表。";
                    }
                    if (!in_array('author', $plugins_columns)) {
                        $db->exec("ALTER TABLE plugins ADD COLUMN author VARCHAR(100) NULL;");
                        $repair_messages[] = "成功添加 `author` 字段到 `plugins` 表。";
                    }
                    if (!in_array('is_active', $plugins_columns)) {
                        $db->exec("ALTER TABLE plugins ADD COLUMN is_active BOOLEAN DEFAULT 0;");
                        $repair_messages[] = "成功添加 `is_active` 字段到 `plugins` 表。";
                    }
                    if (!in_array('installed_at', $plugins_columns)) {
                        $db->exec("ALTER TABLE plugins ADD COLUMN installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;");
                        $repair_messages[] = "成功添加 `installed_at` 字段到 `plugins` 表。";
                    }
                }
            }

            if (empty($repair_messages)) {
                $message = "数据库结构已是最新，无需修复。";
            } else {
                $message = "数据库修复完成：<br>" . implode('<br>', $repair_messages);
            }
        } catch (Exception $e) {
            $error = "数据库修复失败: " . $e->getMessage();
        }
    }
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
                    <div class="alert alert-warning">此功能仅在您使用 <strong>SQLite</strong> 数据库时可用。</div>
                <?php else: ?>
                    <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0">导出与导入</h5></div>
                                <div class="card-body">
                                    <p>点击下方按钮下载当前数据库的完整备份文件。</p>
                                    <a href="backup.php?action=export" class="btn btn-primary"><i class="fas fa-download me-2"></i>导出数据库</a>
                                    <hr>
                                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> 导入操作将完全覆盖当前数据库，请谨慎操作！</div>
                                    <form method="post" enctype="multipart/form-data" onsubmit="return confirm('您确定要用上传的文件覆盖当前数据库吗？此操作无法撤销！');">
                                        <input type="hidden" name="action" value="import">
                                        <div class="mb-3">
                                            <label for="sqlite_restore_file" class="form-label">选择 <code>.db</code> 备份文件</label>
                                            <input class="form-control" type="file" id="sqlite_restore_file" name="sqlite_restore_file" accept=".db,.sqlite,.sqlite3" required>
                                        </div>
                                        <button type="submit" class="btn btn-danger"><i class="fas fa-upload me-2"></i>导入并恢复</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header"><h5 class="mb-0">数据库维护</h5></div>
                                <div class="card-body">
                                    <p>此功能将检查数据库结构，自动添加新版本所需的字段，并移除已废弃的旧数据表（如会员表）。</p>
                                    <p>当您从旧版本升级，或上传了旧版本的数据库备份后，请点击此按钮来确保数据库兼容。</p>
                                    <form method="post" onsubmit="return confirm('确定要开始检查和修复数据库表吗？');">
                                        <button type="submit" name="action" value="repair_db" class="btn btn-warning w-100 mb-2">
                                            <i class="fas fa-tools me-2"></i>一键修复数据库表
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('确定要强制重建 plugins 表吗？这将删除现有插件数据！');">
                                        <button type="submit" name="action" value="force_rebuild_plugins" class="btn btn-danger w-100">
                                            <i class="fas fa-exclamation-triangle me-2"></i>强制重建 plugins 表
                                        </button>
                                    </form>
                                </div>
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
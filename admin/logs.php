<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查管理员权限
check_admin_auth();

$message = '';

try {
    $db = db();
    
    // 检查并创建 admin_logs 表（如果不存在）
    $tableCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admin_logs'")->fetch();
    if (!$tableCheck) {
        $db->exec("
            CREATE TABLE admin_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT,
                target TEXT,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $message = "已创建 admin_logs 表";
    } else {
        // 检查并添加缺失的列，以兼容旧版本
        $columns_info = $db->query("PRAGMA table_info(admin_logs);")->fetchAll();
        $columns = array_column($columns_info, 'name');
        if (!in_array('ip_address', $columns)) {
            $db->exec("ALTER TABLE admin_logs ADD COLUMN ip_address VARCHAR(45) NULL;");
        }
        if (!in_array('user_agent', $columns)) {
            $db->exec("ALTER TABLE admin_logs ADD COLUMN user_agent TEXT NULL;");
        }
    }

    // 处理删除日志操作 - 重定向到统一API
    if (isset($_GET['action']) && $_GET['action'] === 'delete') {
        $_POST['action'] = 'clear_logs';
        include __DIR__.'/../api/admin.php';
        exit;
    }

    // 获取日志列表（最近100条）
    $logs = $db->query("
        SELECT l.*, u.username 
        FROM admin_logs l 
        LEFT JOIN admin_users u ON l.user_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT 100
    ")->fetchAll();

    // 获取日志统计
    $stats = $db->query("
        SELECT
            COUNT(*) as total,
            COUNT(CASE WHEN action = 'login' THEN 1 END) as login_count,
            COUNT(CASE WHEN action = 'login_failed' THEN 1 END) as login_failed_count,
            COUNT(CASE WHEN action = 'create' THEN 1 END) as create_count,
            COUNT(CASE WHEN action = 'update' THEN 1 END) as update_count,
            COUNT(CASE WHEN action = 'delete' THEN 1 END) as delete_count
        FROM admin_logs
    ")->fetch();
    
} catch (Exception $e) {
    $message = '加载日志数据失败: ' . $e->getMessage();
    $logs = [];
    $stats = [
        'total' => 0,
        'login_count' => 0,
        'login_failed_count' => 0,
        'create_count' => 0,
        'update_count' => 0,
        'delete_count' => 0
    ];
    error_log('日志页面错误: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>操作日志 - Yuan-ICP</title>
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
                    <h1 class="h2">操作日志</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($stats['total'] > 0): ?>
                        <a href="logs.php?action=delete" class="btn btn-sm btn-outline-danger" 
                           onclick="return confirm('确定要清除所有日志记录吗？')">
                            <i class="fas fa-trash me-1"></i>清除日志
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <!-- 统计卡片 -->
                <div class="row">
                    <div class="col-md-2">
                        <div class="stat-card total">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>总日志数</h6>
                                    <h3><?php echo $stats['total']; ?></h3>
                                </div>
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card login">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>登录</h6>
                                    <h3><?php echo $stats['login_count']; ?></h3>
                                </div>
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card create">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>创建</h6>
                                    <h3><?php echo $stats['create_count']; ?></h3>
                                </div>
                                <i class="fas fa-plus-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card update">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>更新</h6>
                                    <h3><?php echo $stats['update_count']; ?></h3>
                                </div>
                                <i class="fas fa-edit"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card delete">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>删除</h6>
                                    <h3><?php echo $stats['delete_count']; ?></h3>
                                </div>
                                <i class="fas fa-trash-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 日志列表 -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">最近操作记录</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                        <div class="text-center text-muted py-3">
                            <p>暂无操作日志</p>
                            <small class="text-muted">
                                调试信息：数据库连接状态 - 
                                <?php 
                                try {
                                    $test_db = db();
                                    echo "正常";
                                } catch (Exception $e) {
                                    echo "错误: " . $e->getMessage();
                                }
                                ?>
                            </small>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover log-table">
                                <thead>
                                    <tr>
                                        <th>时间</th>
                                        <th>操作用户</th>
                                        <th>操作类型</th>
                                        <th>操作对象</th>
                                        <th>IP地址</th>
                                        <th>详细信息</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? '系统/访客'); ?></td>
                                        <td>
                                            <?php if($log['action'] == 'login_failed'): ?>
                                                <span class="badge bg-danger">登录失败</span>
                                            <?php else: ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($log['action']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['target'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

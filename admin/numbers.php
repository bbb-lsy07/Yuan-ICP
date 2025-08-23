<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

require_login();
$db = db();
$message = '';

// 处理删除或状态切换操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'delete' && $id > 0) {
        $stmt = $db->prepare("DELETE FROM selectable_numbers WHERE id = ?");
        $stmt->execute([$id]);
        $message = "号码已删除。";
    } elseif ($action === 'toggle_premium' && $id > 0) {
        $stmt = $db->prepare("UPDATE selectable_numbers SET is_premium = NOT is_premium WHERE id = ?");
        $stmt->execute([$id]);
        $message = "号码靓号状态已更新。";
    }
    redirect('numbers.php?message=' . urlencode($message));
}

if(isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// 分页和搜索逻辑
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if (!empty($search)) {
    $where = "WHERE number LIKE ?";
    $params[] = "%$search%";
}

// 获取总数
$countQuery = "SELECT COUNT(*) FROM selectable_numbers $where";
$totalStmt = $db->prepare($countQuery);
$totalStmt->execute($params);
$totalItems = $totalStmt->fetchColumn();
$totalPages = ceil($totalItems / $perPage);

// 获取当前页数据
$query = "SELECT * FROM selectable_numbers $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$numbers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>号码池管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__."/../includes/admin_sidebar.php"; ?>
            </div>
            
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>号码池管理</h2>
                    <a href="settings.php?tab=numbers" class="btn btn-primary"><i class="fas fa-plus"></i> 添加新号码</a>
                </div>
                
                <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form>
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" name="search" class="form-control" placeholder="搜索备案号..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>号码</th>
                                        <th>状态</th>
                                        <th>是否靓号</th>
                                        <th>创建时间</th>
                                        <th width="180px">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($numbers as $num): ?>
                                    <tr>
                                        <td><?php echo $num['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($num['number']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $num['status'] === 'available' ? 'success' : 'secondary'; ?>">
                                                <?php echo $num['status'] === 'available' ? '可用' : '已使用'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $num['is_premium'] ? 'warning' : 'info'; ?>">
                                                <?php echo $num['is_premium'] ? '是' : '否'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($num['created_at'])); ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $num['id']; ?>">
                                                <button type="submit" name="action" value="toggle_premium" class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-gem"></i> 切换
                                                </button>
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除这个号码吗？')">
                                                    <i class="fas fa-trash"></i> 删除
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Pagination links -->
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once __DIR__.'/../includes/bootstrap.php';

require_login();

$message = '';

// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $action = $_POST['action'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            throw new Exception('无效的号码ID');
        }
        
        $db = db();
        
        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM selectable_numbers WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => '号码已删除']);
        } elseif ($action === 'toggle_premium') {
            $stmt = $db->prepare("UPDATE selectable_numbers SET is_premium = NOT is_premium WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => '靓号状态已更新']);
        } else {
            throw new Exception('无效的操作');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 获取查询参数
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 20;

try {
    $db = db();
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

    // 使用分页类
    $pagination = new Pagination($page, $totalItems, $perPage, '', $_GET);

    // 获取当前页数据
    $query = "SELECT * FROM selectable_numbers $where ORDER BY created_at DESC " . $pagination->getSqlLimit();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $numbers = $stmt->fetchAll();
    
} catch (Exception $e) {
    handle_error('加载号码列表失败: ' . $e->getMessage());
}
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
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning" onclick="togglePremium(<?php echo $num['id']; ?>)">
                                                    <i class="fas fa-gem"></i> 切换
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteNumber(<?php echo $num['id']; ?>)">
                                                    <i class="fas fa-trash"></i> 删除
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="pagination-container">
                            <?php echo $pagination->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-api.js"></script>
    <script>
        const api = new AdminAPI();
        
        function togglePremium(id) {
            if (confirm('确定要切换此号码的靓号状态吗？')) {
                const formData = new FormData();
                formData.append('action', 'toggle_premium');
                formData.append('id', id);
                
                api.request('numbers.php', {
                    method: 'POST',
                    body: formData,
                    headers: {}
                })
                .then(response => {
                    if (response.success) {
                        api.showSuccess(response.message);
                        location.reload();
                    } else {
                        api.showError(response.message);
                    }
                })
                .catch(error => {
                    api.showError(error.message);
                });
            }
        }
        
        function deleteNumber(id) {
            if (confirm('确定要删除这个号码吗？')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                api.request('numbers.php', {
                    method: 'POST',
                    body: formData,
                    headers: {}
                })
                .then(response => {
                    if (response.success) {
                        api.showSuccess(response.message);
                        location.reload();
                    } else {
                        api.showError(response.message);
                    }
                })
                .catch(error => {
                    api.showError(error.message);
                });
            }
        }
    </script>
</body>
</html>

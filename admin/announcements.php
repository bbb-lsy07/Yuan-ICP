<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

// 检查登录状态
require_login();

// 获取查询参数
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 10;

// 构建基础查询
$db = db();
$query = "SELECT * FROM announcements";
$where = [];
$params = [];

// 添加搜索条件
if (!empty($search)) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 组合WHERE条件
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// 获取总数用于分页
$countQuery = "SELECT COUNT(*) FROM ($query) as total";
$total = $db->prepare($countQuery);
$total->execute($params);
$totalItems = $total->fetchColumn();

// 计算分页
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

// 获取当前页数据
$query .= " ORDER BY is_pinned DESC, created_at DESC LIMIT $offset, $perPage";
$stmt = $db->prepare($query);
$stmt->execute($params);
$announcements = $stmt->fetchAll();

// 处理公告操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'delete') {
        $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
    } elseif ($action === 'toggle_pin') {
        $db->prepare("UPDATE announcements SET is_pinned = NOT is_pinned WHERE id = ?")->execute([$id]);
    }
    
    // 重定向避免重复提交
    redirect(current_url());
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    .pinned {
        background-color: #fff8e1;
    }
    .search-box {
        max-width: 300px;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>公告管理</h2>
                    <a href="announcement_edit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> 新增公告
                    </a>
                </div>
                
                <!-- 搜索 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">搜索公告</label>
                                <div class="input-group search-box">
                                    <input type="text" name="search" class="form-control" placeholder="标题或内容" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">搜索</button>
                                <a href="announcements.php" class="btn btn-link ms-2">重置</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- 公告列表 -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50px">置顶</th>
                                        <th>标题</th>
                                        <th width="150px">发布时间</th>
                                        <th width="150px">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($announcements as $ann): ?>
                                    <tr class="<?php echo $ann['is_pinned'] ? 'pinned' : ''; ?>">
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_pin">
                                                <button type="submit" class="btn btn-sm btn-link">
                                                    <i class="fas fa-thumbtack <?php echo $ann['is_pinned'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                                            <div class="text-muted small mt-1">
                                                <?php echo mb_substr(strip_tags($ann['content']), 0, 50); ?>...
                                            </div>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($ann['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="announcement_edit.php?id=<?php echo $ann['id']; ?>" class="btn btn-primary">编辑</a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $ann['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('确定要删除此公告吗？')">删除</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo modify_url(['page' => $page - 1]); ?>">上一页</a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo modify_url(['page' => $i]); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo modify_url(['page' => $page + 1]); ?>">下一页</a>
                                </li>
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

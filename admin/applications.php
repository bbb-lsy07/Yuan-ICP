<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

// 检查登录状态
require_login();

// 获取查询参数
$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 15;

// 构建基础查询
$db = db();
$query = "SELECT a.*, u.username as reviewer FROM icp_applications a
          LEFT JOIN admin_users u ON a.reviewed_by = u.id";
$where = [];
$params = [];

// 添加状态筛选
if (in_array($status, ['pending', 'approved', 'rejected'])) {
    $where[] = "a.status = ?";
    $params[] = $status;
}

// 添加搜索条件
if (!empty($search)) {
    $where[] = "(a.website_name LIKE ? OR a.domain LIKE ? OR a.number LIKE ?)";
    $params[] = "%$search%";
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
$query .= " ORDER BY a.created_at DESC LIMIT $offset, $perPage";
$stmt = $db->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// 处理审核/驳回操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $user = current_user();
    
    // 获取申请详情用于发送邮件
    $appStmt = $db->prepare("SELECT * FROM icp_applications WHERE id = ?");
    $appStmt->execute([$id]);
    $application = $appStmt->fetch();

    if ($application) {
        if ($_POST['action'] === 'approve') {
            $db->prepare("UPDATE icp_applications SET status = 'approved', reviewed_by = ?, reviewed_at = ".db_now()." WHERE id = ?")
               ->execute([$user['id'], $id]);
            
            // 发送通过邮件
            $site_name = db()->query("SELECT config_value FROM system_config WHERE config_key = 'site_name'")->fetchColumn() ?: 'Yuan-ICP';
            $subject = "【{$site_name}】您的备案申请已通过";
            $body = "
                <p>尊敬的用户 {$application['owner_name']},</p>
                <p>恭喜您！您为网站 <strong>{$application['website_name']} ({$application['domain']})</strong> 提交的备案申请已审核通过。</p>
                <p>您的备案号为：<strong>{$application['number']}</strong></p>
                <p>请按照要求将备案号链接放置在您网站的底部。感谢您的使用！</p>
                <br>
                <p>-- {$site_name} 团队</p>
            ";
            send_email($application['owner_email'], $application['owner_name'], $subject, $body);

        } elseif ($_POST['action'] === 'reject' && !empty($_POST['reason'])) {
            $reason = trim($_POST['reason']);
            $db->prepare("UPDATE icp_applications SET status = 'rejected', reviewed_by = ?, reviewed_at = ".db_now().", reject_reason = ? WHERE id = ?")
               ->execute([$user['id'], $reason, $id]);
            
            // 发送驳回邮件
            $site_name = db()->query("SELECT config_value FROM system_config WHERE config_key = 'site_name'")->fetchColumn() ?: 'Yuan-ICP';
            $subject = "【{$site_name}】您的备案申请已被驳回";
            $body = "
                <p>尊敬的用户 {$application['owner_name']},</p>
                <p>很遗憾地通知您，您为网站 <strong>{$application['website_name']} ({$application['domain']})</strong> 提交的备案申请已被驳回。</p>
                <p>驳回原因如下：</p>
                <blockquote style='border-left: 4px solid #ccc; padding-left: 15px; margin-left: 0;'>
                    <p>".nl2br(htmlspecialchars($reason))."</p>
                </blockquote>
                <p>请您根据驳回原因修改信息后重新提交申请。感谢您的理解与合作！</p>
                <br>
                <p>-- {$site_name} 团队</p>
            ";
            send_email($application['owner_email'], $application['owner_name'], $subject, $body);
        }
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
    <title>备案管理 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    .status-badge {
        min-width: 60px;
        display: inline-block;
        text-align: center;
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
                <h2 class="mb-4">备案管理</h2>
                
                <!-- 筛选和搜索 -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">状态筛选</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>全部状态</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>待审核</option>
                                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>已通过</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>已驳回</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">搜索</label>
                                <div class="input-group search-box">
                                    <input type="text" name="search" class="form-control" placeholder="网站名称/域名/备案号" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">筛选</button>
                                <a href="applications.php" class="btn btn-link ms-2">重置</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- 备案申请列表 -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>备案号</th>
                                        <th>网站名称</th>
                                        <th>域名</th>
                                        <th>状态</th>
                                        <th>申请时间</th>
                                        <th>审核人</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($app['number']); ?>
                                            <?php if (check_if_number_is_premium($app['number'])): ?>
                                                <span class="badge bg-warning text-dark ms-2"><i class="fas fa-gem"></i> 靓号</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['website_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['domain']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $app['status'] === 'approved' ? 'success' : 
                                                     ($app['status'] === 'pending' ? 'warning' : 'danger'); 
                                            ?> status-badge">
                                                <?php echo $app['status'] === 'approved' ? '已通过' : 
                                                      ($app['status'] === 'pending' ? '待审核' : '已驳回'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($app['reviewer'] ?? '-'); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($app['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"
                                                        data-id="<?php echo $app['id']; ?>">通过</button>
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"
                                                        data-id="<?php echo $app['id']; ?>">驳回</button>
                                                <?php endif; ?>
                                                
                                                <!-- 新增的编辑按钮 -->
                                                <a href="application_edit.php?id=<?php echo $app['id']; ?>" class="btn btn-info">编辑</a>

                                                <a href="applications.php?search=<?php echo urlencode($app['number']); ?>" class="btn btn-primary">查看</a>
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

    <!-- 审核通过模态框 -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" id="approveId">
                    <input type="hidden" name="action" value="approve">
                    <div class="modal-header">
                        <h5 class="modal-title">通过备案申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>确定要通过此备案申请吗？</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-success">确认通过</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 驳回模态框 -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" id="rejectId">
                    <input type="hidden" name="action" value="reject">
                    <div class="modal-header">
                        <h5 class="modal-title">驳回备案申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rejectReason" class="form-label">驳回原因</label>
                            <textarea class="form-control" id="rejectReason" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">确认驳回</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 模态框事件处理
        const approveModal = document.getElementById('approveModal');
        const rejectModal = document.getElementById('rejectModal');
        
        approveModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('approveId').value = button.getAttribute('data-id');
        });
        
        rejectModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('rejectId').value = button.getAttribute('data-id');
        });
    </script>
</body>
</html>

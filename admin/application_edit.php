<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

// 检查登录状态，确保只有管理员可以访问
require_login();

$db = db();
$application = null;
$error = '';
$message = '';

// 必须通过GET参数提供一个ID
if (!isset($_GET['id'])) {
    // 如果没有ID，重定向回列表页
    redirect('applications.php');
}

$id = intval($_GET['id']);
$stmt = $db->prepare("SELECT * FROM icp_applications WHERE id = ?");
$stmt->execute([$id]);
$application = $stmt->fetch();
$is_premium = false; // 先设置一个默认值
if ($application) {
    $is_premium = check_if_number_is_premium($application['number']);
}

// 如果根据ID找不到申请记录，则显示错误
if (!$application) {
    $error = '未找到指定的备案申请记录。';
}

// 处理表单提交（保存数据）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $application) {
    // 从POST数据中获取所有可编辑字段的值
    $number = trim($_POST['number'] ?? '');
    $website_name = trim($_POST['website_name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $owner_email = trim($_POST['owner_email'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');
    $reject_reason = trim($_POST['reject_reason'] ?? '');

    // 简单的数据验证
    if (empty($website_name) || empty($domain) || empty($number)) {
        $error = '备案号、网站名称和域名不能为空。';
    } elseif (!in_array($status, ['pending', 'approved', 'rejected'])) {
        $error = '无效的审核状态。';
    } else {
        // 构建更新语句
        $update_stmt = $db->prepare(
            "UPDATE icp_applications SET 
                number = ?, 
                website_name = ?, 
                domain = ?, 
                description = ?, 
                owner_name = ?, 
                owner_email = ?, 
                status = ?, 
                reject_reason = ?
            WHERE id = ?"
        );
        
        // 执行更新
        $update_stmt->execute([
            $number,
            $website_name,
            $domain,
            $description,
            $owner_name,
            $owner_email,
            $status,
            $reject_reason,
            $id
        ]);
        
        $message = '备案信息已成功更新！';
        
        // 更新成功后，重新从数据库加载最新的数据以在表单中显示
        $stmt->execute([$id]);
        $application = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑备案申请 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- 引入您自己的后台CSS -->
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
                    <h2>编辑备案申请</h2>
                    <a href="applications.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($application): ?>
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo $application['id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="number" class="form-label">备案号</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="number" name="number" value="<?php echo htmlspecialchars($application['number']); ?>" required>
                                        <?php if ($is_premium): ?>
                                            <span class="input-group-text bg-warning text-dark"><i class="fas fa-gem"></i> 靓号</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">审核状态</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="pending" <?php echo ($application['status'] === 'pending') ? 'selected' : ''; ?>>待审核</option>
                                        <option value="approved" <?php echo ($application['status'] === 'approved') ? 'selected' : ''; ?>>已通过</option>
                                        <option value="rejected" <?php echo ($application['status'] === 'rejected') ? 'selected' : ''; ?>>已驳回</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="website_name" class="form-label">网站名称</label>
                                    <input type="text" class="form-control" id="website_name" name="website_name" value="<?php echo htmlspecialchars($application['website_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="domain" class="form-label">域名</label>
                                    <input type="text" class="form-control" id="domain" name="domain" value="<?php echo htmlspecialchars($application['domain']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">网站描述</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($application['description']); ?></textarea>
                            </div>

                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="owner_name" class="form-label">所有者名称</label>
                                    <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($application['owner_name']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="owner_email" class="form-label">所有者邮箱</label>
                                    <input type="email" class="form-control" id="owner_email" name="owner_email" value="<?php echo htmlspecialchars($application['owner_email']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reject_reason" class="form-label">驳回原因 (如果状态为"已驳回")</label>
                                <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3"><?php echo htmlspecialchars($application['reject_reason']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">申请时间</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['created_at']); ?>" disabled readonly>
                                </div>
                                 <div class="col-md-6 mb-3">
                                    <label class="form-label">审核时间</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['reviewed_at'] ?? 'N/A'); ?>" disabled readonly>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 保存更改
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">无法加载备案申请信息，该记录可能已被删除。</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
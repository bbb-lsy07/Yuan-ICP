<?php
require_once __DIR__.'/../includes/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $appManager = new ApplicationManager();
        $user = current_user();
        $id = intval($_POST['id']);
        
        if ($_POST['action'] === 'approve') {
            $appManager->review($id, 'approve', $user['id']);
            echo json_encode(['success' => true, 'message' => '申请已通过']);
        } elseif ($_POST['action'] === 'reject' && !empty($_POST['reason'])) {
            $reason = trim($_POST['reason']);
            $appManager->review($id, 'reject', $user['id'], $reason);
            echo json_encode(['success' => true, 'message' => '申请已驳回']);
        } elseif ($_POST['action'] === 'delete') {
            $appManager->delete($id);
            // 您可以按需添加操作日志
            // log_admin_action('delete', 'application', "删除备案申请 ID: {$id}");
            echo json_encode(['success' => true, 'message' => '申请已成功删除']);
        } else {
            throw new Exception('无效的操作');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 15;

try {
    $appManager = new ApplicationManager();
    $filters = ['status' => $status, 'search' => $search];
    $result = $appManager->getList($filters, $page, $perPage);
    $applications = $result['applications'];
    $pagination = $result['pagination'];
} catch (Exception $e) {
    handle_error('加载申请列表失败: ' . $e->getMessage());
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
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* 自定义弹窗样式 */
        .custom-alert-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        .custom-alert-box {
            background-color: white;
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
            max-width: 400px;
            width: 90%;
            animation: slideInUp 0.3s ease;
        }
        .custom-alert-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .custom-alert-overlay.success .custom-alert-icon {
            color: #10b981;
        }
        .custom-alert-overlay.error .custom-alert-icon {
            color: #ef4444;
        }
        .custom-alert-overlay.warning .custom-alert-icon {
            color: #f59e0b;
        }
        .custom-alert-overlay.info .custom-alert-icon {
            color: #3b82f6;
        }
        #custom-alert-message {
            font-size: 1.1rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        @keyframes slideInUp { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0"><?php include __DIR__.'/../includes/admin_sidebar.php'; ?></div>
            <div class="col-md-10 main-content">
                <h2 class="mb-4">备案管理</h2>
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">状态筛选</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php if ($status === 'all') echo 'selected'; ?>>全部状态</option>
                                    <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>待审核</option>
                                    <option value="approved" <?php if ($status === 'approved') echo 'selected'; ?>>已通过</option>
                                    <option value="rejected" <?php if ($status === 'rejected') echo 'selected'; ?>>已驳回</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">搜索</label>
                                <div class="input-group search-box">
                                    <input type="text" name="search" class="form-control" placeholder="网站名称/域名/备案号" value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">筛选</button>
                                <a href="applications.php" class="btn btn-link ms-2">重置</a>
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
                                        <th>备案号</th>
                                        <th>网站名称 / 域名</th>
                                        <th>申请人</th>
                                        <th>状态</th>
                                        <th>申请时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                    <tr data-id="<?php echo $app['id']; ?>">
                                        <td>
                                            <?php echo htmlspecialchars($app['number']); ?>
                                            <?php if (check_if_number_is_premium($app['number'])): ?>
                                                <span class="badge bg-warning text-dark ms-1" title="靓号"><i class="fas fa-gem"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['website_name']); ?></strong>
                                            <div class="text-muted small"><?php echo htmlspecialchars($app['domain']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['owner_name']); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = 'secondary'; $status_text = '未知';
                                            switch ($app['status']) {
                                                case 'approved': $status_class = 'success'; $status_text = '已通过'; break;
                                                case 'pending': $status_class = 'warning'; $status_text = '待审核'; break;
                                                case 'pending_payment': $status_class = 'info'; $status_text = '待付款'; break;
                                                case 'rejected': $status_class = 'danger'; $status_text = '已驳回'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?> status-badge"><?php echo $status_text; ?></span>
                                            <?php if ($app['is_resubmitted']): ?>
                                                <span class="badge bg-primary ms-1" title="用户修改后重新提交">重</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($app['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($app['status'] === 'pending' || $app['status'] === 'pending_payment'): ?>
                                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal" data-id="<?php echo $app['id']; ?>">通过</button>
                                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#rejectModal" data-id="<?php echo $app['id']; ?>">驳回</button>
                                                <?php endif; ?>
                                                <a href="application_edit.php?id=<?php echo $app['id']; ?>" class="btn btn-info">详情</a>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $app['id']; ?>">删除</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination-container">
                            <?php $paginationObj = new Pagination($page, $pagination['total_items'], $perPage, '', $_GET); echo $paginationObj->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modals -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" id="approveId">
                    <input type="hidden" name="action" value="approve">
                    <div class="modal-header">
                        <h5 class="modal-title">通过备案申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" id="rejectId">
                    <input type="hidden" name="action" value="reject">
                    <div class="modal-header">
                        <h5 class="modal-title">驳回备案申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
    
    <!-- 新增的删除确认弹窗 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" id="deleteId">
                    <input type="hidden" name="action" value="delete">
                    <div class="modal-header">
                        <h5 class="modal-title">删除申请</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger"><strong>警告:</strong> 此操作不可恢复！</p>
                        <p>您确定要永久删除此备案申请吗？</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">确认删除</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 自定义弹窗UI -->
    <div id="custom-alert" class="custom-alert-overlay" style="display: none;">
        <div class="custom-alert-box">
            <div class="custom-alert-icon">
                <i id="custom-alert-icon" class="fas"></i>
            </div>
            <p id="custom-alert-message"></p>
            <button id="custom-alert-close" class="btn btn-primary">好的</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/toast.js"></script>
    <script>
        // 自定义弹窗函数
        function showCustomAlert(message, type = 'info') {
            const customAlert = document.getElementById('custom-alert');
            const customAlertMessage = document.getElementById('custom-alert-message');
            const customAlertIcon = document.getElementById('custom-alert-icon');
            const customAlertClose = document.getElementById('custom-alert-close');
            
            // 设置图标和样式
            const iconMap = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            customAlertIcon.className = `fas ${iconMap[type] || iconMap.info}`;
            customAlertMessage.textContent = message;
            customAlert.style.display = 'flex';
            
            // 设置样式
            customAlert.className = `custom-alert-overlay ${type}`;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const customAlertClose = document.getElementById('custom-alert-close');
            customAlertClose.addEventListener('click', () => {
                document.getElementById('custom-alert').style.display = 'none';
            });
            
            const approveModalEl = document.getElementById('approveModal');
            const rejectModalEl = document.getElementById('rejectModal');
            const deleteModalEl = document.getElementById('deleteModal');

            if (approveModalEl) {
                approveModalEl.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    approveModalEl.querySelector('#approveId').value = id;
                });
            }

            if (rejectModalEl) {
                rejectModalEl.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    rejectModalEl.querySelector('#rejectId').value = id;
                });
            }

            if (deleteModalEl) {
                deleteModalEl.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const id = button.getAttribute('data-id');
                    deleteModalEl.querySelector('#deleteId').value = id;
                });
            }

            // **关键修复**: 只对模态框内的表单进行AJAX处理
            const modalForms = document.querySelectorAll('#approveModal form, #rejectModal form, #deleteModal form');
            modalForms.forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    const modalElement = form.closest('.modal');
                    const modalInstance = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;
                    
                    submitBtn.disabled = true;
                    submitBtn.textContent = '处理中...';
                    
                    try {
                        const response = await fetch(window.location.href, { // 使用当前页面的URL作为action
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const result = await response.json();

                        if (result.success) {
                            const id = formData.get('id');
                            const action = formData.get('action');
                            
                            // **核心逻辑**: 直接更新UI
                            updateTableRow(id, action);
                            
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                            
                            // 使用自定义的toast通知
                            if (window.toast) {
                                window.toast.success(result.message || '操作成功！');
                            } else {
                                showCustomAlert(result.message || '操作成功！', 'success');
                            }
                        } else {
                            throw new Error(result.message || '操作失败，请重试');
                        }
                    } catch (error) {
                        if (window.toast) {
                            window.toast.error(error.message || '网络错误或服务器响应异常');
                        } else {
                            showCustomAlert(error.message || '网络错误或服务器响应异常', 'error');
                        }
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });
            });
        });

        /**
         * 更新表格行的函数 (优化版)
         * @param {string} id - 申请ID
         * @param {string} action - 操作类型 ('approve', 'reject' 或 'delete')
         */
        function updateTableRow(id, action) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (!row) return;

            if (action === 'delete') {
                row.style.transition = 'opacity 0.5s ease';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 500);
                return;
            }

            const statusBadge = row.querySelector('.status-badge');
            const actionButtonsContainer = row.querySelector('.btn-group');

            if (statusBadge) {
                if (action === 'approve') {
                    statusBadge.className = 'badge bg-success status-badge';
                    statusBadge.textContent = '已通过';
                } else if (action === 'reject') {
                    statusBadge.className = 'badge bg-danger status-badge';
                    statusBadge.textContent = '已驳回';
                }
            }
            
            // 移除操作按钮
            if (actionButtonsContainer) {
                actionButtonsContainer.innerHTML = `<a href="application_edit.php?id=${id}" class="btn btn-info">详情</a> <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="${id}">删除</button>`;
            }
        }
    </script>
</body>
</html>
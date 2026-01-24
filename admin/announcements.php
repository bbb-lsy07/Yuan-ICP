<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

// --- START: 新增的核心修复代码 ---
// 这个代码块专门用于处理来自页面JavaScript的后台请求（AJAX）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查这是否是一个AJAX请求
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // 设置响应头为JSON格式
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // 从POST数据中获取操作类型和ID
            $action = $_POST['action'] ?? '';
            $id = intval($_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('无效的公告ID');
            }
            
            $db = db();

            // 根据不同的action执行不同的数据库操作
            switch ($action) {
                case 'delete_announcement':
                    $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
                    $stmt->execute([$id]);
                    echo json_encode(['success' => true, 'message' => '公告已成功删除']);
                    break;
                    
                case 'toggle_announcement_pin':
                    // 使用 NOT 运算符直接反转置顶状态
                    $stmt = $db->prepare("UPDATE announcements SET is_pinned = NOT is_pinned WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    // 获取更新后的置顶状态并返回给前端
                    $stmt = $db->prepare("SELECT is_pinned FROM announcements WHERE id = ?");
                    $stmt->execute([$id]);
                    $is_pinned = $stmt->fetchColumn();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => '置顶状态已更新',
                        'is_pinned' => (bool)$is_pinned // 将结果转为布尔值
                    ]);
                    break;
                    
                default:
                    throw new Exception('无效的操作: ' . htmlspecialchars($action));
            }

        } catch (Exception $e) {
            // 如果发生错误，返回包含错误信息的JSON
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        exit; // 处理完 AJAX 请求后必须退出，防止后续的 HTML 输出
    } else {
        // 对于非AJAX的POST请求（例如浏览器意外提交），重定向回GET页面，防止出错
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}
// --- END: 新增的核心修复代码 ---

// --- 以下是原有的页面加载逻辑，保持不变 ---
$page = max(1, intval($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$perPage = 10;

try {
    $db = db();
    $query = "SELECT * FROM announcements";
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(title LIKE ? OR content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $countQuery = "SELECT COUNT(*) FROM ($query) as total";
    $total = $db->prepare($countQuery);
    $total->execute($params);
    $totalItems = $total->fetchColumn();

    $pagination = new Pagination($page, $totalItems, $perPage, '', $_GET);
    
    $query .= " ORDER BY is_pinned DESC, created_at DESC " . $pagination->getSqlLimit();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll();
    
} catch (Exception $e) {
    handle_error('加载公告列表失败: ' . $e->getMessage());
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
                                    <tr data-id="<?php echo $ann['id']; ?>">
                                        <td>
                                            <button type="button" class="btn btn-sm btn-link" onclick="togglePin(<?php echo $ann['id']; ?>)">
                                                <i class="fas fa-thumbtack <?php echo $ann['is_pinned'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            </button>
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
                                                <a href="announcement_edit.php?id=<?php echo $ann['id']; ?>" class="btn btn-info">编辑</a>
                                                <button type="button" class="btn btn-danger" onclick="deleteAnnouncement(<?php echo $ann['id']; ?>)">删除</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- 分页 -->
                        <div class="pagination-container">
                            <?php echo $pagination->render(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- START: 全新优化的JavaScript代码 ---

        /**
         * 删除公告函数 (AJAX版本)
         * @param {number} id 公告ID
         */
        function deleteAnnouncement(id) {
            if (confirm('确定要删除此公告吗？操作不可恢复！')) {
                // 准备发送到后台的数据
                const formData = new FormData();
                formData.append('id', id);
                formData.append('action', 'delete_announcement');
                
                // 使用 fetch API 发送异步请求
                fetch(window.location.href, { // 请求发送到当前页面
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // 告知后台这是一个AJAX请求
                    }
                })
                .then(response => response.json()) // 将返回结果解析为JSON
                .then(data => {
                    if (data.success) {
                        alert(data.message); // 弹出成功提示
                        // 关键：直接在页面上移除对应的表格行，无需刷新
                        const row = document.querySelector('tr[data-id="' + id + '"]');
                        if (row) {
                            row.style.transition = 'opacity 0.5s ease';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 500);
                        }
                    } else {
                        // 如果失败，弹出错误提示
                        alert('删除失败: ' + data.message);
                    }
                })
                .catch(error => {
                    // 处理网络错误等
                    console.error('删除操作发生错误:', error);
                    alert('删除失败，请检查网络或联系管理员。');
                });
            }
        }
        
        /**
         * 切换置顶状态函数 (AJAX版本)
         * @param {number} id 公告ID
         */
        function togglePin(id) {
            // 准备发送到后台的数据
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'toggle_announcement_pin');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // 关键：直接修改图标样式，无需刷新
                    const button = document.querySelector('tr[data-id="' + id + '"] button[onclick^="togglePin"]');
                    const icon = button.querySelector('i');
                    if (icon) {
                        if (data.is_pinned) {
                            // 设置为置顶样式
                            icon.classList.remove('text-muted');
                            icon.classList.add('text-warning');
                        } else {
                            // 取消置顶样式
                            icon.classList.remove('text-warning');
                            icon.classList.add('text-muted');
                        }
                    }
                    // 重新加载页面以确保排序正确
                    window.location.reload();
                } else {
                    alert('操作失败: ' + data.message);
                }
            })
            .catch(error => {
                console.error('置顶操作发生错误:', error);
                alert('操作失败，请检查网络或联系管理员。');
            });
        }
        // --- END: 全新优化的JavaScript代码 ---
    </script>
</body>
</html>
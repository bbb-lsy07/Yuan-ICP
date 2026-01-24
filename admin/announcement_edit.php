<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查登录状态
require_login();

$db = db();
$announcement = [
    'id' => 0,
    'title' => '',
    'content' => '',
    'is_pinned' => 0
];

// 编辑模式：获取现有公告
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $announcement = $stmt->fetch() ?: $announcement;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的请求，请刷新页面重试';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        
        // 严格验证输入
        if (empty($title)) {
            $error = '标题不能为空';
        } elseif (strlen($title) > 200) {
            $error = '标题不能超过200个字符';
        } elseif (strlen($content) > 10000) {
            $error = '内容不能超过10000个字符';
        } elseif (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\s\-_.,!?()（）]+$/u', $title)) {
            $error = '标题包含非法字符，只允许中文、英文、数字、空格和常用标点符号';
        } else {
            // 过滤HTML标签和特殊字符
            $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
            // 内容允许HTML，但需要清理危险标签
            $content = strip_tags($content, '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><pre><code>');
            
            // 验证链接安全性
            if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
                foreach ($matches[1] as $url) {
                    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $url)) {
                        $error = '链接地址格式不正确，只允许http和https协议';
                        break;
                    }
                }
            }
            
            if (!isset($error)) {
                if ($announcement['id'] > 0) {
                    // 更新现有公告
                    $stmt = $db->prepare("UPDATE announcements SET title = ?, content = ?, is_pinned = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$title, $content, $is_pinned, $announcement['id']]);
                } else {
                    // 新增公告
                    $stmt = $db->prepare("INSERT INTO announcements (title, content, is_pinned, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                    $stmt->execute([$title, $content, $is_pinned]);
                    $announcement['id'] = $db->lastInsertId();
                }
                
                // 重定向到公告列表
                redirect('announcements.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $announcement['id'] ? '编辑' : '新增'; ?>公告 - Yuan-ICP</title>
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
                    <h2><?php echo $announcement['id'] ? '编辑公告' : '新增公告'; ?></h2>
                    <a href="announcements.php" class="btn btn-outline-secondary">返回列表</a>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="card form-container">
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">标题</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                    value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="content" class="form-label">内容</label>
                                <!-- 自定义富文本编辑器 -->
                                <div class="editor-container">
                                    <div class="editor-toolbar">
                                        <button type="button" class="toolbar-button" data-command="bold" title="粗体">
                                            <i class="fas fa-bold"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="italic" title="斜体">
                                            <i class="fas fa-italic"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="underline" title="下划线">
                                            <i class="fas fa-underline"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="insertUnorderedList" title="无序列表">
                                            <i class="fas fa-list-ul"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="insertOrderedList" title="有序列表">
                                            <i class="fas fa-list-ol"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="formatBlock" data-option="<h3>" title="标题">
                                            <i class="fas fa-heading"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="createLink" title="链接">
                                            <i class="fas fa-link"></i>
                                        </button>
                                        <button type="button" class="toolbar-button" data-command="unlink" title="取消链接">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </div>
                                    <div class="editor-content" id="editor" contenteditable="true"><?php 
                                        echo $announcement['content']; 
                                    ?></div>
                                    <input type="hidden" id="content" name="content" value="<?php echo htmlspecialchars($announcement['content']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_pinned" name="is_pinned" 
                                    <?php echo $announcement['is_pinned'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_pinned">置顶显示</label>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">保存公告</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 自定义富文本编辑器功能
        document.addEventListener('DOMContentLoaded', function() {
            const editor = document.getElementById('editor');
            const contentInput = document.getElementById('content');
            const toolbarButtons = document.querySelectorAll('.toolbar-button');
            
            // 更新隐藏输入框的值
            function updateContent() {
                contentInput.value = editor.innerHTML;
            }
            
            // 监听编辑器内容变化
            editor.addEventListener('input', updateContent);
            editor.addEventListener('blur', updateContent);
            
            // 工具栏按钮点击事件
            toolbarButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const command = this.getAttribute('data-command');
                    const option = this.getAttribute('data-option') || null;
                    
                    // 特殊处理链接命令
                    if (command === 'createLink') {
                        const url = prompt('请输入链接地址:');
                        if (url) {
                            document.execCommand(command, false, url);
                        }
                    } else {
                        document.execCommand(command, false, option);
                    }
                    
                    // 更新内容
                    updateContent();
                    
                    // 设置焦点回编辑器
                    editor.focus();
                });
            });
            
            // 初始化内容
            updateContent();
        });
    </script>
</body>
</html>

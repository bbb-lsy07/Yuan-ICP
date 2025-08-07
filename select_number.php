<?php
session_start();
require_once __DIR__.'/includes/functions.php';

// 检查会话中是否存在申请数据，如果没有则返回第一步
if (!isset($_SESSION['application_data'])) {
    header("Location: apply.php");
    exit;
}

$db = db();
$error = '';

// 处理选择号码的表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_number = trim($_POST['number'] ?? '');
    
    if (empty($selected_number)) {
        $error = '请选择一个备案号。';
    } else {
        // 从会话中获取申请数据
        $app_data = $_SESSION['application_data'];
        
        // 开始数据库事务
        $db->beginTransaction();
        
        try {
            // 将所有信息插入到备案申请表
            $stmt = $db->prepare("
                INSERT INTO icp_applications 
                (number, website_name, domain, description, owner_name, owner_email, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $selected_number,
                $app_data['site_name'],
                $app_data['domain'],
                $app_data['description'],
                $app_data['contact_name'],
                $app_data['contact_email'],
            ]);
            
            // 获取刚刚插入的申请ID
            $application_id = $db->lastInsertId();
            
            // 将号码标记为已使用
            $stmt_update = $db->prepare("
                UPDATE selectable_numbers 
                SET status = 'used', 
                    used_by = ?,
                    used_at = CURRENT_TIMESTAMP
                WHERE number = ?
            ");
            $stmt_update->execute([$application_id, $selected_number]);
            
            // 提交事务
            $db->commit();
            
            // 清理会话数据并重定向到结果页
            unset($_SESSION['application_data']);
            header("Location: result.php?application_id=" . $application_id);
            exit;
            
        } catch (Exception $e) {
            // 如果出错则回滚事务
            $db->rollBack();
            $error = '处理您的请求时发生错误，请重试。详情：' . $e->getMessage();
        }
    }
}


// 获取可选号码（已修复RAND()为RANDOM()）
$numbers = $db->query("
    SELECT * FROM selectable_numbers 
    WHERE status = 'available'
    ORDER BY is_premium DESC, RANDOM()
    LIMIT 12
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>选择备案号 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        .number-selector { max-width: 800px; margin: 0 auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .form-header { text-align: center; margin-bottom: 2rem; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .step { text-align: center; flex: 1; position: relative; }
        .step-number { width: 40px; height: 40px; background: #e9ecef; color: #495057; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-weight: bold; border: 2px solid #dee2e6; }
        .step.completed .step-number { background: #28a745; color: white; border-color: #28a745; }
        .step.active .step-number { background: #4a6cf7; color: white; border-color: #4a6cf7; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 20px; left: 70%; width: 60%; height: 2px; background: #ddd; z-index: -1; }
        .number-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .number-card { border: 2px solid #dee2e6; border-radius: 8px; padding: 1rem; text-align: center; cursor: pointer; transition: all 0.3s; }
        .number-card:hover { border-color: #6e8efb; transform: translateY(-3px); }
        .number-card.selected { border-color: #4a6cf7; background-color: #f0f3ff; font-weight: bold; }
        .number-card .number { font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem; }
        .number-card .type { font-size: 0.8rem; color: #6c757d; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container"><a class="navbar-brand" href="index.php">Yuan-ICP</a></div>
    </nav>
    <div class="container my-5">
        <div class="number-selector">
            <div class="step-indicator">
                <div class="step completed"><div class="step-number"><i class="fas fa-check"></i></div><div class="step-title">填写信息</div></div>
                <div class="step active"><div class="step-number">2</div><div class="step-title">选择号码</div></div>
                <div class="step"><div class="step-number">3</div><div class="step-title">完成申请</div></div>
            </div>
            <div class="form-header">
                <h2>选择备案号</h2>
                <p class="text-muted">为您的网站选择一个心仪的备案号</p>
            </div>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" id="number-form">
                <input type="hidden" name="number" id="selected_number">
                
                <?php if (empty($numbers)): ?>
                    <div class="alert alert-warning text-center">
                        <h4 class="alert-heading">无可用号码</h4>
                        <p>当前号码池已空，暂时无法选择备案号。请联系网站管理员进行添加。</p>
                    </div>
                <?php else: ?>
                    <div class="number-grid">
                        <?php foreach ($numbers as $num): ?>
                        <div class="number-card" data-number="<?php echo htmlspecialchars($num['number']); ?>">
                            <div class="number"><?php echo htmlspecialchars($num['number']); ?></div>
                            <div class="type">普通号码</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" <?php if (empty($numbers)) echo 'disabled'; ?>>确认选择并完成申请</button>
                    <a href="apply.php" class="btn btn-outline-secondary">返回修改信息</a>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center"><p class="mb-0">&copy; <?php echo date('Y'); ?> Yuan-ICP. 保留所有权利。</p></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('number-form');
            const submitButton = form.querySelector('button[type="submit"]');
            const hiddenInput = document.getElementById('selected_number');
            const numberCards = document.querySelectorAll('.number-card');

            if (numberCards.length > 0) {
                submitButton.disabled = true; // 初始禁用
            }

            numberCards.forEach(card => {
                card.addEventListener('click', function() {
                    numberCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    hiddenInput.value = this.dataset.number;
                    submitButton.disabled = false; // 启用按钮
                });
            });

            form.addEventListener('submit', function(e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    alert('请先选择一个号码！');
                }
            });
        });
    </script>
</body>
</html>
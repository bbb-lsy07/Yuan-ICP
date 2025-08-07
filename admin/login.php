<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的CSRF令牌';
    } else {
        // 验证登录凭证
        if (login($_POST['username'], $_POST['password'])) {
            // 登录成功，重定向到仪表盘
            redirect('dashboard.php');
        } else {
            $error = '用户名或密码错误';
            // 防止暴力破解：登录失败后延迟响应
            sleep(2);
        }
    }
}

// 生成CSRF令牌
$csrf_token = csrf_token();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuan-ICP 后台登录</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .login-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }
    h1 {
        text-align: center;
        color: #333;
        margin-bottom: 1.5rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    label {
        display: block;
        margin-bottom: 0.5rem;
        color: #555;
    }
    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }
    button {
        width: 100%;
        padding: 0.75rem;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
    }
    button:hover {
        background-color: #45a049;
    }
    .error {
        color: #d32f2f;
        margin-bottom: 1rem;
        text-align: center;
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
    <div class="login-container">
        <h1>Yuan-ICP 后台登录</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>

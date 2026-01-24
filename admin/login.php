<?php
require_once __DIR__.'/../includes/bootstrap.php';

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
            // 注意：IP限制功能已在 auth.php 中实现，无需额外延迟
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
    <link rel="stylesheet" href="css/admin.css"> <!-- 引入主样式 -->
    <link rel="stylesheet" href="css/login.css">  <!-- 引入登录页专属样式 -->
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <h1>Yuan-ICP 后台登录</h1>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">登录</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
session_start();
require_once __DIR__.'/includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的请求，请刷新页面重试。';
    } else {
        $site_name = trim($_POST['site_name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');
    
        if (empty($site_name)) {
            $error = '网站名称不能为空。';
        } elseif (empty($domain)) {
            $error = '网站域名不能为空。';
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的联系邮箱。';
        } else {
            // 【新逻辑】将数据存入会话，而不是数据库
            $_SESSION['application_data'] = [
                'site_name' => $site_name,
                'domain' => $domain,
                'description' => $description,
                'contact_name' => $contact_name,
                'contact_email' => $contact_email,
            ];
            
            // 跳转到号码选择页面
            header("Location: select_number.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<!-- HTML部分保持不变 -->
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请备案 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        .apply-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #e9ecef;
            color: #495057;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            border: 2px solid #dee2e6;
        }
        .step.active .step-number {
            background: #4a6cf7;
            color: white;
            border-color: #4a6cf7;
        }
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 70%;
            width: 60%;
            height: 2px;
            background: #ddd;
            z-index: -1;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Yuan-ICP</a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="apply-form">
            <!-- 步骤指示器 -->
            <div class="step-indicator">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-title">填写信息</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-title">选择号码</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-title">完成申请</div>
                </div>
            </div>
            
            <div class="form-header">
                <h2>填写备案信息</h2>
                <p class="text-muted">请如实填写您的网站和联系人信息。</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-globe me-2"></i>网站信息</h5>
                    
                    <div class="mb-3">
                        <label for="site_name" class="form-label">网站名称</label>
                        <input type="text" class="form-control" id="site_name" name="site_name" 
                            value="<?php echo htmlspecialchars($_POST['site_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="domain" class="form-label">网站域名</label>
                        <div class="input-group">
                            <span class="input-group-text">https://</span>
                            <input type="text" class="form-control" id="domain" name="domain" 
                                placeholder="example.com" 
                                value="<?php echo htmlspecialchars($_POST['domain'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">网站描述 (选填)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php 
                            echo htmlspecialchars($_POST['description'] ?? ''); 
                        ?></textarea>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-user me-2"></i>联系人信息</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_name" class="form-label">联系人姓名 (选填)</label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_email" class="form-label">联系人邮箱</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" 
                                value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">下一步：选择号码</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Yuan-ICP. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
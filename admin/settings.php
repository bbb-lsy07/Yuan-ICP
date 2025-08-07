<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

// 检查登录状态
require_login();

$db = db();
$tab = $_GET['tab'] ?? 'basic';
$message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'basic';
    
    // 根据不同的选项卡处理不同的设置
    switch ($tab) {
        case 'basic':
            $settings = [
                'site_name' => trim($_POST['site_name'] ?? ''),
                'site_url' => trim($_POST['site_url'] ?? ''),
                'timezone' => trim($_POST['timezone'] ?? 'Asia/Shanghai'),
                'icp_prefix' => trim($_POST['icp_prefix'] ?? 'YIC'),
                'icp_digits' => intval($_POST['icp_digits'] ?? 8)
            ];
            break;
            
        case 'seo':
            $settings = [
                'seo_title' => trim($_POST['seo_title'] ?? ''),
                'seo_description' => trim($_POST['seo_description'] ?? ''),
                'seo_keywords' => trim($_POST['seo_keywords'] ?? '')
            ];
            break;
            
        case 'email':
            $settings = [
                'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                'smtp_port' => intval($_POST['smtp_port'] ?? 587),
                'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                'smtp_password' => trim($_POST['smtp_password'] ?? ''),
                'smtp_secure' => trim($_POST['smtp_secure'] ?? 'tls'),
                'from_email' => trim($_POST['from_email'] ?? ''),
                'from_name' => trim($_POST['from_name'] ?? '')
            ];
            break;
            
        case 'numbers':
            $settings = [
                'reserved_numbers' => trim($_POST['reserved_numbers'] ?? ''),
                'sponsor_message' => trim($_POST['sponsor_message'] ?? '')
            ];
            break;
    }
    
    // 保存设置到数据库
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    $message = '设置已保存';
}

// 加载所有设置
$stmt = $db->query("SELECT config_key, config_value FROM system_config");
$allSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 获取时区列表
$timezones = DateTimeZone::listIdentifiers();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - Yuan-ICP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
<style>
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .nav-tabs .nav-link.active {
        font-weight: bold;
    }
    .tab-pane {
        padding: 20px 0;
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
                <h2 class="mb-4">系统设置</h2>
                
                <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <div class="card settings-container">
                    <div class="card-body">
                        <!-- 选项卡导航 -->
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'basic' ? 'active' : ''; ?>" 
                                   href="?tab=basic">基本设置</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'seo' ? 'active' : ''; ?>" 
                                   href="?tab=seo">SEO设置</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'email' ? 'active' : ''; ?>" 
                                   href="?tab=email">邮件设置</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $tab === 'numbers' ? 'active' : ''; ?>" 
                                   href="?tab=numbers">号码池管理</a>
                            </li>
                        </ul>
                        
                        <!-- 选项卡内容 -->
                        <div class="tab-content">
                            <!-- 基本设置 -->
                            <div class="tab-pane <?php echo $tab === 'basic' ? 'show active' : ''; ?>" id="basic">
                                <form method="post">
                                    <input type="hidden" name="tab" value="basic">
                                    
                                    <div class="mb-3">
                                        <label for="site_name" class="form-label">网站名称</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" 
                                            value="<?php echo htmlspecialchars($allSettings['site_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_url" class="form-label">网站URL</label>
                                        <input type="url" class="form-control" id="site_url" name="site_url" 
                                            value="<?php echo htmlspecialchars($allSettings['site_url'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label">时区</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <?php foreach ($timezones as $tz): ?>
                                            <option value="<?php echo $tz; ?>" <?php echo ($allSettings['timezone'] ?? 'Asia/Shanghai') === $tz ? 'selected' : ''; ?>>
                                                <?php echo $tz; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="icp_prefix" class="form-label">备案号前缀</label>
                                            <input type="text" class="form-control" id="icp_prefix" name="icp_prefix" 
                                                value="<?php echo htmlspecialchars($allSettings['icp_prefix'] ?? 'YIC'); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="icp_digits" class="form-label">备案号数字位数</label>
                                            <input type="number" class="form-control" id="icp_digits" name="icp_digits" min="6" max="12"
                                                value="<?php echo htmlspecialchars($allSettings['icp_digits'] ?? 8); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">保存设置</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- SEO设置 -->
                            <div class="tab-pane <?php echo $tab === 'seo' ? 'show active' : ''; ?>" id="seo">
                                <form method="post">
                                    <input type="hidden" name="tab" value="seo">
                                    
                                    <div class="mb-3">
                                        <label for="seo_title" class="form-label">首页标题(Title)</label>
                                        <input type="text" class="form-control" id="seo_title" name="seo_title" 
                                            value="<?php echo htmlspecialchars($allSettings['seo_title'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="seo_description" class="form-label">首页描述(Description)</label>
                                        <textarea class="form-control" id="seo_description" name="seo_description" rows="3"><?php 
                                            echo htmlspecialchars($allSettings['seo_description'] ?? ''); 
                                        ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="seo_keywords" class="form-label">首页关键词(Keywords)</label>
                                        <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" 
                                            value="<?php echo htmlspecialchars($allSettings['seo_keywords'] ?? ''); ?>">
                                        <div class="form-text">多个关键词用英文逗号分隔</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">保存设置</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 邮件设置 -->
                            <div class="tab-pane <?php echo $tab === 'email' ? 'show active' : ''; ?>" id="email">
                                <form method="post">
                                    <input type="hidden" name="tab" value="email">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_host" class="form-label">SMTP服务器</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                value="<?php echo htmlspecialchars($allSettings['smtp_host'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_port" class="form-label">SMTP端口</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                value="<?php echo htmlspecialchars($allSettings['smtp_port'] ?? 587); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_username" class="form-label">SMTP用户名</label>
                                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                                value="<?php echo htmlspecialchars($allSettings['smtp_username'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_password" class="form-label">SMTP密码</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                value="<?php echo htmlspecialchars($allSettings['smtp_password'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="smtp_secure" class="form-label">加密方式</label>
                                            <select class="form-select" id="smtp_secure" name="smtp_secure">
                                                <option value="">无</option>
                                                <option value="tls" <?php echo ($allSettings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo ($allSettings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="from_email" class="form-label">发件人邮箱</label>
                                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                                value="<?php echo htmlspecialchars($allSettings['from_email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="from_name" class="form-label">发件人名称</label>
                                            <input type="text" class="form-control" id="from_name" name="from_name" 
                                                value="<?php echo htmlspecialchars($allSettings['from_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">保存设置</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 号码池管理 -->
                            <div class="tab-pane <?php echo $tab === 'numbers' ? 'show active' : ''; ?>" id="numbers">
                                <form method="post">
                                    <input type="hidden" name="tab" value="numbers">
                                    
                                    <div class="mb-3">
                                        <label for="reserved_numbers" class="form-label">保留号码</label>
                                        <textarea class="form-control" id="reserved_numbers" name="reserved_numbers" rows="5"><?php 
                                            echo htmlspecialchars($allSettings['reserved_numbers'] ?? ''); 
                                        ?></textarea>
                                        <div class="form-text">每行一个号码，这些号码将不会被自动分配</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="sponsor_message" class="form-label">赞助说明</label>
                                        <textarea class="form-control" id="sponsor_message" name="sponsor_message" rows="3"><?php 
                                            echo htmlspecialchars($allSettings['sponsor_message'] ?? ''); 
                                        ?></textarea>
                                        <div class="form-text">当用户选择赞助号码时显示的说明信息</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">保存设置</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 切换选项卡时更新URL
        document.querySelectorAll('.nav-tabs .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    window.location.href = this.getAttribute('href');
                }
                e.preventDefault();
            });
        });
    </script>
</body>
</html>

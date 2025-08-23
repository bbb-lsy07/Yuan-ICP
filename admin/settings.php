<?php
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

require_login();

$db = db();
$tab = $_GET['tab'] ?? 'basic';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';
    $tab = $_POST['tab'] ?? 'basic'; // Ensure tab stays correct after POST

    if ($action === 'add_numbers') {
        $numbers_str = trim($_POST['new_numbers'] ?? '');
        $is_premium = isset($_POST['is_premium']) ? 1 : 0;
        
        if (empty($numbers_str)) {
            $error = '号码列表不能为空。';
        } else {
            $numbers = array_filter(array_map('trim', explode("\n", $numbers_str)));
            $added_count = 0;
            $skipped_count = 0;
            $stmt = $db->prepare("INSERT OR IGNORE INTO selectable_numbers (number, is_premium, status) VALUES (?, ?, 'available')");
            
            foreach ($numbers as $number) {
                if ($stmt->execute([$number, $is_premium])) {
                    if ($stmt->rowCount() > 0) {
                        $added_count++;
                    } else {
                        $skipped_count++;
                    }
                }
            }
            $message = "操作完成：成功添加 {$added_count} 个号码，跳过 {$skipped_count} 个重复号码。";
        }
    } elseif ($action === 'save_settings') {
        $settings = [];
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
                    'number_auto_generate' => isset($_POST['number_auto_generate']) ? 1 : 0,
                    'number_generate_format' => trim($_POST['number_generate_format'] ?? ''),
                    'reserved_numbers' => trim($_POST['reserved_numbers'] ?? ''),
                    'sponsor_message' => trim($_POST['sponsor_message'] ?? '')
                ];
                break;
        }
        
        if (!empty($settings)) {
            $stmt = $db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            $message = '设置已成功保存！';
        }
    }
}

$stmt = $db->query("SELECT config_key, config_value FROM system_config");
$allSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
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
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .settings-container { max-width: 900px; margin: 0 auto; }
        .nav-tabs .nav-link.active { font-weight: bold; }
        .tab-content { padding-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include __DIR__.'/../includes/admin_sidebar.php'; ?>
            </div>
            
            <div class="col-md-10 main-content">
                <h2 class="mb-4">系统设置</h2>
                
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <div class="card settings-container">
                    <div class="card-body">
                        <ul class="nav nav-tabs">
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'basic') echo 'active'; ?>" href="?tab=basic">基本设置</a></li>
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'seo') echo 'active'; ?>" href="?tab=seo">SEO设置</a></li>
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'email') echo 'active'; ?>" href="?tab=email">邮件设置</a></li>
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'numbers') echo 'active'; ?>" href="?tab=numbers">号码池设置</a></li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- 基本设置 -->
                            <div class="tab-pane fade <?php if($tab === 'basic') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings">
                                    <input type="hidden" name="tab" value="basic">
                                    <div class="mb-3"><label for="site_name" class="form-label">网站名称</label><input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($allSettings['site_name'] ?? ''); ?>"></div>
                                    <div class="mb-3"><label for="site_url" class="form-label">网站URL</label><input type="url" class="form-control" id="site_url" name="site_url" value="<?php echo htmlspecialchars($allSettings['site_url'] ?? ''); ?>"></div>
                                    <div class="mb-3"><label for="timezone" class="form-label">时区</label><select class="form-select" id="timezone" name="timezone"><?php foreach ($timezones as $tz): ?><option value="<?php echo $tz; ?>" <?php echo ($allSettings['timezone'] ?? 'Asia/Shanghai') === $tz ? 'selected' : ''; ?>><?php echo $tz; ?></option><?php endforeach; ?></select></div>
                                    <div class="row"><div class="col-md-6 mb-3"><label for="icp_prefix" class="form-label">备案号前缀</label><input type="text" class="form-control" id="icp_prefix" name="icp_prefix" value="<?php echo htmlspecialchars($allSettings['icp_prefix'] ?? 'YIC'); ?>"></div><div class="col-md-6 mb-3"><label for="icp_digits" class="form-label">备案号数字位数</label><input type="number" class="form-control" id="icp_digits" name="icp_digits" min="6" max="12" value="<?php echo htmlspecialchars($allSettings['icp_digits'] ?? 8); ?>"></div></div>
                                    <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
                                </form>
                            </div>

                            <!-- SEO设置 -->
                            <div class="tab-pane fade <?php if($tab === 'seo') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings">
                                    <input type="hidden" name="tab" value="seo">
                                    <div class="mb-3"><label for="seo_title" class="form-label">首页标题(Title)</label><input type="text" class="form-control" id="seo_title" name="seo_title" value="<?php echo htmlspecialchars($allSettings['seo_title'] ?? ''); ?>"></div>
                                    <div class="mb-3"><label for="seo_description" class="form-label">首页描述(Description)</label><textarea class="form-control" id="seo_description" name="seo_description" rows="3"><?php echo htmlspecialchars($allSettings['seo_description'] ?? ''); ?></textarea></div>
                                    <div class="mb-3"><label for="seo_keywords" class="form-label">首页关键词(Keywords)</label><input type="text" class="form-control" id="seo_keywords" name="seo_keywords" value="<?php echo htmlspecialchars($allSettings['seo_keywords'] ?? ''); ?>"><div class="form-text">多个关键词用英文逗号分隔</div></div>
                                    <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
                                </form>
                            </div>

                            <!-- 邮件设置 -->
                            <div class="tab-pane fade <?php if($tab === 'email') echo 'show active'; ?>">
                                <form method="post">
                                     <input type="hidden" name="action" value="save_settings">
                                     <input type="hidden" name="tab" value="email">
                                     <div class="row"><div class="col-md-6 mb-3"><label for="smtp_host" class="form-label">SMTP服务器</label><input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($allSettings['smtp_host'] ?? ''); ?>"></div><div class="col-md-6 mb-3"><label for="smtp_port" class="form-label">SMTP端口</label><input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($allSettings['smtp_port'] ?? 587); ?>"></div></div>
                                     <div class="row"><div class="col-md-6 mb-3"><label for="smtp_username" class="form-label">SMTP用户名</label><input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($allSettings['smtp_username'] ?? ''); ?>"></div><div class="col-md-6 mb-3"><label for="smtp_password" class="form-label">SMTP密码</label><input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($allSettings['smtp_password'] ?? ''); ?>"></div></div>
                                     <div class="row"><div class="col-md-6 mb-3"><label for="smtp_secure" class="form-label">加密方式</label><select class="form-select" id="smtp_secure" name="smtp_secure"><option value="">无</option><option value="tls" <?php echo ($allSettings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option><option value="ssl" <?php echo ($allSettings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option></select></div></div>
                                     <div class="row"><div class="col-md-6 mb-3"><label for="from_email" class="form-label">发件人邮箱</label><input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($allSettings['from_email'] ?? ''); ?>"></div><div class="col-md-6 mb-3"><label for="from_name" class="form-label">发件人名称</label><input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($allSettings['from_name'] ?? ''); ?>"></div></div>
                                     <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
                                </form>
                            </div>
                            
                            <!-- 号码池设置 -->
                            <div class="tab-pane fade <?php if($tab === 'numbers') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings">
                                    <input type="hidden" name="tab" value="numbers">
                                    <h5 class="mb-3">自动/手动模式设置</h5>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="number_auto_generate" name="number_auto_generate" value="1" <?php echo !empty($allSettings['number_auto_generate']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="number_auto_generate">开启自动生成备案号</label>
                                        <div class="form-text">开启后，前台将根据下方规则自动生成号码。关闭后，则从手动添加的号码池中随机选择。</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="number_generate_format" class="form-label">自动生成格式</label>
                                        <input type="text" class="form-control" id="number_generate_format" name="number_generate_format" value="<?php echo htmlspecialchars($allSettings['number_generate_format'] ?? 'Yuan{U}{U}{N}{N}{N}{N}{N}{N}'); ?>">
                                        <div class="form-text">规则: <code>{N}</code>=数字, <code>{U}</code>=大写字母, <code>{L}</code>=小写字母。</div>
                                    </div>
                                    <hr class="my-4">
                                    <h5 class="mb-3">通用设置</h5>
                                    <div class="mb-3">
                                        <label for="reserved_numbers" class="form-label">保留号码</label>
                                        <textarea class="form-control" id="reserved_numbers" name="reserved_numbers" rows="5" placeholder="每行一个号码..."><?php echo htmlspecialchars($allSettings['reserved_numbers'] ?? ''); ?></textarea>
                                        <div class="form-text">这些号码将不会被自动生成或分配。</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="sponsor_message" class="form-label">靓号/赞助说明</label>
                                        <textarea class="form-control" id="sponsor_message" name="sponsor_message" rows="3"><?php echo htmlspecialchars($allSettings['sponsor_message'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">保存设置</button>
                                    </div>
                                </form>

                                <hr class="my-4">
                                
                                <h5 class="mb-3">添加号码到号码池</h5>
                                <p class="text-muted small">当“自动生成备案号”关闭时，系统会从此号码池中为用户提供可选号码。您可以在 <a href="numbers.php">号码池列表</a> 中管理已添加的号码。</p>
                                <form method="post">
                                     <input type="hidden" name="tab" value="numbers">
                                     <input type="hidden" name="action" value="add_numbers">
                                     <div class="mb-3">
                                         <label for="new_numbers" class="form-label">号码列表</label>
                                         <textarea class="form-control" id="new_numbers" name="new_numbers" rows="8" placeholder="每行一个号码..."></textarea>
                                         <div class="form-text">每行输入一个号码。系统会自动忽略已存在的重复号码。</div>
                                     </div>
                                     <div class="mb-3 form-check">
                                         <input type="checkbox" class="form-check-input" id="is_premium" name="is_premium" value="1">
                                         <label class="form-check-label" for="is_premium">将这些号码标记为靓号</label>
                                     </div>
                                     <div class="d-flex justify-content-end">
                                         <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i> 添加到号码池</button>
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
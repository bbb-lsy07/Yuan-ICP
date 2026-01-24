<?php
require_once __DIR__.'/../includes/bootstrap.php';

require_login();

// 检查系统设置权限
if (!has_permission('system.settings')) {
    handle_error('您没有权限访问系统设置页面', true, 403);
}

$db = db();
$tab = $_GET['tab'] ?? 'basic';
$message = '';
$error = '';
$upload_dir = __DIR__.'/../uploads/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 处理添加号码的逻辑
        if (isset($_POST['action']) && $_POST['action'] === 'add_numbers') {
            $numbers_to_add = trim($_POST['numbers_to_add'] ?? '');
            $is_premium = isset($_POST['is_premium']) ? 1 : 0;
            
            if (empty($numbers_to_add)) {
                throw new Exception('号码列表不能为空。');
            }

            $numbers = array_filter(array_map('trim', explode("\n", $numbers_to_add)));
            $added_count = 0;
            
            $stmt = $db->prepare("INSERT OR IGNORE INTO selectable_numbers (number, is_premium) VALUES (?, ?)");
            foreach ($numbers as $number) {
                if (!empty($number)) {
                    $stmt->execute([$number, $is_premium]);
                    if ($stmt->rowCount() > 0) {
                        $added_count++;
                    }
                }
            }
            $message = "成功添加 {$added_count} 个新号码。";
        }
        
        $action = $_POST['action'] ?? 'save_settings';
        $tab = $_POST['tab'] ?? 'basic';

        if ($action === 'save_settings') {
            $settings = [];
            switch ($tab) {
                case 'basic':
                    $settings = [
                        'site_name' => trim($_POST['site_name'] ?? ''),
                        'site_url' => trim($_POST['site_url'] ?? ''),
                        'timezone' => trim($_POST['timezone'] ?? 'Asia/Shanghai'),
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
                        'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
                        'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                        'smtp_port' => intval($_POST['smtp_port'] ?? 587),
                        'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                        'smtp_password' => trim($_POST['smtp_password'] ?? ''),
                        'smtp_secure' => trim($_POST['smtp_secure'] ?? 'tls'),
                        'from_email' => trim($_POST['from_email'] ?? ''),
                        'from_name' => trim($_POST['from_name'] ?? '')
                    ];
                    break;
                case 'notification':
                    $settings = [
                        'dingtalk_enabled' => isset($_POST['dingtalk_enabled']) ? '1' : '0',
                        'dingtalk_webhook' => trim($_POST['dingtalk_webhook'] ?? '')
                    ];
                    break;
                case 'numbers':
                    $settings = [
                        'number_auto_generate' => isset($_POST['number_auto_generate']) ? 1 : 0,
                        'number_generate_format' => trim($_POST['number_generate_format'] ?? ''),
                        'reserved_numbers' => trim($_POST['reserved_numbers'] ?? ''),
                    ];
                    break;
                case 'sponsorship': // 新增的处理逻辑
                    $settings = [
                        'sponsorship_amount' => trim($_POST['sponsorship_amount'] ?? '10.00'),
                        'sponsorship_instructions' => trim($_POST['sponsorship_instructions'] ?? '感谢您选择靓号！您的赞助是对我们最大的支持。请扫描下方二维码完成赞助，并在下方填写您的付款平台和订单号以便我们进行核对。'),
                        'wechat_payment_enabled' => isset($_POST['wechat_payment_enabled']) ? 1 : 0,
                        'alipay_payment_enabled' => isset($_POST['alipay_payment_enabled']) ? 1 : 0,
                    ];
                    // 处理微信收款码上传
                    if (isset($_FILES['wechat_qr']) && $_FILES['wechat_qr']['error'] === UPLOAD_ERR_OK) {
                        $target_file = $upload_dir . 'wechat_qr.png';
                        if (move_uploaded_file($_FILES['wechat_qr']['tmp_name'], $target_file)) {
                            $settings['wechat_qr_enabled'] = '1';
                        }
                    }
                    // 处理支付宝收款码上传
                    if (isset($_FILES['alipay_qr']) && $_FILES['alipay_qr']['error'] === UPLOAD_ERR_OK) {
                        $target_file = $upload_dir . 'alipay_qr.png';
                        if (move_uploaded_file($_FILES['alipay_qr']['tmp_name'], $target_file)) {
                            $settings['alipay_qr_enabled'] = '1';
                        }
                    }
                    break;
                case 'footer':
                    $settings = [
                        'footer_copyright' => trim($_POST['footer_copyright'] ?? ''),
                        'show_footer_icp' => isset($_POST['show_footer_icp']) ? '1' : '0',
                        'footer_icp_beian' => trim($_POST['footer_icp_beian'] ?? ''),
                        'footer_icp_link' => trim($_POST['footer_icp_link'] ?? ''),
                        'show_footer_gongan' => isset($_POST['show_footer_gongan']) ? '1' : '0',
                        'footer_gongan_beian' => trim($_POST['footer_gongan_beian'] ?? ''),
                        'footer_gongan_link' => trim($_POST['footer_gongan_link'] ?? '')
                    ];
                    break;
                case 'rss_widget_save': // 新增的处理逻辑
                    $settings = [
                        'rss_widget_enabled' => isset($_POST['rss_widget_enabled']) ? '1' : '0',
                        'rss_widget_title'   => trim($_POST['rss_widget_title'] ?? ''),
                        'rss_widget_count'   => trim($_POST['rss_widget_count'] ?? '10'),
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
    } catch (Exception $e) {
        $error = '保存设置失败: ' . $e->getMessage();
    }
}

$allSettings = get_config();
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
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'notification') echo 'active'; ?>" href="?tab=notification">通知设置</a></li>
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'numbers') echo 'active'; ?>" href="?tab=numbers">号码池设置</a></li>
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'sponsorship') echo 'active'; ?>" href="?tab=sponsorship">赞助设置</a></li>
                            <li class="nav-item"><a class="nav-link <?php if($tab === 'footer') echo 'active'; ?>" href="?tab=footer">页脚设置</a></li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- 基本设置 -->
                            <div class="tab-pane fade <?php if($tab === 'basic') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings"><input type="hidden" name="tab" value="basic">
                                    <div class="mb-3"><label for="site_name" class="form-label">网站名称</label><input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($allSettings['site_name'] ?? ''); ?>"></div>
                                    <div class="mb-3"><label for="site_url" class="form-label">网站URL</label><input type="url" class="form-control" id="site_url" name="site_url" value="<?php echo htmlspecialchars($allSettings['site_url'] ?? ''); ?>"></div>
                                    <div class="mb-3"><label for="timezone" class="form-label">时区</label><input type="text" class="form-control" id="timezone" name="timezone" value="<?php echo htmlspecialchars($allSettings['timezone'] ?? 'Asia/Shanghai'); ?>"></div>
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
                                     
                                     <!-- 邮件总开关 -->
                                     <div class="form-check form-switch mb-4">
                                         <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" value="1" <?php echo !empty($allSettings['email_enabled']) ? 'checked' : ''; ?>>
                                         <label class="form-check-label" for="email_enabled"><strong>启用邮件通知功能</strong></label>
                                         <div class="form-text">关闭后，系统将不会发送任何邮件（包括审核通知和测试邮件）。</div>
                                     </div>
                                     <hr>
                                     
                                     <div class="row"><div class="col-md-6 mb-3"><label for="smtp_host" class="form-label">SMTP服务器</label><input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($allSettings['smtp_host'] ?? ''); ?>"></div><div class="col-md-6 mb-3"><label for="smtp_port" class="form-label">SMTP端口</label><input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($allSettings['smtp_port'] ?? 587); ?>"></div></div>
                                     <div class="row"><div class="col-md-6 mb-3"><label for="smtp_username" class="form-label">SMTP用户名</label><input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($allSettings['smtp_username'] ?? ''); ?>"></div><div class="col-md-6 mb-3"><label for="smtp_password" class="form-label">SMTP密码</label><input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($allSettings['smtp_password'] ?? ''); ?>"></div></div>
                                     <div class="row"><div class="col-md-6 mb-3"><label for="smtp_secure" class="form-label">加密方式</label><select class="form-select" id="smtp_secure" name="smtp_secure"><option value="">无</option><option value="tls" <?php echo ($allSettings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option><option value="ssl" <?php echo ($allSettings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option></select></div></div>
                                     <div class="row"><div class="col-md-6 mb-3"><label for="from_email" class="form-label">发件人邮箱</label><input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($allSettings['from_email'] ?? ''); ?>"></div><div class="col-md-6 mb-3"><label for="from_name" class="form-label">发件人名称</label><input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($allSettings['from_name'] ?? ''); ?>"></div></div>
                                     
                                     <!-- 邮件测试区域 -->
                                     <div class="alert alert-info">
                                         <h6><i class="fas fa-info-circle me-2"></i>邮件配置测试</h6>
                                         <p class="mb-2">配置完成后，您可以发送一封测试邮件来验证SMTP设置是否正确。</p>
                                         <button type="button" class="btn btn-outline-primary btn-sm" id="testEmailBtn">
                                             <i class="fas fa-paper-plane me-1"></i>发送测试邮件
                                         </button>
                                         <div id="testEmailResult" class="mt-2" style="display: none;"></div>
                                     </div>
                                     
                                     <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
                                </form>
                            </div>

                            <!-- 通知设置 -->
                            <div class="tab-pane fade <?php if($tab === 'notification') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings">
                                    <input type="hidden" name="tab" value="notification">
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> 配置消息推送服务，以便管理员及时收到新的备案申请通知。
                                    </div>
                                    
                                    <h5 class="mb-3">钉钉机器人通知</h5>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="dingtalk_enabled" name="dingtalk_enabled" value="1" <?php echo ($allSettings['dingtalk_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="dingtalk_enabled">启用钉钉通知</label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="dingtalk_webhook" class="form-label">Webhook 地址</label>
                                        <input type="url" class="form-control" id="dingtalk_webhook" name="dingtalk_webhook" value="<?php echo htmlspecialchars($allSettings['dingtalk_webhook'] ?? ''); ?>" placeholder="https://oapi.dingtalk.com/robot/send?access_token=...">
                                        <div class="form-text">请在钉钉群设置中添加自定义机器人，并将生成的 Webhook 地址粘贴到此处。安全设置请勾选"自定义关键词"，并包含关键词"备案"。</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
                                </form>
                            </div>
                            
                            <!-- 号码池设置 -->
                            <div class="tab-pane fade <?php if($tab === 'numbers') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings"><input type="hidden" name="tab" value="numbers">
                                    <h5 class="mb-3">号码生成模式</h5>
                                    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="number_auto_generate" name="number_auto_generate" value="1" <?php echo !empty($allSettings['number_auto_generate']) ? 'checked' : ''; ?>><label class="form-check-label" for="number_auto_generate">开启自动生成号码</label><div class="form-text">开启后，系统将按规则生成号码。关闭后，将从手动添加的号码池中选择。</div></div>
                                    <div class="mb-3"><label for="number_generate_format" class="form-label">自动生成格式</label><input type="text" class="form-control" id="number_generate_format" name="number_generate_format" value="<?php echo htmlspecialchars($allSettings['number_generate_format'] ?? 'Yuan{U}{U}{N}{N}{N}{N}'); ?>"><div class="form-text">规则: <code>{N}</code>=数字, <code>{U}</code>=大写字母, <code>{L}</code>=小写字母。</div></div>
                                    <hr class="my-4">
                                    <h5 class="mb-3">保留号码</h5>
                                    <div class="mb-3"><label for="reserved_numbers" class="form-label">保留号码列表</label><textarea class="form-control" id="reserved_numbers" name="reserved_numbers" rows="5" placeholder="每行一个号码..."><?php echo htmlspecialchars($allSettings['reserved_numbers'] ?? ''); ?></textarea><div class="form-text">这些号码将不会被自动生成或分配。</div></div>
                                    <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
                                </form>
                                
                                <!-- 手动添加号码 -->
                                <?php if (empty($allSettings['number_auto_generate'])): ?>
                                <hr class="my-4">
                                <h5 class="mb-3">手动添加号码</h5>
                                <form method="post">
                                    <input type="hidden" name="tab" value="numbers">
                                    <input type="hidden" name="action" value="add_numbers">
                                    <div class="mb-3">
                                        <label for="numbers_to_add" class="form-label">号码列表 (每行一个)</label>
                                        <textarea class="form-control" name="numbers_to_add" id="numbers_to_add" rows="8" required></textarea>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_premium" name="is_premium">
                                        <label class="form-check-label" for="is_premium">将这些号码标记为靓号</label>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                         <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> 批量添加</button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-info-circle"></i> 当前已开启号码自动生成模式。如需手动添加号码，请先关闭自动生成。
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- 赞助设置 -->
                            <div class="tab-pane fade <?php if($tab === 'sponsorship') echo 'show active'; ?>">
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="save_settings"><input type="hidden" name="tab" value="sponsorship">
                                    <div class="mb-3"><label for="sponsorship_amount" class="form-label">赞助金额 (元)</label><input type="number" step="0.01" class="form-control" id="sponsorship_amount" name="sponsorship_amount" value="<?php echo htmlspecialchars($allSettings['sponsorship_amount'] ?? '10.00'); ?>"></div>
                                    <div class="mb-3"><label for="sponsorship_instructions" class="form-label">赞助说明文字</label><textarea class="form-control" id="sponsorship_instructions" name="sponsorship_instructions" rows="4"><?php echo htmlspecialchars($allSettings['sponsorship_instructions'] ?? '感谢您选择靓号！您的赞助是对我们最大的支持。请扫描下方二维码完成赞助，并在下方填写您的付款平台和订单号以便我们进行核对。'); ?></textarea></div>
                                    
                                    <!-- 支付方式开关 -->
                                    <h5 class="mt-4">支付方式启用/关闭</h5>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="wechat_payment_enabled" name="wechat_payment_enabled" value="1" <?php echo !empty($allSettings['wechat_payment_enabled']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="wechat_payment_enabled">启用微信支付</label>
                                    </div>
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="alipay_payment_enabled" name="alipay_payment_enabled" value="1" <?php echo !empty($allSettings['alipay_payment_enabled']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="alipay_payment_enabled">启用支付宝支付</label>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="wechat_qr" class="form-label">微信收款码</label>
                                            <input class="form-control" type="file" name="wechat_qr" id="wechat_qr" accept="image/png">
                                            <?php if (file_exists($upload_dir . 'wechat_qr.png')): ?><img src="/uploads/wechat_qr.png?t=<?php echo time(); ?>" class="img-thumbnail mt-2" style="max-width: 150px;"><?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="alipay_qr" class="form-label">支付宝收款码</label>
                                            <input class="form-control" type="file" name="alipay_qr" id="alipay_qr" accept="image/png">
                                            <?php if (file_exists($upload_dir . 'alipay_qr.png')): ?><img src="/uploads/alipay_qr.png?t=<?php echo time(); ?>" class="img-thumbnail mt-2" style="max-width: 150px;"><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存赞助设置</button></div>
                                </form>
                            </div>

                            <!-- 页脚设置 -->
                            <div class="tab-pane fade <?php if($tab === 'footer') echo 'show active'; ?>">
                                <form method="post">
                                    <input type="hidden" name="action" value="save_settings">
                                    <input type="hidden" name="tab" value="footer">
                                    <h5 class="mb-3">页脚信息配置</h5>
                                    <div class="mb-3">
                                        <label for="footer_copyright" class="form-label">版权信息</label>
                                        <input type="text" class="form-control" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars($allSettings['footer_copyright'] ?? '版权所有 © ' . date('Y') . ' ' . ($allSettings['site_name'] ?? 'Yuan-ICP')); ?>">
                                        <div class="form-text">例如：版权所有 © 2025 Yuan-ICP</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">ICP备案号</label>
                                            <div class="input-group">
                                                <div class="input-group-text">
                                                    <input class="form-check-input mt-0" type="checkbox" name="show_footer_icp" value="1" <?php echo ($allSettings['show_footer_icp'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                </div>
                                                <input type="text" class="form-control" name="footer_icp_beian" value="<?php echo htmlspecialchars($allSettings['footer_icp_beian'] ?? ''); ?>">
                                            </div>
                                            <div class="form-text">勾选左侧框以在页脚显示。</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="footer_icp_link" class="form-label">ICP备案链接</label>
                                            <input type="url" class="form-control" id="footer_icp_link" name="footer_icp_link" value="<?php echo htmlspecialchars($allSettings['footer_icp_link'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">公网安备号</label>
                                            <div class="input-group">
                                                <div class="input-group-text">
                                                    <input class="form-check-input mt-0" type="checkbox" name="show_footer_gongan" value="1" <?php echo ($allSettings['show_footer_gongan'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                </div>
                                                <input type="text" class="form-control" name="footer_gongan_beian" value="<?php echo htmlspecialchars($allSettings['footer_gongan_beian'] ?? ''); ?>">
                                            </div>
                                            <div class="form-text">勾选左侧框以在页脚显示。</div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="footer_gongan_link" class="form-label">公网安备链接</label>
                                            <input type="url" class="form-control" id="footer_gongan_link" name="footer_gongan_link" value="<?php echo htmlspecialchars($allSettings['footer_gongan_link'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end"><button type="submit" class="btn btn-primary">保存设置</button></div>
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

        // 邮件测试功能
        document.addEventListener('DOMContentLoaded', function() {
            const testEmailBtn = document.getElementById('testEmailBtn');
            const testEmailResult = document.getElementById('testEmailResult');
            
            if (testEmailBtn) {
                testEmailBtn.addEventListener('click', function() {
                    // 禁用按钮，显示加载状态
                    testEmailBtn.disabled = true;
                    testEmailBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>发送中...';
                    testEmailResult.style.display = 'none';
                    
                    // 准备请求数据
                    const formData = new FormData();
                    formData.append('csrf_token', '<?php echo csrf_token(); ?>');
                    
                    // 发送AJAX请求
                    fetch('../api/send_test_email.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // 恢复按钮状态
                        testEmailBtn.disabled = false;
                        testEmailBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>发送测试邮件';
                        
                        // 显示结果
                        testEmailResult.style.display = 'block';
                        
                        if (data.success) {
                            testEmailResult.innerHTML = `
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>发送成功！</strong> ${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                        } else {
                            testEmailResult.innerHTML = `
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>发送失败！</strong> ${data.error}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        // 恢复按钮状态
                        testEmailBtn.disabled = false;
                        testEmailBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>发送测试邮件';
                        
                        // 显示错误
                        testEmailResult.style.display = 'block';
                        testEmailResult.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>请求失败！</strong> 网络错误或服务器无响应
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                        console.error('Test email error:', error);
                    });
                });
            }
        });
    </script>
</body>
</html>

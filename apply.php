<?php
// apply.php
require_once __DIR__.'/includes/bootstrap.php';

$db = db();

// 处理表单提交 (AJAX的后备方案)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = '无效的请求，请刷新页面重试。';
    } else {
        // 基础清理
        $site_name = sanitizeInput($_POST['site_name'] ?? '');
        $domain = sanitizeInput($_POST['domain'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $contact_name = sanitizeInput($_POST['contact_name'] ?? ''); // 可选
        $contact_email = sanitizeInput($_POST['contact_email'] ?? '');

        // 规范域名（移除协议/末尾斜杠）
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = rtrim($domain, "/");

        // 校验必填项（联系人姓名为可选）
        if (empty($site_name) || empty($domain) || empty($contact_email)) {
            $error = '网站名称、域名和联系邮箱为必填项。';
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的联系邮箱。';
        } elseif (!isValidDomain($domain)) {
            $error = '请输入有效的域名（例如 example.com）。';
        } else {
            // 保存数据到会话并跳转
            $_SESSION['application_data'] = [
                'site_name' => $site_name,
                'domain' => $domain,
                'description' => $description,
                'contact_name' => $contact_name,
                'contact_email' => $contact_email,
            ];
            header("Location: select_number.php");
            exit;
        }
    }
}

$config = get_config();

// 准备数据
$data = [
    'config' => $config,
    'error' => $error ?? '',
    'page_title' => '申请备案 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'apply',
    'page_scripts' => ['/js/apply-form.js'],
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('apply', $data);
ThemeManager::render('footer', $data);

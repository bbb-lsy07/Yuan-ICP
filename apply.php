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
        $site_name = trim($_POST['site_name'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contact_name = trim($_POST['contact_name'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');

        if (empty($site_name) || empty($domain) || empty($contact_name) || empty($contact_email)) {
            $error = '所有字段均为必填项。';
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的联系邮箱。';
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
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('apply', $data);
ThemeManager::render('footer', $data);

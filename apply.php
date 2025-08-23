<?php
session_start();
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/theme_manager.php';

$db = db();
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
            $_SESSION['application_data'] = [
                'site_name' => $site_name, 'domain' => $domain, 'description' => $description,
                'contact_name' => $contact_name, 'contact_email' => $contact_email,
            ];
            header("Location: select_number.php");
            exit;
        }
    }
}

// 加载系统配置
$stmt_config = $db->query("SELECT config_key, config_value FROM system_config");
$config = $stmt_config->fetchAll(PDO::FETCH_KEY_PAIR);

// 准备传递给模板的数据
$data = [
    'config' => $config,
    'error' => $error,
    'page_title' => '申请备案 - ' . ($config['site_name'] ?? 'Yuan-ICP'),
    'active_page' => 'apply'
];

// 渲染页面
ThemeManager::render('header', $data);
ThemeManager::render('apply', $data);
ThemeManager::render('footer', $data);

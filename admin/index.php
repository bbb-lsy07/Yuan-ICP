<?php
// 防止直接访问
if (!defined('YICP_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// 检查是否已安装
require_once __DIR__.'/../includes/bootstrap.php';
if (!is_installed()) {
    header('Location: /install/step1.php');
    exit;
}

// 检查管理员登录
if (!is_admin_logged_in()) {
    header('Location: /admin/login.php');
    exit;
}

// 重定向到仪表盘
header('Location: /admin/dashboard.php');

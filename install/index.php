<?php
// 防止直接访问
if (!defined('YICP_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// 重定向到安装向导第一步
header('Location: /install/step1.php');

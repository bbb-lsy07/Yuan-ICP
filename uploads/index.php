<?php
// 防止直接访问
if (!defined('YICP_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// 上传目录不允许直接访问
header('HTTP/1.1 403 Forbidden');
exit('Direct access to uploads directory is not allowed');

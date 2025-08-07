<?php
// 防止直接访问
if (!defined('YICP_ROOT')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}

// API入口
header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid API request', 'code' => 400]);

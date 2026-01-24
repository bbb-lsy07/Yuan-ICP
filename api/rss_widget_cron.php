<?php
require_once __DIR__.'/../includes/bootstrap.php';
// 为了安全，可以加一个简单的密钥检查，或者只允许本地 IP 访问
// define('RSS_KEY', 'your_secret_key'); if($_GET['key'] != RSS_KEY) exit;

require_once __DIR__.'/../plugins/rss_widget/plugin.php';

// 调用插件内的抓取函数
if (function_exists('rss_widget_fetch_and_cache_feed')) {
    rss_widget_fetch_and_cache_feed();
    echo json_encode(['status' => 'success', 'message' => 'RSS Cache Updated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Function not found']);
}

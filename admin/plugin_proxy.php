<?php
require_once __DIR__.'/../includes/bootstrap.php';

check_admin_auth();

$plugin_identifier = $_GET['plugin'] ?? '';

if (empty($plugin_identifier) || !preg_match('/^[a-zA-Z0-9_-]+$/', $plugin_identifier)) {
    handle_error('无效的插件标识符。');
}

$plugin_admin_page = __DIR__ . '/../plugins/' . $plugin_identifier . '/admin_page.php';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插件页面 - Yuan-ICP</title>
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
                <?php
                if (file_exists($plugin_admin_page)) {
                    include $plugin_admin_page;
                } else {
                    echo '<div class="alert alert-danger">插件管理页面文件未找到！</div>';
                }
                ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

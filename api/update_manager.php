<?php
// api/update_manager.php (V2 - 全面重构版)

@set_time_limit(0);
define('IN_UPDATE_PROCESS', true);

// 在包含bootstrap之前设置错误处理，确保任何错误都以JSON格式返回
error_reporting(0);
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['success' => false, 'message' => '服务器发生致命错误: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']]);
        exit;
    }
});

require_once __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_login();

$REPO_SLUG = 'bbb-lsy07/Yuan-ICP';
$UPDATE_DIR = __DIR__.'/../uploads/updates/';
$BACKUP_DIR = __DIR__.'/../data/backups/';
$LOG_FILE = __DIR__.'/../data/update_log.txt';
$ROOT_PATH = realpath(__DIR__.'/..');

// 主路由
try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // 初始化日志文件 (仅在下载或更新时)
    if (in_array($action, ['download', 'update'])) {
        if (file_exists($LOG_FILE)) {
            unlink($LOG_FILE);
        }
    }
    
    switch ($action) {
        case 'check':
            handle_check();
            break;
        case 'download':
            handle_download();
            break;
        case 'update':
            handle_update();
            break;
        default:
            throw new Exception('无效的操作。');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// 日志记录函数
function log_progress($message) {
    global $LOG_FILE;
    file_put_contents($LOG_FILE, date('[H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// 检查所有版本
function handle_check() {
    global $REPO_SLUG;
    $current_version = get_system_version();
    // 修改API地址为获取所有Releases
    $apiUrl = "https://api.github.com/repos/{$REPO_SLUG}/releases";
    
    $context = stream_context_create(['http' => ['header' => "User-Agent: Yuan-ICP-Updater\r\nAccept: application/vnd.github.v3+json"]]);
    $responseJson = @file_get_contents($apiUrl, false, $context);
    
    if ($responseJson === false) {
        throw new Exception('检查更新失败，无法连接到 GitHub API。');
    }
    
    $releases_data = json_decode($responseJson, true);
    if (!$releases_data || isset($releases_data['message'])) {
        throw new Exception('从GitHub API获取数据失败: ' . ($releases_data['message'] ?? '未知错误'));
    }

    $available_updates = [];
    foreach ($releases_data as $release) {
        $version = ltrim($release['tag_name'], 'v');
        
        // 处理资源文件列表
        $assets_list = [];
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                $assets_list[] = [
                    'name' => $asset['name'],
                    'url' => $asset['browser_download_url'],
                    'size' => $asset['size'], // GitHub API 直接提供字节大小
                ];
            }
        }
        
        // 找到用于一键更新的 zip 包 URL
        $main_download_url = '';
        foreach ($assets_list as $asset) {
            // 假设主更新包的文件名包含版本号
            if (strpos($asset['name'], $version . '.zip') !== false) {
                $main_download_url = $asset['url'];
                break;
            }
        }
        // 如果没找到，退而求其次找第一个 zip
        if (empty($main_download_url) && !empty($assets_list)) {
            foreach ($assets_list as $asset) {
                if (strpos($asset['name'], '.zip') !== false) {
                    $main_download_url = $asset['url'];
                    break;
                }
            }
        }
        
        $available_updates[] = [
            'version' => $version,
            'changelog' => $release['body'],
            'published_at' => date('Y-m-d H:i', strtotime($release['published_at'])),
            'download_url' => $main_download_url, // 主更新包URL
            'assets' => $assets_list, // 包含所有资源的数组
            'full_changelog_url' => $release['html_url'] // 完整的发布页面链接
        ];
    }
    
    echo json_encode([
        'success' => true,
        'current_version' => $current_version,
        'releases' => $available_updates
    ]);
}

// 下载逻辑 (保持不变)
function handle_download() {
    global $UPDATE_DIR;
    $url = $_POST['download_url'] ?? '';
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('无效的下载地址。');
    }
    if (!is_dir($UPDATE_DIR)) @mkdir($UPDATE_DIR, 0755, true);
    if (!is_writable($UPDATE_DIR)) throw new Exception('更新目录不可写: ' . $UPDATE_DIR);
    $zipFile = $UPDATE_DIR . 'update.zip';

    log_progress("开始从 {$url} 下载...");
    $ch = curl_init($url);
    $fp = fopen($zipFile, 'w+');
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Yuan-ICP-Updater');
    curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('下载更新包失败: ' . curl_error($ch));
    }
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($http_code !== 200) {
        throw new Exception("下载失败，服务器返回状态码: {$http_code}");
    }
    
    echo json_encode(['success' => true, 'message' => '更新包下载成功！']);
}

// 核心更新逻辑
function handle_update() {
    global $UPDATE_DIR, $ROOT_PATH;
    $zipFile = $UPDATE_DIR . 'update.zip';
    $new_version = $_POST['new_version'] ?? 'unknown';

    try {
        log_progress('更新流程启动...');
        
        touch($ROOT_PATH . '/maintenance.flag');
        log_progress('维护模式已开启。');

        backup_system();
        log_progress('数据库和配置文件已备份。');
        
        apply_updates($zipFile);
        log_progress('核心文件已更新。');
        
        log_progress('开始检查数据库更新...');
        $db = db(true);
        run_database_migrations($db);
        log_progress('数据库迁移检查完成。');

        // 修改为写入 VERSION 文件
        file_put_contents($ROOT_PATH . '/VERSION', $new_version);
        log_progress("系统版本号已更新至 v{$new_version}。");

        unlink($zipFile);
        log_progress('临时文件已清理。');
        
        unlink($ROOT_PATH . '/maintenance.flag');
        log_progress('维护模式已关闭。更新完成！');

        echo json_encode(['success' => true, 'message' => '系统更新成功！']);

    } catch (Exception $e) {
        log_progress('!!! 严重错误: ' . $e->getMessage());
        if (file_exists($ROOT_PATH . '/maintenance.flag')) {
            unlink($ROOT_PATH . '/maintenance.flag');
            log_progress('维护模式已关闭。');
        }
        throw $e;
    }
}

// 备份函数
function backup_system() {
    global $BACKUP_DIR, $ROOT_PATH;
    if (!is_dir($BACKUP_DIR)) @mkdir($BACKUP_DIR, 0755, true);
    if (!is_writable($BACKUP_DIR)) throw new Exception('备份目录不可写: ' . $BACKUP_DIR);
    $backupDir = $BACKUP_DIR . 'backup_' . date('Ymd_His');
    mkdir($backupDir, 0755, true);

    $db_config = config('database');
    $db_path = $db_config['database'];
    if (file_exists($db_path)) {
        copy($db_path, $backupDir . '/' . basename($db_path) . '.bak');
    }
    
    if (file_exists($ROOT_PATH . '/config/database.php')) {
        copy($ROOT_PATH . '/config/database.php', $backupDir . '/database.php.bak');
    }
}

// 应用更新函数
function apply_updates($zipFile) {
    global $ROOT_PATH, $UPDATE_DIR;
    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== TRUE) throw new Exception('无法打开更新包。');
    
    $tempDir = $UPDATE_DIR . 'update_temp_' . time();
    $zip->extractTo($tempDir);
    $zip->close();
    
    $ignore_list = ['config/', 'data/', 'uploads/', '.env', 'maintenance.flag', 'VERSION'];
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $source_path = $item->getRealPath();
        $relative_path = substr($source_path, strlen($tempDir) + 1);
        $destination_path = $ROOT_PATH . '/' . $relative_path;

        $should_ignore = false;
        foreach ($ignore_list as $ignore_item) {
            if (strpos($relative_path, $ignore_item) === 0) {
                $should_ignore = true;
                break;
            }
        }
        if ($should_ignore) continue;

        if ($item->isDir()) {
            if (!is_dir($destination_path)) mkdir($destination_path, 0755, true);
        } else {
            if (!copy($source_path, $destination_path)) {
                throw new Exception("文件复制失败: {$relative_path}");
            }
        }
    }
    
    // 递归删除函数
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object))
                        rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
    rrmdir($tempDir);
}


?>
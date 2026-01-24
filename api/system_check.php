<?php
require_once __DIR__.'/../includes/bootstrap.php';

// 检查管理员权限
check_admin_auth();

if (!has_permission('system.settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '权限不足']);
    exit;
}

header('Content-Type: application/json');

try {
    $checks = [];
    
    // 1. 检查数据目录权限
    $dataDir = __DIR__ . '/../data';
    if (is_writable($dataDir)) {
        $checks[] = [
            'name' => '数据目录权限',
            'status' => 'success',
            'description' => 'data 目录可写权限正常',
            'details' => '路径: ' . $dataDir
        ];
    } else {
        $checks[] = [
            'name' => '数据目录权限',
            'status' => 'error',
            'description' => 'data 目录不可写，可能影响系统功能',
            'details' => '请检查目录权限: ' . $dataDir
        ];
    }
    
    // 2. 检查安装目录
    $installDir = __DIR__ . '/../install';
    if (file_exists($installDir)) {
        $checks[] = [
            'name' => '安装目录安全',
            'status' => 'warning',
            'description' => '检测到安装目录仍然存在',
            'details' => '建议删除 install 目录以提高安全性'
        ];
    } else {
        $checks[] = [
            'name' => '安装目录安全',
            'status' => 'success',
            'description' => '安装目录已正确删除',
            'details' => '系统安全性良好'
        ];
    }
    
    // 3. 检查数据库连接
    try {
        $db = db();
        $db->query("SELECT 1")->fetch();
        $checks[] = [
            'name' => '数据库连接',
            'status' => 'success',
            'description' => '数据库连接正常',
            'details' => '连接状态良好'
        ];
    } catch (Exception $e) {
        $checks[] = [
            'name' => '数据库连接',
            'status' => 'error',
            'description' => '数据库连接失败',
            'details' => $e->getMessage()
        ];
    }
    
    // 4. 检查SMTP配置（如果启用了邮件功能）
    $config = get_config();
    if (!empty($config['smtp_host'])) {
        // 简单检查SMTP配置是否完整
        $smtpComplete = !empty($config['smtp_host']) && 
                       !empty($config['smtp_username']) && 
                       !empty($config['smtp_password']);
        
        if ($smtpComplete) {
            $checks[] = [
                'name' => 'SMTP 配置',
                'status' => 'success',
                'description' => 'SMTP 邮件配置完整',
                'details' => '服务器: ' . $config['smtp_host']
            ];
        } else {
            $checks[] = [
                'name' => 'SMTP 配置',
                'status' => 'warning',
                'description' => 'SMTP 配置不完整',
                'details' => '请检查邮件服务器设置'
            ];
        }
    } else {
        $checks[] = [
            'name' => 'SMTP 配置',
            'status' => 'warning',
            'description' => '未配置 SMTP 邮件服务',
            'details' => '邮件功能可能无法正常使用'
        ];
    }
    
    // 5. 检查PHP版本
    $phpVersion = PHP_VERSION;
    $minVersion = '7.4.0';
    if (version_compare($phpVersion, $minVersion, '>=')) {
        $checks[] = [
            'name' => 'PHP 版本',
            'status' => 'success',
            'description' => 'PHP 版本符合要求',
            'details' => '当前版本: ' . $phpVersion
        ];
    } else {
        $checks[] = [
            'name' => 'PHP 版本',
            'status' => 'warning',
            'description' => 'PHP 版本过低',
            'details' => '当前: ' . $phpVersion . ', 建议: >= ' . $minVersion
        ];
    }
    
    // 6. 检查必要的PHP扩展
    $requiredExtensions = ['pdo', 'pdo_sqlite', 'json', 'mbstring'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    if (empty($missingExtensions)) {
        $checks[] = [
            'name' => 'PHP 扩展',
            'status' => 'success',
            'description' => '所有必要的PHP扩展已安装',
            'details' => '扩展: ' . implode(', ', $requiredExtensions)
        ];
    } else {
        $checks[] = [
            'name' => 'PHP 扩展',
            'status' => 'error',
            'description' => '缺少必要的PHP扩展',
            'details' => '缺少: ' . implode(', ', $missingExtensions)
        ];
    }
    
    // 7. 检查磁盘空间
    $freeBytes = disk_free_space(__DIR__ . '/../');
    $freeMB = round($freeBytes / 1024 / 1024, 2);
    
    if ($freeMB > 100) {
        $checks[] = [
            'name' => '磁盘空间',
            'status' => 'success',
            'description' => '磁盘空间充足',
            'details' => '可用空间: ' . $freeMB . ' MB'
        ];
    } elseif ($freeMB > 50) {
        $checks[] = [
            'name' => '磁盘空间',
            'status' => 'warning',
            'description' => '磁盘空间较少',
            'details' => '可用空间: ' . $freeMB . ' MB'
        ];
    } else {
        $checks[] = [
            'name' => '磁盘空间',
            'status' => 'error',
            'description' => '磁盘空间不足',
            'details' => '可用空间: ' . $freeMB . ' MB'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $checks
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
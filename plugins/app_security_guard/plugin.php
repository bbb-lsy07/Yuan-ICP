<?php
/**
 * 插件信息
 */
$plugin_info = [
    'name'          => '申请安全卫士 (Application Security Guard)',
    'identifier'    => 'app_security_guard',
    'version'       => '1.2.0', // 功能增强，版本升级
    'description'   => '通过IP地址限制表单提交频率，并记录所有尝试，有效防止机器人刷帖和恶意提交。',
    'author'        => 'bbb-lsy07',
];

if (isset($_GET['plugin_info'])) {
    return $plugin_info;
}

/**
 * 插件激活时执行
 */
function app_security_guard_activate() {
    $db = db();
    try {
        // 1. 确保 icp_applications 表有 ip_address 字段
        $columns = array_column($db->query("PRAGMA table_info(icp_applications);")->fetchAll(), 'name');
        if (!in_array('ip_address', $columns)) {
            $db->exec("ALTER TABLE icp_applications ADD COLUMN ip_address VARCHAR(45) NULL;");
        }

        // 2. 创建日志表
        $db->exec("
            CREATE TABLE IF NOT EXISTS plugin_app_security_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                status VARCHAR(20) NOT NULL, -- 'allowed' or 'blocked'
                details TEXT
            )
        ");

        // 3. 插入默认设置
        $db->exec("INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES ('app_security_guard_enabled', '1');"); // 默认启用
        $db->exec("INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES ('app_security_guard_count', '5');");
        $db->exec("INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES ('app_security_guard_minutes', '10');");

    } catch (Exception $e) {
        error_log('app_security_guard_activate error: ' . $e->getMessage());
    }
}

/**
 * 插件卸载时执行
 */
function app_security_guard_uninstall() {
    $db = db();
    $db->exec("DELETE FROM system_config WHERE config_key LIKE 'app_security_guard_%';");
    $db->exec("DROP TABLE IF EXISTS plugin_app_security_logs;"); // 删除日志表
}

// 注册后台菜单
EnhancedPluginHooks::registerAdminMenu('app_security_guard_settings', '申请安全设置', 'plugin_proxy.php?plugin=app_security_guard', 'fas fa-user-shield', null, 21);

// 挂载到最终确定申请的钩子
PluginHooks::add('before_finalize_application', 'app_security_guard_verify_submission', 10);

/**
 * 核心验证函数
 */
function app_security_guard_verify_submission($data) {
    if (get_config('app_security_guard_enabled', '0') !== '1') {
        return $data;
    }

    $limit_count = (int)get_config('app_security_guard_count', 5);
    $limit_minutes = (int)get_config('app_security_guard_minutes', 10);
    $client_ip = get_client_ip();

    if ($limit_count <= 0 || $limit_minutes <= 0) {
        return $data;
    }

    try {
        $db = db();
        
        // --- 关键逻辑修复 ---
        // ✅ 改进：使用 PHP 计算时间并绑定参数，以兼容不同数据库
        $time_limit = db_date_offset("-{$limit_minutes} minutes");
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM icp_applications WHERE ip_address = ? AND created_at > ?");
        $stmt->execute([$client_ip, $time_limit]);
        $submission_count = $stmt->fetchColumn();

        if ($submission_count >= $limit_count) {
            // 超过限制，记录日志并抛出异常
            log_security_attempt($client_ip, 'blocked', "超过限制 (每 {$limit_minutes} 分钟最多 {$limit_count} 次)");
            throw new Exception("您的申请提交过于频繁，请在 {$limit_minutes} 分钟后再试。");
        } else {
            // 允许通过，记录日志
            log_security_attempt($client_ip, 'allowed', "允许提交 (当前 {$submission_count}/{$limit_count})");
        }
    } catch (Exception $e) {
        // 如果是频率限制的异常，直接向上抛出
        if (strpos($e->getMessage(), "您的申请提交过于频繁") !== false) {
            throw $e;
        }
        // 对于其他数据库错误，记录日志但放行
        error_log('Application Security Guard DB Error: ' . $e->getMessage());
    }

    return $data;
}

/**
 * 辅助函数：记录安全日志
 */
function log_security_attempt($ip, $status, $details = '') {
    try {
        $db = db();
        $stmt = $db->prepare("INSERT INTO plugin_app_security_logs (ip_address, status, details) VALUES (?, ?, ?)");
        $stmt->execute([$ip, $status, $details]);
    } catch (Exception $e) {
        error_log("Failed to log security attempt: " . $e->getMessage());
    }
}
?>
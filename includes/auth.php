<?php
/**
 * Yuan-ICP 认证系统
 */

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 插件系统已移至 bootstrap.php 中统一管理

// 检查用户是否登录
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 检查IP是否被临时封禁
function is_ip_blocked($ip) {
    $db = db();
    
    // 检查最近1小时内是否有超过5次失败尝试
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > datetime('now', '-1 hour') 
        AND success = 0
    ");
    $stmt->execute([$ip]);
    $failedAttempts = $stmt->fetchColumn();
    
    return $failedAttempts >= 5;
}

// 记录登录尝试
function log_login_attempt($ip, $username, $success, $userAgent = '') {
    $db = db();
    $stmt = $db->prepare("
        INSERT INTO login_attempts (ip_address, username, success, user_agent) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$ip, $username, $success ? 1 : 0, $userAgent]);
}

// 获取客户端真实IP
function get_client_ip() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// 用户登录
function login($username, $password) {
    $db = db();
    $clientIp = get_client_ip();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // 检查IP是否被临时封禁
    if (is_ip_blocked($clientIp)) {
        error_log("Blocked login attempt from IP: {$clientIp}");
        log_login_attempt($clientIp, $username, false, $userAgent);
        return false;
    }
    
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && verify_password($password, $user['password'])) {
        // 登录成功，设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_login'] = time();
        $_SESSION['login_ip'] = $clientIp;
        
        // 更新最后登录时间
        $stmt = $db->prepare("UPDATE admin_users SET last_login = ".db_now()." WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // 记录成功的登录尝试
        log_login_attempt($clientIp, $username, true, $userAgent);
        
        // 记录管理员操作日志
        try {
            $stmt = $db->prepare("
                INSERT INTO admin_logs (user_id, action, target, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user['id'],
                'login',
                'system',
                '管理员登录成功',
                $clientIp,
                $userAgent
            ]);
        } catch (Exception $e) {
            error_log('记录登录操作日志失败: ' . $e->getMessage());
        }
        
        return true;
    } else {
        // 记录失败的登录尝试
        log_login_attempt($clientIp, $username, false, $userAgent);

        // --- 新增代码：记录失败日志 ---
        $stmt = $db->prepare("INSERT INTO admin_logs (user_id, action, target, details, ip_address, user_agent, created_at) VALUES (0, 'login_failed', 'system', ?, ?, ?, ".db_now().")");
        $stmt->execute([
            "尝试用户名: " . $username,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        // --- 新增结束 ---

        return false;
    }
}

// 用户登出
function logout() {
    // 记录登出操作日志（在清除会话前）
    if (is_logged_in()) {
        try {
            $db = db();
            $currentUser = current_user();
            if ($currentUser) {
                $stmt = $db->prepare("
                    INSERT INTO admin_logs (user_id, action, target, details, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $currentUser['id'],
                    'logout',
                    'system',
                    '管理员登出',
                    get_client_ip(),
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
            }
        } catch (Exception $e) {
            error_log('记录登出操作日志失败: ' . $e->getMessage());
        }
    }
    
    // 清除所有会话数据
    $_SESSION = [];
    
    // 删除会话cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // 销毁会话
    session_destroy();
}

// 获取当前用户信息
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    $db = db();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// 检查是否为管理员
function is_admin() {
    $user = current_user();
    // 假设所有登录用户都是管理员，或者根据需要添加更复杂的权限检查逻辑
    return $user !== null;
}

// 检查管理员权限
// 防止未授权访问
function require_login() {
    if (!is_logged_in()) {
        // 检查这是否是一个 AJAX 请求
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            // 如果是 AJAX 请求，发送一个 JSON 错误响应
            http_response_code(401); // 401 Unauthorized 状态码
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false, 
                'message' => '您的登录会话已过期，请重新登录。',
                'redirect' => '/admin/login.php' // 告知前端跳转地址
            ]);
            exit;
        } else {
            // 如果是常规浏览器请求，执行重定向
            redirect('/admin/login.php');
        }
    }
}

/**
 * 检查用户是否拥有特定权限
 * @param string $permission 权限名称
 * @return bool
 */
function has_permission($permission) {
    $user = current_user();
    if (!$user) return false;
    
    // 超级管理员拥有所有权限
    if (isset($user['role']) && $user['role'] === 'super_admin') {
        return true;
    }
    
    try {
        $db = db();
        
        // 查询用户角色及其权限
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = ? AND p.name = ?
        ");
        $stmt->execute([$user['id'], $permission]);
        
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        error_log('权限检查失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 要求用户拥有特定权限，否则拒绝访问
 * @param string $permission 权限名称
 * @param string $message 自定义错误消息
 */
function require_permission($permission, $message = null) {
    if (!has_permission($permission)) {
        $defaultMessage = "您没有权限执行此操作 ({$permission})";
        handle_error($message ?: $defaultMessage, true, 403);
    }
}

function check_admin_auth() {
    require_login(); // 确保用户已登录
    if (!is_admin()) {
        // 如果不是管理员，显示拒绝访问并停止执行
        die('Access Denied: Administrator privileges required.');
    }
}

/**
 * 记录管理员操作日志
 * @param string $action 操作类型
 * @param string $target 操作目标
 * @param string $details 操作详情
 */
function log_admin_action($action, $target, $details = '') {
    $user = current_user();
    if (!$user) return;
    
    try {
        $db = db();
        $stmt = $db->prepare("
            INSERT INTO admin_logs (user_id, action, target, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, " . db_now() . ")
        ");
        $stmt->execute([
            $user['id'],
            $action,
            $target,
            $details,
            get_client_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log('记录操作日志失败: ' . $e->getMessage());
    }
}

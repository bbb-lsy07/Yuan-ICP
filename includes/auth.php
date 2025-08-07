<?php
/**
 * Yuan-ICP 认证系统
 */

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否登录
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 用户登录
function login($username, $password) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && verify_password($password, $user['password'])) {
        // 登录成功，设置会话
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_login'] = time();
        
        // 更新最后登录时间
        $stmt = $db->prepare("UPDATE admin_users SET last_login = ".db_now()." WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        return true;
    }
    
    return false;
}

// 用户登出
function logout() {
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
        redirect('/admin/login.php');
    }
}

function check_admin_auth() {
    require_login(); // 确保用户已登录
    if (!is_admin()) {
        // 如果不是管理员，显示拒绝访问并停止执行
        die('Access Denied: Administrator privileges required.');
    }
}
<?php
// /includes/bootstrap.php

// 1. 初始化环境并安全地启动会话
require_once __DIR__ . '/Environment.php';
Environment::init();

// 使用更安全的会话cookie参数
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $cookieParams = [
        'lifetime' => (int) Environment::get('session_lifetime', 7200),
        'path' => '/',
        'domain' => '', // 默认当前域名
        'secure' => $secure ? true : false,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        // 兼容低版本
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'] . '; samesite=' . $cookieParams['samesite'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// 2. 加载核心函数库 (定义了 db(), get_config() 等)
require_once __DIR__.'/functions.php';

// 3. 加载插件钩子系统
require_once __DIR__.'/hooks.php';

// 4. 加载并初始化所有已启用的插件
//    由于 functions.php 和 hooks.php 已加载, 插件可以安全地使用核心函数和钩子
load_plugins();

// 5. 加载其他核心管理器
require_once __DIR__.'/auth.php';
require_once __DIR__.'/theme_manager.php';
require_once __DIR__.'/ApplicationManager.php';
require_once __DIR__.'/AnnouncementManager.php';
require_once __DIR__.'/SettingsManager.php';

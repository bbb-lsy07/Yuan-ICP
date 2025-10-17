<?php
// /includes/bootstrap.php

// 1. 启动会话
if (session_status() === PHP_SESSION_NONE) {
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

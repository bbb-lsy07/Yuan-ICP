<?php
// /includes/bootstrap.php

// 1. 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// +++ 新增代码开始 +++
// 检查维护模式
if (file_exists(__DIR__.'/../maintenance.flag') && !defined('IN_UPDATE_PROCESS')) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Retry-After: 300'); // 告诉浏览器5分钟后再试
    // 显示一个简单的维护页面
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>系统维护中</title><style>body{text-align:center;padding:150px;font-family:sans-serif;}h1{font-size:50px;}p{font-size:20px;color:#666;}</style></head><body><h1>系统正在更新中...</h1><p>为了给您带来更好的体验，我们正在对系统进行升级。请稍后几分钟再访问。</p></body></html>';
    exit;
}
// +++ 新增代码结束 +++

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

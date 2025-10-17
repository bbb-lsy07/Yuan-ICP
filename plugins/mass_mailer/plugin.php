<?php
/**
 * 插件信息
 */
$plugin_info = [
    'name'          => '邮件群发助手',
    'identifier'    => 'mass_mailer',
    'version'       => '1.0.1',
    'description'   => '允许管理员向不同状态的备案用户批量发送邮件通知。',
    'author'        => 'bbb-lsy07',
];

if (isset($_GET['plugin_info'])) {
    return $plugin_info;
}

// *** 核心修复：添加函数存在性检查 ***

if (!function_exists('mass_mailer_activate')) {
    /**
     * 插件激活时执行的函数
     */
    function mass_mailer_activate() {
        // 无特殊操作
    }
}

if (!function_exists('mass_mailer_uninstall')) {
    /**
     * 插件卸载时执行的函数
     */
    function mass_mailer_uninstall() {
        // 无特殊操作
    }
}

/**
 * 注册后台菜单项
 */
EnhancedPluginHooks::registerAdminMenu(
    'mass_mailer',
    '邮件群发',
    'plugin_proxy.php?plugin=mass_mailer',
    'fas fa-paper-plane',
    null,
    30
);
<?php
/**
 * 插件信息数组
 * identifier: 必须与插件目录名一致
 */
$plugin_info = [
    'name' => '我的第一个插件',
    'identifier' => 'my_first_plugin',
    'version' => '1.0',
    'description' => '这是一个演示插件，用于展示插件生命周期管理。',
    'author' => 'bbb-lsy07',
];

// 如果是通过 include 加载的，返回插件信息
if (basename($_SERVER['PHP_SELF']) === 'plugin.php' || isset($_GET['plugin_info'])) {
    return $plugin_info;
}

/**
 * 插件激活时执行
 * 函数名必须是: {identifier}_activate
 */
function my_first_plugin_activate() {
    $db = db();
    // 例如：创建一个新数据表
    $db->exec("
        CREATE TABLE IF NOT EXISTS my_plugin_data (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            some_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

/**
 * 插件停用时执行
 * 函数名必须是: {identifier}_deactivate
 */
function my_first_plugin_deactivate() {
    // 例如：可以在这里执行一些清理任务，但通常停用时不做破坏性操作
    error_log('My First Plugin has been deactivated.');
}

/**
 * 插件卸载时执行
 * 函数名必须是: {identifier}_uninstall
 */
function my_first_plugin_uninstall() {
    $db = db();
    // 例如：删除插件创建的数据表
    $db->exec("DROP TABLE IF EXISTS my_plugin_data");
}

// 使用钩子添加新功能
// 示例：在页脚添加一句话
PluginHooks::add('footer_output', function($content) {
    return $content . '<p style="text-align:center;">My First Plugin is running!</p>';
});

// 示例：注册一个新的后台菜单
EnhancedPluginHooks::registerAdminMenu(
    'my_plugin_page',          // 菜单ID
    '我的插件',                // 菜单标题
    '/admin/my_plugin_page.php', // 链接 (需要自己创建这个文件)
    'fas fa-star',             // 图标
    'extensions'               // 父菜单ID (可选, 比如 'extensions')
);

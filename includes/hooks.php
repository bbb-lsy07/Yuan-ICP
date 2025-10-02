<?php
/**
 * Yuan-ICP 插件钩子系统
 * 
 * 提供插件与核心系统的交互机制
 */

class PluginHooks {
    private static $hooks = [];
    
    /**
     * 添加钩子
     * @param string $name 钩子名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级(数字越大优先级越高)
     */
    public static function add($name, $callback, $priority = 10) {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = [];
        }
        
        self::$hooks[$name][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
    }
    
    /**
     * 执行钩子
     * @param string $name 钩子名称
     * @param mixed $data 传递给回调函数的数据
     * @return mixed 处理后的数据
     */
    public static function run($name, $data = null) {
        if (!isset(self::$hooks[$name])) {
            return $data;
        }
        
        // 按优先级排序
        usort(self::$hooks[$name], function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        foreach (self::$hooks[$name] as $hook) {
            $data = call_user_func($hook['callback'], $data);
        }
        
        return $data;
    }
    
    /**
     * 获取所有已注册的钩子
     * @return array
     */
    public static function getAll() {
        return self::$hooks;
    }
}

/**
 * 增强的插件钩子系统
 */
class EnhancedPluginHooks {
    private static $adminMenus = [];
    private static $settingsPages = [];
    
    /**
     * 注册后台菜单项
     * @param string $id 菜单项ID
     * @param string $title 菜单标题
     * @param string $url 菜单链接
     * @param string $icon 菜单图标
     * @param string $parent 父菜单ID（可选）
     * @param int $priority 优先级
     */
    public static function registerAdminMenu($id, $title, $url, $icon = 'fas fa-cog', $parent = null, $priority = 10) {
        self::$adminMenus[$id] = [
            'id' => $id,
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'parent' => $parent,
            'priority' => $priority
        ];
    }
    
    /**
     * 注册设置页面
     * @param string $id 页面ID
     * @param string $title 页面标题
     * @param string $tab 标签页ID
     * @param callable $callback 渲染回调函数
     * @param int $priority 优先级
     */
    public static function registerSettingsPage($id, $title, $tab, $callback, $priority = 10) {
        self::$settingsPages[$id] = [
            'id' => $id,
            'title' => $title,
            'tab' => $tab,
            'callback' => $callback,
            'priority' => $priority
        ];
    }
    
    /**
     * 获取所有注册的后台菜单
     * @return array
     */
    public static function getAdminMenus() {
        // 按优先级排序
        uasort(self::$adminMenus, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        return self::$adminMenus;
    }
    
    /**
     * 获取所有注册的设置页面
     * @return array
     */
    public static function getSettingsPages() {
        // 按优先级排序
        uasort(self::$settingsPages, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        return self::$settingsPages;
    }
    
    /**
     * 渲染设置页面
     * @param string $tab 当前标签页
     */
    public static function renderSettingsPage($tab) {
        $pages = self::getSettingsPages();
        foreach ($pages as $page) {
            if ($page['tab'] === $tab && is_callable($page['callback'])) {
                call_user_func($page['callback']);
                return;
            }
        }
    }
}

/**
 * 插件管理类
 */
class PluginManager {
    /**
     * Helper function to call a function within a plugin's main file.
     */
    private static function call_plugin_function($identifier, $function_name) {
        $mainFile = __DIR__ . '/../plugins/' . $identifier . '/plugin.php';
        if (file_exists($mainFile)) {
            // Include the file to make sure the function is available
            require_once $mainFile;
            if (function_exists($function_name)) {
                call_user_func($function_name);
            }
        }
    }

    public static function activate($identifier) {
        self::call_plugin_function($identifier, $identifier . '_activate');
        $db = db();
        $stmt = $db->prepare("UPDATE plugins SET is_active = 1 WHERE identifier = ?");
        return $stmt->execute([$identifier]);
    }

    public static function deactivate($identifier) {
        self::call_plugin_function($identifier, $identifier . '_deactivate');
        $db = db();
        $stmt = $db->prepare("UPDATE plugins SET is_active = 0 WHERE identifier = ?");
        return $stmt->execute([$identifier]);
    }

    public static function uninstall($identifier) {
        // 1. Run the plugin's own uninstallation logic (e.g., drop tables)
        self::call_plugin_function($identifier, $identifier . '_uninstall');

        // 2. Delete the plugin's files
        $pluginDir = __DIR__.'/../plugins/'.$identifier;
        if (is_dir($pluginDir)) {
            // A simple recursive delete function
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($pluginDir);
        }

        // 3. Remove from the database
        $db = db();
        $stmt = $db->prepare("DELETE FROM plugins WHERE identifier = ?");
        return $stmt->execute([$identifier]);
    }
    
    /**
     * 获取所有已安装插件
     * @return array
     */
    public static function getInstalledPlugins() {
        $db = db(); // 确保获取数据库连接
        if (!$db) {
            return []; // 如果数据库连接失败，返回空数组
        }
        
        $stmt = $db->query("SELECT * FROM plugins WHERE is_active = 1");
        return $stmt->fetchAll();
    }
    
    /**
     * 安装插件 (修改版，兼容SQLite)
     * @param string $pluginDir 插件目录
     * @return bool
     */
    public static function install($pluginDir) {
        $mainFile = $pluginDir . '/plugin.php';
        if (!file_exists($mainFile)) return false;

        $pluginInfo = include $mainFile;
        if (!isset($pluginInfo['name']) || !isset($pluginInfo['version']) || !isset($pluginInfo['identifier'])) {
            return false;
        }

        $db = db();
        
        // First, check if the plugin already exists
        $stmt_check = $db->prepare("SELECT id FROM plugins WHERE identifier = ?");
        $stmt_check->execute([$pluginInfo['identifier']]);
        $exists = $stmt_check->fetch();

        if ($exists) {
            // If it exists, update it
            $stmt = $db->prepare("UPDATE plugins SET name = ?, version = ?, author = ?, description = ? WHERE identifier = ?");
            return $stmt->execute([
                $pluginInfo['name'],
                $pluginInfo['version'],
                $pluginInfo['author'] ?? '',
                $pluginInfo['description'] ?? '',
                $pluginInfo['identifier']
            ]);
        } else {
            // If not, insert it
            $stmt = $db->prepare(
                "INSERT INTO plugins (name, identifier, version, author, description, installed_at)
                 VALUES (?, ?, ?, ?, ?, " . db_now() . ")"
            );
            return $stmt->execute([
                $pluginInfo['name'],
                $pluginInfo['identifier'],
                $pluginInfo['version'],
                $pluginInfo['author'] ?? '',
                $pluginInfo['description'] ?? ''
            ]);
        }
    }
    
    /**
     * 根据标识符（目录名）安装或更新插件信息到数据库
     * @param string $identifier 插件标识符
     * @return bool
     */
    public static function installFromIdentifier($identifier) {
        $mainFile = __DIR__ . '/../plugins/' . $identifier . '/plugin.php';
        if (!file_exists($mainFile)) return false;

        $_GET['plugin_info'] = true;
        $pluginInfo = include $mainFile;
        unset($_GET['plugin_info']);
        
        // 验证插件信息是否完整
        if (!isset($pluginInfo['name'], $pluginInfo['version'], $pluginInfo['identifier'])) {
            return false;
        }

        // 确保文件中的标识符与目录名一致
        if ($pluginInfo['identifier'] !== $identifier) {
            return false;
        }

        $db = db();
        
        $stmt_check = $db->prepare("SELECT id FROM plugins WHERE identifier = ?");
        $stmt_check->execute([$identifier]);
        $exists = $stmt_check->fetch();

        if ($exists) {
            $stmt = $db->prepare("UPDATE plugins SET name = ?, version = ?, author = ?, description = ? WHERE identifier = ?");
            return $stmt->execute([
                $pluginInfo['name'], $pluginInfo['version'],
                $pluginInfo['author'] ?? '', $pluginInfo['description'] ?? '',
                $identifier
            ]);
        } else {
            $stmt = $db->prepare(
                "INSERT INTO plugins (name, identifier, version, author, description, is_active, installed_at)
                 VALUES (?, ?, ?, ?, ?, 0, " . db_now() . ")"
            );
            return $stmt->execute([
                $pluginInfo['name'], $identifier, $pluginInfo['version'],
                $pluginInfo['author'] ?? '', $pluginInfo['description'] ?? ''
            ]);
        }
    }
}

// 自动加载插件
function load_plugins() {
    $plugins = PluginManager::getInstalledPlugins();
    foreach ($plugins as $plugin) {
        $pluginFile = __DIR__ . '/../plugins/' . $plugin['identifier'] . '/plugin.php';
        if (file_exists($pluginFile)) {
            include_once $pluginFile;
        }
    }
}

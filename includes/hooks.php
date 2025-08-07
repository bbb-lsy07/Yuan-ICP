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
 * 插件管理类
 */
class PluginManager {
    /**
     * 获取所有已安装插件
     * @return array
     */
    public static function getInstalledPlugins() {
        $db = db(); // 确保获取数据库连接
        if (!$db) {
            return []; // 如果数据库连接失败，返回空数组
        }
        
        $stmt = $db->query("SELECT * FROM plugins");
        return $stmt->fetchAll();
    }
    
    /**
     * 安装插件
     * @param string $pluginDir 插件目录
     * @return bool
     */
    public static function install($pluginDir) {
        // 检查插件主文件
        $mainFile = $pluginDir . '/plugin.php';
        if (!file_exists($mainFile)) {
            return false;
        }
        
        // 获取插件信息
        $pluginInfo = include $mainFile;
        if (!isset($pluginInfo['name']) || !isset($pluginInfo['version'])) {
            return false;
        }
        
        // 写入数据库
        global $db;
        $stmt = $db->prepare("
            INSERT INTO plugins 
            (name, identifier, version, author, description, installed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            version = VALUES(version)
        ");
        
        return $stmt->execute([
            $pluginInfo['name'],
            $pluginInfo['identifier'],
            $pluginInfo['version'],
            $pluginInfo['author'] ?? '',
            $pluginInfo['description'] ?? ''
        ]);
    }
    
    /**
     * 卸载插件
     * @param string $identifier 插件标识符
     * @return bool
     */
    public static function uninstall($identifier) {
        global $db;
        $stmt = $db->prepare("DELETE FROM plugins WHERE identifier = ?");
        return $stmt->execute([$identifier]);
    }
    
    /**
     * 启用插件
     * @param string $identifier 插件标识符
     * @return bool
     */
    public static function enable($identifier) {
        // 由于数据库中没有status字段，此方法暂时留空
        return true;
    }
    
    /**
     * 禁用插件
     * @param string $identifier 插件标识符
     * @return bool
     */
    public static function disable($identifier) {
        // 由于数据库中没有status字段，此方法暂时留空
        return true;
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

// 系统启动时加载插件
load_plugins();

<?php
/**
 * Yuan-ICP 主题管理系统
 */

class ThemeManager {
    /**
     * 获取所有可用主题
     * @return array
     */
    public static function getAvailableThemes() {
        $themesDir = __DIR__.'/../themes/';
        $themes = [];
        
        if (is_dir($themesDir)) {
            $dirs = array_diff(scandir($themesDir), ['.', '..']);
            foreach ($dirs as $dir) {
                $themePath = $themesDir . $dir;
                if (is_dir($themePath) && file_exists($themePath.'/theme.json')) {
                    $themeInfo = json_decode(file_get_contents($themePath.'/theme.json'), true);
                    if ($themeInfo) {
                        $themes[$dir] = array_merge($themeInfo, [
                            'screenshot' => file_exists($themePath.'/screenshot.png') ? 
                                'themes/'.$dir.'/screenshot.png' : null
                        ]);
                    }
                }
            }
        }
        
        return $themes;
    }
    
    /**
     * 获取当前激活的主题
     * @return string
     */
    public static function getActiveTheme() {
        // 检查是否为预览模式
        if (isset($_SESSION['preview_theme']) && isset($_GET['preview_theme'])) {
            return $_SESSION['preview_theme'];
        }
        
        try {
            $db = db();
            $stmt = $db->query("SELECT config_value FROM system_config WHERE config_key = 'active_theme'");
            $theme = $stmt->fetchColumn();
            return $theme ?: 'default';
        } catch (Exception $e) {
            // 数据库或表不存在时，返回默认值
            return 'default';
        }
    }
    
    /**
     * 激活主题
     * @param string $themeName 主题名称
     * @return bool
     */
    public static function activateTheme($themeName) {
        $db = db();
        
        // 检查主题是否存在
        $themes = self::getAvailableThemes();
        if (!isset($themes[$themeName])) {
            return false;
        }
        
        // 更新数据库 (使用 REPLACE INTO 兼容 SQLite 和 MySQL)
        // 这是修复后的代码
        $stmt = $db->prepare("
            REPLACE INTO system_config (config_key, config_value) 
            VALUES ('active_theme', ?)
        ");
        return $stmt->execute([$themeName]);
    }
    
    /**
     * 安装主题
     * @param string $zipFile 主题ZIP文件路径
     * @return bool
     */
    public static function installTheme($zipFile) {
        $themesDir = __DIR__.'/../themes/';
        if (!is_dir($themesDir)) {
            mkdir($themesDir, 0755, true);
        }
        
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            // 获取主题名称
            $themeName = pathinfo($zipFile, PATHINFO_FILENAME);
            $extractPath = $themesDir . $themeName;
            
            // 解压主题
            $zip->extractTo($extractPath);
            $zip->close();
            
            // 验证主题
            if (!file_exists($extractPath.'/theme.json')) {
                self::removeTheme($themeName);
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 删除主题
     * @param string $themeName 主题名称
     * @return bool
     */
    public static function removeTheme($themeName) {
        $themeDir = __DIR__.'/../themes/'.$themeName;
        if (!is_dir($themeDir) || $themeName === 'default') { // 防止删除默认主题
            return false;
        }
        
        // 不能删除当前激活的主题
        if ($themeName === self::getActiveTheme()) {
            return false;
        }
        
        // 递归删除主题目录
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($themeDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        return rmdir($themeDir);
    }
    
    /**
     * 获取主题选项
     * @param string $themeName 主题名称（可选，默认当前主题）
     * @return array
     */
    public static function getThemeOptions($themeName = null) {
        $theme = $themeName ?: self::getActiveTheme();
        $themeFile = __DIR__.'/../themes/'.$theme.'/theme.json';
        
        if (!file_exists($themeFile)) {
            return [];
        }
        
        $themeInfo = json_decode(file_get_contents($themeFile), true);
        return $themeInfo['options'] ?? [];
    }
    
    /**
     * 获取主题选项值
     * @param string $optionKey 选项键名
     * @param mixed $default 默认值
     * @param string $themeName 主题名称（可选）
     * @return mixed
     */
    public static function getThemeOption($optionKey, $default = null, $themeName = null) {
        $theme = $themeName ?: self::getActiveTheme();
        
        // 检查是否为预览模式
        if (isset($_SESSION['preview_theme']) && isset($_GET['preview_theme']) && 
            $_SESSION['preview_theme'] === $theme && isset($_SESSION['preview_options'][$optionKey])) {
            return $_SESSION['preview_options'][$optionKey];
        }
        
        $db = db();
        
        try {
            $stmt = $db->prepare("SELECT config_value FROM theme_options WHERE theme_name = ? AND option_key = ?");
            $stmt->execute([$theme, $optionKey]);
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * 设置主题选项值
     * @param string $optionKey 选项键名
     * @param mixed $value 选项值
     * @param string $themeName 主题名称（可选）
     * @return bool
     */
    public static function setThemeOption($optionKey, $value, $themeName = null) {
        $theme = $themeName ?: self::getActiveTheme();
        $db = db();
        
        try {
            // 确保表存在
            $db->exec("
                CREATE TABLE IF NOT EXISTS theme_options (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    theme_name VARCHAR(50) NOT NULL,
                    option_key VARCHAR(100) NOT NULL,
                    config_value TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(theme_name, option_key)
                )
            ");
            
            $stmt = $db->prepare("
                REPLACE INTO theme_options (theme_name, option_key, config_value, updated_at) 
                VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ");
            return $stmt->execute([$theme, $optionKey, $value]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取所有主题选项值
     * @param string $themeName 主题名称（可选）
     * @return array
     */
    public static function getAllThemeOptions($themeName = null) {
        $theme = $themeName ?: self::getActiveTheme();
        $db = db();
        
        try {
            $stmt = $db->prepare("SELECT option_key, config_value FROM theme_options WHERE theme_name = ?");
            $stmt->execute([$theme]);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * 渲染主题模板
     * @param string $template 模板名称
     * @param array $data 模板数据
     */
    public static function render($template, $data = []) {
        $theme = self::getActiveTheme();
        $templateFile = __DIR__.'/../themes/'.$theme.'/templates/'.$template.'.php';

        // 增加一个辅助函数，用于获取主题URL
        if (!function_exists('get_theme_url')) {
            function get_theme_url() {
                // 这个函数可以根据你的URL结构进行调整
                return '/themes/' . ThemeManager::getActiveTheme();
            }
        }
        
        // 增加一个辅助函数，用于获取主题选项
        if (!function_exists('get_theme_option')) {
            function get_theme_option($key, $default = null) {
                return ThemeManager::getThemeOption($key, $default);
            }
        }
        
        if (!file_exists($templateFile)) {
            // 回退到默认模板
            $defaultTemplate = __DIR__.'/../themes/default/templates/'.$template.'.php';
            if (file_exists($defaultTemplate)) {
                $templateFile = $defaultTemplate;
            } else {
                throw new Exception("Template file not found for '{$template}' in both '{$theme}' and 'default' themes.");
            }
        }
        
        extract($data);

        // --- START: 新增的核心修改 ---
        // 对非 header/footer 的主内容模板启用内容过滤
        if ($template !== 'header' && $template !== 'footer') {
            ob_start(); // 开始输出缓冲
            include $templateFile;
            $content = ob_get_clean(); // 获取缓冲区内容并清空
            
            // 执行 'the_content' 钩子, 传递内容和当前模板及数据
            $result = PluginHooks::run('the_content', [
                'content' => $content,
                'template' => $template,
                'data' => $data
            ]);
            
            // 只输出处理后的 content
            echo $result['content'];

        } else {
            // Header 和 Footer 直接输出
            include $templateFile;
        }
        // --- END: 新增的核心修改 ---
    }
}
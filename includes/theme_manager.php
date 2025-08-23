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
        
        if (file_exists($templateFile)) {
            extract($data);
            include $templateFile;
        } else {
            // 回退到默认模板
            $defaultTemplate = __DIR__.'/../themes/default/templates/'.$template.'.php';
            if (file_exists($defaultTemplate)) {
                extract($data);
                include $defaultTemplate;
            } else {
                // 抛出更明确的错误
                throw new Exception("Template file not found for '{$template}' in both '{$theme}' and 'default' themes.");
            }
        }
    }
}

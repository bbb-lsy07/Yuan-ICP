<?php
/**
 * 系统设置管理类
 * 处理所有与系统设置相关的数据库操作
 */
class SettingsManager {
    private $db;
    private $configCache = [];
    private $cacheLoaded = false;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * 获取系统配置
     * @param string|null $key 配置键名，为null时返回所有配置
     * @param mixed $default 默认值
     * @return mixed 配置值或所有配置数组
     */
    public function getConfig($key = null, $default = null) {
        // 如果缓存未加载，从数据库加载所有配置
        if (!$this->cacheLoaded) {
            try {
                $stmt = $this->db->query("SELECT config_key, config_value FROM system_config");
                $this->configCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                $this->cacheLoaded = true;
            } catch (Exception $e) {
                error_log("Failed to load system config: " . $e->getMessage());
                $this->configCache = [];
                $this->cacheLoaded = true;
            }
        }
        
        // 如果请求特定键
        if ($key !== null) {
            return $this->configCache[$key] ?? $default;
        }
        
        // 返回所有配置
        return $this->configCache;
    }
    
    /**
     * 设置系统配置
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * @return bool
     */
    public function setConfig($key, $value) {
        try {
            $stmt = $this->db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
            $result = $stmt->execute([$key, $value]);
            
            // 更新缓存
            $this->configCache[$key] = $value;
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to set config {$key}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 批量设置系统配置
     * @param array $configs 配置数组
     * @return bool
     */
    public function setConfigs($configs) {
        if (empty($configs) || !is_array($configs)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("REPLACE INTO system_config (config_key, config_value) VALUES (?, ?)");
            
            foreach ($configs as $key => $value) {
                $stmt->execute([$key, $value]);
                // 更新缓存
                $this->configCache[$key] = $value;
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Failed to set configs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 删除配置项
     * @param string $key 配置键名
     * @return bool
     */
    public function deleteConfig($key) {
        try {
            $stmt = $this->db->prepare("DELETE FROM system_config WHERE config_key = ?");
            $result = $stmt->execute([$key]);
            
            // 从缓存中移除
            unset($this->configCache[$key]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Failed to delete config {$key}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取基本设置
     * @return array
     */
    public function getBasicSettings() {
        return [
            'site_name' => $this->getConfig('site_name', 'Yuan-ICP'),
            'site_url' => $this->getConfig('site_url', ''),
            'timezone' => $this->getConfig('timezone', 'Asia/Shanghai'),
            'icp_prefix' => $this->getConfig('icp_prefix', 'YIC'),
            'icp_digits' => $this->getConfig('icp_digits', 8)
        ];
    }
    
    /**
     * 获取SEO设置
     * @return array
     */
    public function getSeoSettings() {
        return [
            'seo_title' => $this->getConfig('seo_title', ''),
            'seo_description' => $this->getConfig('seo_description', ''),
            'seo_keywords' => $this->getConfig('seo_keywords', '')
        ];
    }
    
    /**
     * 获取邮件设置
     * @return array
     */
    public function getEmailSettings() {
        return [
            'smtp_host' => $this->getConfig('smtp_host', ''),
            'smtp_port' => $this->getConfig('smtp_port', 587),
            'smtp_username' => $this->getConfig('smtp_username', ''),
            'smtp_password' => $this->getConfig('smtp_password', ''),
            'smtp_secure' => $this->getConfig('smtp_secure', 'tls'),
            'from_email' => $this->getConfig('from_email', ''),
            'from_name' => $this->getConfig('from_name', '')
        ];
    }
    
    /**
     * 获取号码池设置
     * @return array
     */
    public function getNumberSettings() {
        return [
            'number_auto_generate' => $this->getConfig('number_auto_generate', 0),
            'number_generate_format' => $this->getConfig('number_generate_format', 'Yuan{U}{U}{N}{N}{N}{N}{N}{N}'),
            'reserved_numbers' => $this->getConfig('reserved_numbers', ''),
            'sponsor_message' => $this->getConfig('sponsor_message', '')
        ];
    }
    
    /**
     * 保存基本设置
     * @param array $data
     * @return bool
     */
    public function saveBasicSettings($data) {
        $settings = [
            'site_name' => trim($data['site_name'] ?? ''),
            'site_url' => trim($data['site_url'] ?? ''),
            'timezone' => trim($data['timezone'] ?? 'Asia/Shanghai'),
            'icp_prefix' => trim($data['icp_prefix'] ?? 'YIC'),
            'icp_digits' => intval($data['icp_digits'] ?? 8)
        ];
        
        return $this->setConfigs($settings);
    }
    
    /**
     * 保存SEO设置
     * @param array $data
     * @return bool
     */
    public function saveSeoSettings($data) {
        $settings = [
            'seo_title' => trim($data['seo_title'] ?? ''),
            'seo_description' => trim($data['seo_description'] ?? ''),
            'seo_keywords' => trim($data['seo_keywords'] ?? '')
        ];
        
        return $this->setConfigs($settings);
    }
    
    /**
     * 保存邮件设置
     * @param array $data
     * @return bool
     */
    public function saveEmailSettings($data) {
        $settings = [
            'smtp_host' => trim($data['smtp_host'] ?? ''),
            'smtp_port' => intval($data['smtp_port'] ?? 587),
            'smtp_username' => trim($data['smtp_username'] ?? ''),
            'smtp_password' => trim($data['smtp_password'] ?? ''),
            'smtp_secure' => trim($data['smtp_secure'] ?? 'tls'),
            'from_email' => trim($data['from_email'] ?? ''),
            'from_name' => trim($data['from_name'] ?? '')
        ];
        
        return $this->setConfigs($settings);
    }
    
    /**
     * 保存号码池设置
     * @param array $data
     * @return bool
     */
    public function saveNumberSettings($data) {
        $settings = [
            'number_auto_generate' => isset($data['number_auto_generate']) ? 1 : 0,
            'number_generate_format' => trim($data['number_generate_format'] ?? ''),
            'reserved_numbers' => trim($data['reserved_numbers'] ?? ''),
            'sponsor_message' => trim($data['sponsor_message'] ?? '')
        ];
        
        return $this->setConfigs($settings);
    }
    
    /**
     * 验证设置数据
     * @param string $type 设置类型
     * @param array $data 数据
     * @return array 验证错误数组
     */
    public function validateSettings($type, $data) {
        $errors = [];
        
        switch ($type) {
            case 'basic':
                if (empty($data['site_name'])) {
                    $errors[] = '网站名称不能为空';
                }
                if (!empty($data['site_url']) && !filter_var($data['site_url'], FILTER_VALIDATE_URL)) {
                    $errors[] = '网站URL格式不正确';
                }
                if (!empty($data['icp_digits']) && ($data['icp_digits'] < 6 || $data['icp_digits'] > 12)) {
                    $errors[] = '备案号数字位数必须在6-12之间';
                }
                break;
                
            case 'email':
                if (!empty($data['smtp_host']) && empty($data['smtp_port'])) {
                    $errors[] = 'SMTP端口不能为空';
                }
                if (!empty($data['smtp_port']) && ($data['smtp_port'] < 1 || $data['smtp_port'] > 65535)) {
                    $errors[] = 'SMTP端口必须在1-65535之间';
                }
                if (!empty($data['from_email']) && !filter_var($data['from_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = '发件人邮箱格式不正确';
                }
                break;
        }
        
        return $errors;
    }
    
    /**
     * 清除配置缓存
     */
    public function clearCache() {
        $this->configCache = [];
        $this->cacheLoaded = false;
    }
}
?>

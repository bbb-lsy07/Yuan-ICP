<?php
/**
 * Analytics Module Installation Verification
 * Analytics模块安装验证脚本
 */

require_once '../includes/bootstrap.php';

class AnalyticsInstaller
{
    private $errors = [];
    private $warnings = [];
    private $success = [];
    
    public function __construct()
    {
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Analytics Module Installation Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2c3e50; margin: 0; }
        .header p { color: #7f8c8d; margin: 10px 0 0 0; }
        .check-item { margin: 15px 0; padding: 15px; border-radius: 5px; border-left: 4px solid; }
        .check-success { background: #d4edda; border-color: #28a745; }
        .check-warning { background: #fff3cd; border-color: #ffc107; }
        .check-error { background: #f8d7da; border-color: #dc3545; }
        .check-info { background: #d1ecf1; border-color: #17a2b8; }
        .check-icon { font-weight: bold; margin-right: 10px; }
        .success-icon { color: #28a745; }
        .warning-icon { color: #ffc107; }
        .error-icon { color: #dc3545; }
        .info-icon { color: #17a2b8; }
        .actions { margin-top: 30px; text-align: center; }
        .btn { padding: 12px 24px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .progress { margin: 20px 0; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s; }
        .summary { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px; }
        .summary h3 { margin: 0 0 15px 0; color: #2c3e50; }
    </style>
</head>
<body>
<div class='container'>
    <div class='header'>
        <h1>🔍 Analytics Module Installation Verification</h1>
        <p>检查Yuan-ICP Analytics模块的安装状态</p>
    </div>";
    }
    
    public function runVerification()
    {
        $this->checkPHPVersion();
        $this->checkExtensions();
        $this->checkFiles();
        $this->checkDatabase();
        $this->checkPermissions();
        $this->checkConfiguration();
        $this->runBasicTests();
        
        $this->displayResults();
    }
    
    private function checkPHPVersion()
    {
        echo "<h2>📋 PHP环境检查</h2>";
        
        $version = phpversion();
        if (version_compare($version, '7.4.0', '>=')) {
            $this->addSuccess("PHP版本: $version ✓");
        } else {
            $this->addError("PHP版本过低: $version (需要7.4+)");
        }
        
        // 检查必需扩展
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->addSuccess("PHP扩展 $ext: 已安装 ✓");
            } else {
                $this->addError("PHP扩展 $ext: 未安装 ✗");
            }
        }
    }
    
    private function checkExtensions()
    {
        echo "<h2>📦 扩展检查</h2>";
        
        $recommendedExtensions = ['gd', 'zip', 'openssl', 'xml'];
        foreach ($recommendedExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->addSuccess("推荐扩展 $ext: 已安装 ✓");
            } else {
                $this->addWarning("推荐扩展 $ext: 未安装 (建议安装)");
            }
        }
        
        // 检查JavaScript库
        if (file_exists('../assets/js/analytics.js')) {
            $this->addSuccess("前端脚本文件: 已存在 ✓");
        } else {
            $this->addError("前端脚本文件: 缺失 ✗");
        }
    }
    
    private function checkFiles()
    {
        echo "<h2>📁 文件检查</h2>";
        
        $requiredFiles = [
            '../src/Analytics/AnalyticsManager.php',
            '../src/Analytics/ChartAnalyzer.php',
            '../src/Analytics/ReportGenerator.php',
            '../admin/analytics.php',
            '../api/analytics/index.php',
            '../assets/js/analytics.js',
            '../assets/css/analytics.css',
            '../templates/analytics/layout.php',
            '../templates/analytics/templates.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                $this->addSuccess("文件 " . basename($file) . ": 存在 ✓");
            } else {
                $this->addError("文件 " . basename($file) . ": 缺失 ✗");
            }
        }
        
        // 检查迁移文件
        $migrationFile = '../install/migrations/2025_10_28_000001_create_analytics_tables.sql';
        if (file_exists($migrationFile)) {
            $this->addSuccess("数据库迁移文件: 存在 ✓");
        } else {
            $this->addError("数据库迁移文件: 缺失 ✗");
        }
    }
    
    private function checkDatabase()
    {
        echo "<h2>🗄️ 数据库检查</h2>";
        
        try {
            $db = \YuanICP\Core\Database::getInstance();
            
            // 测试连接
            $stmt = $db->query('SELECT 1');
            if ($stmt) {
                $this->addSuccess("数据库连接: 正常 ✓");
            }
            
            // 检查Analytics表
            $analyticsTables = [
                'custom_reports',
                'custom_report_templates',
                'scheduled_reports',
                'report_shares',
                'generated_reports',
                'analytics_cache',
                'user_dashboard_configs',
                'report_permissions'
            ];
            
            foreach ($analyticsTables as $table) {
                try {
                    $stmt = $db->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $this->addSuccess("数据表 $table: 存在 ✓");
                    } else {
                        $this->addWarning("数据表 $table: 不存在 (需要运行迁移)");
                    }
                } catch (Exception $e) {
                    $this->addError("检查数据表 $table: 失败 ✗");
                }
            }
            
        } catch (Exception $e) {
            $this->addError("数据库连接失败: " . $e->getMessage() . " ✗");
        }
    }
    
    private function checkPermissions()
    {
        echo "<h2>🔐 权限检查</h2>";
        
        $writableDirs = [
            '../logs',
            '../uploads',
            '../tmp'
        ];
        
        foreach ($writableDirs as $dir) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    $this->addSuccess("目录 " . basename($dir) . ": 可写 ✓");
                } else {
                    $this->addWarning("目录 " . basename($dir) . ": 不可写 (建议设置为可写)");
                }
            } else {
                $this->addWarning("目录 " . basename($dir) . ": 不存在");
            }
        }
    }
    
    private function checkConfiguration()
    {
        echo "<h2>⚙️ 配置检查</h2>";
        
        // 检查会话
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->addSuccess("PHP会话: 正常 ✓");
        } else {
            $this->addWarning("PHP会话: 未启动");
        }
        
        // 检查错误报告级别
        $errorReporting = error_reporting();
        if ($errorReporting & E_NOTICE) {
            $this->addInfo("错误报告: 包含 NOTICE 级别 (开发模式)");
        } else {
            $this->addInfo("错误报告: 生产模式");
        }
        
        // 检查时区
        $timezone = date_default_timezone_get();
        if ($timezone) {
            $this->addSuccess("时区设置: $timezone ✓");
        } else {
            $this->addWarning("时区设置: 未配置");
        }
    }
    
    private function runBasicTests()
    {
        echo "<h2>🧪 功能测试</h2>";
        
        // 测试类加载
        try {
            require_once '../src/Analytics/AnalyticsManager.php';
            require_once '../src/Analytics/ChartAnalyzer.php';
            require_once '../src/Analytics/ReportGenerator.php';
            
            $this->addSuccess("类文件加载: 成功 ✓");
            
            // 测试实例化
            $analyticsManager = new \YuanICP\Analytics\AnalyticsManager();
            $this->addSuccess("AnalyticsManager实例化: 成功 ✓");
            
            $chartAnalyzer = new \YuanICP\Analytics\ChartAnalyzer();
            $this->addSuccess("ChartAnalyzer实例化: 成功 ✓");
            
            $reportGenerator = new \YuanICP\Analytics\ReportGenerator();
            $this->addSuccess("ReportGenerator实例化: 成功 ✓");
            
        } catch (Exception $e) {
            $this->addError("类实例化失败: " . $e->getMessage() . " ✗");
        }
        
        // 测试API文件
        if (file_exists('../api/analytics/index.php')) {
            $this->addSuccess("API文件: 存在 ✓");
        } else {
            $this->addError("API文件: 缺失 ✗");
        }
        
        // 测试管理界面
        if (file_exists('../admin/analytics.php')) {
            $this->addSuccess("管理界面文件: 存在 ✓");
        } else {
            $this->addError("管理界面文件: 缺失 ✗");
        }
    }
    
    private function addSuccess($message)
    {
        $this->success[] = $message;
        echo "<div class='check-item check-success'>
                <span class='check-icon success-icon'>✓</span>
                <strong>成功:</strong> $message
              </div>";
    }
    
    private function addWarning($message)
    {
        $this->warnings[] = $message;
        echo "<div class='check-item check-warning'>
                <span class='check-icon warning-icon'>⚠</span>
                <strong>警告:</strong> $message
              </div>";
    }
    
    private function addError($message)
    {
        $this->errors[] = $message;
        echo "<div class='check-item check-error'>
                <span class='check-icon error-icon'>✗</span>
                <strong>错误:</strong> $message
              </div>";
    }
    
    private function addInfo($message)
    {
        echo "<div class='check-item check-info'>
                <span class='check-icon info-icon'>ℹ</span>
                <strong>信息:</strong> $message
              </div>";
    }
    
    private function displayResults()
    {
        $total = count($this->success) + count($this->warnings) + count($this->errors);
        $successRate = $total > 0 ? round((count($this->success) / $total) * 100, 1) : 0;
        
        echo "<div class='summary'>
                <h3>📊 验证结果汇总</h3>
                <div class='progress'>
                    <div class='progress-bar'>
                        <div class='progress-fill' style='width: {$successRate}%'></div>
                    </div>
                    <p style='text-align: center; margin: 10px 0;'>
                        完成度: {$successRate}% 
                        (成功: " . count($this->success) . ", 
                         警告: " . count($this->warnings) . ", 
                         错误: " . count($this->errors) . ")
                    </p>
                </div>";
        
        if (empty($this->errors)) {
            if (empty($this->warnings)) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>
                        🎉 <strong>恭喜！Analytics模块安装完全成功！</strong><br>
                        所有检查项目都通过了，系统已准备就绪。
                      </div>";
            } else {
                echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; color: #856404;'>
                        ⚡ <strong>安装基本成功！</strong><br>
                        有一些警告项目，建议查看并处理。
                      </div>";
            }
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>
                    ❌ <strong>安装存在问题！</strong><br>
                    发现 " . count($this->errors) . " 个错误，请先解决这些问题。
                  </div>";
        }
        
        echo "</div>";
        
        // 操作按钮
        echo "<div class='actions'>";
        
        if (!empty($this->errors)) {
            echo "<a href='run_analytics_migration.php?action=migrate' class='btn btn-primary'>运行数据库迁移</a>";
        }
        
        echo "<a href='test_analytics.php' class='btn btn-success'>运行功能测试</a>";
        echo "<a href='../admin/analytics.php' class='btn btn-warning'>访问Analytics中心</a>";
        echo "<button onclick='window.location.reload()' class='btn btn-primary'>重新验证</button>";
        echo "</div>";
        
        echo "</div></body></html>";
    }
}

// 运行验证
if (basename($_SERVER['PHP_SELF']) === 'verify_analytics_installation.php') {
    $installer = new AnalyticsInstaller();
    $installer->runVerification();
}
?>

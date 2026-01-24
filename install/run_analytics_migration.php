<?php
/**
 * Analytics Migration Runner
 * 执行Analytics模块的数据库迁移
 */

require_once '../includes/bootstrap.php';

class AnalyticsMigrationRunner
{
    private $db;
    private $migrationPath = '../install/migrations/';
    
    public function __construct()
    {
        $this->db = \YuanICP\Core\Database::getInstance();
    }
    
    /**
     * 运行所有Analytics迁移
     */
    public function runMigrations()
    {
        echo "<h1>Yuan-ICP Analytics Module Migration</h1>\n";
        echo "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
            .migration-item { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        </style>\n";
        
        try {
            // 检查迁移文件
            $migrationFile = $this->migrationPath . '2025_10_28_000001_create_analytics_tables.sql';
            
            if (!file_exists($migrationFile)) {
                throw new Exception("Migration file not found: $migrationFile");
            }
            
            echo "<div class='info'>Found migration file: " . basename($migrationFile) . "</div>\n";
            
            // 读取并执行迁移SQL
            $sql = file_get_contents($migrationFile);
            
            // 分割SQL语句
            $statements = $this->splitSQLStatements($sql);
            
            echo "<h2>Executing Migration Statements</h2>\n";
            
            foreach ($statements as $index => $statement) {
                $statement = trim($statement);
                
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }
                
                echo "<div class='migration-item'>\n";
                echo "<strong>Statement " . ($index + 1) . ":</strong><br>\n";
                echo "<code>" . htmlspecialchars(substr($statement, 0, 100)) . (strlen($statement) > 100 ? '...' : '') . "</code><br>\n";
                
                try {
                    $this->db->exec($statement);
                    echo "<span class='success'>✓ Executed successfully</span>\n";
                } catch (PDOException $e) {
                    // 忽略表已存在的错误
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<span class='info'>ℹ Table already exists, skipped</span>\n";
                    } else {
                        echo "<span class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
                        throw $e;
                    }
                }
                
                echo "</div>\n";
            }
            
            // 验证迁移结果
            $this->verifyMigration();
            
            echo "<h2>Migration Summary</h2>\n";
            echo "<div class='success'>✓ Analytics module migration completed successfully!</div>\n";
            echo "<div class='info'>All required tables have been created and configured.</div>\n";
            
        } catch (Exception $e) {
            echo "<div class='error'>✗ Migration failed: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            return false;
        }
        
        return true;
    }
    
    /**
     * 分割SQL语句
     */
    private function splitSQLStatements($sql)
    {
        // 移除注释
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // 分割语句
        $statements = preg_split('/;\s*[\r\n]+/', $sql);
        
        return array_filter($statements, function($stmt) {
            return trim($stmt) !== '';
        });
    }
    
    /**
     * 验证迁移结果
     */
    private function verifyMigration()
    {
        echo "<h2>Verifying Migration Results</h2>\n";
        
        $tables = [
            'custom_reports',
            'custom_report_templates',
            'scheduled_reports',
            'report_shares',
            'generated_reports',
            'analytics_cache',
            'user_dashboard_configs',
            'report_permissions'
        ];
        
        foreach ($tables as $table) {
            echo "<div class='migration-item'>\n";
            
            try {
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                
                if ($exists) {
                    // 检查表结构
                    $stmt = $this->db->query("DESCRIBE $table");
                    $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    echo "<span class='success'>✓ Table '$table' exists (" . count($columns) . " columns)</span><br>\n";
                    
                    // 显示列信息
                    echo "<small>Columns: " . implode(', ', array_column($columns, 'Field')) . "</small><br>\n";
                } else {
                    echo "<span class='error'>✗ Table '$table' does not exist</span><br>\n";
                }
                
            } catch (PDOException $e) {
                echo "<span class='error'>✗ Error checking table '$table': " . htmlspecialchars($e->getMessage()) . "</span><br>\n";
            }
            
            echo "</div>\n";
        }
        
        // 检查默认数据
        $this->checkDefaultData();
    }
    
    /**
     * 检查默认数据
     */
    private function checkDefaultData()
    {
        echo "<h3>Checking Default Data</h3>\n";
        
        try {
            // 检查报表模板数据
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM custom_report_templates");
            $templateCount = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
            
            echo "<div class='migration-item'>\n";
            echo "<span class='info'>Report Templates: $templateCount templates loaded</span><br>\n";
            
            if ($templateCount > 0) {
                // 显示模板列表
                $stmt = $this->db->query("SELECT id, name, description FROM custom_report_templates LIMIT 5");
                $templates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                echo "<small>Sample templates:</small><br>\n";
                foreach ($templates as $template) {
                    echo "<small>• {$template['name']}: {$template['description']}</small><br>\n";
                }
            }
            
            echo "</div>\n";
            
        } catch (PDOException $e) {
            echo "<div class='error'>Error checking default data: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        }
    }
    
    /**
     * 回滚迁移
     */
    public function rollbackMigration()
    {
        echo "<h1>Rolling back Analytics Migration</h1>\n";
        
        $tables = [
            'report_permissions',
            'user_dashboard_configs',
            'analytics_cache',
            'generated_reports',
            'report_shares',
            'scheduled_reports',
            'custom_report_templates',
            'custom_reports'
        ];
        
        foreach ($tables as $table) {
            try {
                $this->db->exec("DROP TABLE IF EXISTS $table");
                echo "<div class='success'>✓ Dropped table: $table</div>\n";
            } catch (PDOException $e) {
                echo "<div class='error'>✗ Error dropping table $table: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            }
        }
        
        echo "<div class='success'>Analytics migration rollback completed.</div>\n";
    }
    
    /**
     * 重置迁移
     */
    public function resetMigration()
    {
        echo "<h1>Resetting Analytics Migration</h1>\n";
        
        $this->rollbackMigration();
        echo "<br>\n";
        $this->runMigrations();
    }
}

// 如果直接访问此文件，运行迁移
if (basename($_SERVER['PHP_SELF']) === 'run_analytics_migration.php') {
    $action = $_GET['action'] ?? 'migrate';
    
    $runner = new AnalyticsMigrationRunner();
    
    switch ($action) {
        case 'migrate':
            $runner->runMigrations();
            break;
            
        case 'rollback':
            $runner->rollbackMigration();
            break;
            
        case 'reset':
            $runner->resetMigration();
            break;
            
        default:
            echo "<h1>Analytics Migration Tool</h1>\n";
            echo "<p>Available actions:</p>\n";
            echo "<ul>\n";
            echo "<li><a href='?action=migrate'>Run Migration</a></li>\n";
            echo "<li><a href='?action=rollback'>Rollback Migration</a></li>\n";
            echo "<li><a href='?action=reset'>Reset Migration</a></li>\n";
            echo "</ul>\n";
    }
}
?>

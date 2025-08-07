-- Yuan-ICP 数据库初始化脚本
-- 支持MySQL/PostgreSQL/SQLite

-- 管理员表
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    is_admin BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

-- 系统配置表
CREATE TABLE IF NOT EXISTS system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 公告表
CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 可选号码池
CREATE TABLE IF NOT EXISTS selectable_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number VARCHAR(20) NOT NULL UNIQUE,
    is_premium BOOLEAN DEFAULT 0,
    sponsor_info TEXT,
    status VARCHAR(20) DEFAULT 'available', -- 'available', 'used', 'reserved'
    used_by INTEGER,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 备案申请表
CREATE TABLE IF NOT EXISTS icp_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number VARCHAR(20) NOT NULL UNIQUE,
    website_name VARCHAR(100) NOT NULL,
    domain VARCHAR(100) NOT NULL,
    description TEXT,
    owner_name VARCHAR(50),
    owner_email VARCHAR(100),
    owner_phone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending', -- pending/approved/rejected
    reject_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP,
    reviewed_by INTEGER,
    FOREIGN KEY (reviewed_by) REFERENCES admin_users(id)
);

-- 插件表
CREATE TABLE IF NOT EXISTS plugins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plugin_id VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    version VARCHAR(20) NOT NULL,
    author VARCHAR(100),
    description TEXT,
    is_active BOOLEAN DEFAULT 0,
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 操作日志表
CREATE TABLE IF NOT EXISTS operation_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id)
);

-- 初始化管理员账户(密码将在安装时设置)
INSERT OR IGNORE INTO admin_users (username, password, email) VALUES 
('admin', '', 'admin@example.com');

-- 初始化基本配置
INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES
('site_name', 'Yuan-ICP'),
('site_url', 'http://localhost'),
('timezone', 'Asia/Shanghai'),
('icp_prefix', 'ICP'),
('icp_suffix', '号'),
('icp_digits', 8),
('enable_leap', 1),
('default_theme', 'default');

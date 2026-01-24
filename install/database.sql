-- Yuan-ICP 数据库初始化脚本 V2 (移除了会员系统)

-- 管理员表
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TEXT DEFAULT (datetime('now')),
    last_login TEXT
);

-- 系统配置表
CREATE TABLE IF NOT EXISTS system_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 公告表
CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_pinned INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 可选号码池
CREATE TABLE IF NOT EXISTS selectable_numbers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number VARCHAR(20) NOT NULL UNIQUE,
    is_premium INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'available', -- 'available', 'used', 'reserved'
    used_at TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- 备案申请表 (重要修改)
CREATE TABLE IF NOT EXISTS icp_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    number VARCHAR(20) NOT NULL,
    website_name VARCHAR(100) NOT NULL,
    domain VARCHAR(100) NOT NULL,
    description TEXT,
    owner_name VARCHAR(50),
    owner_email VARCHAR(100),
    ip_address VARCHAR(45),            -- 新增：申请人IP地址
    status VARCHAR(20) DEFAULT 'pending', -- pending, pending_payment, approved, rejected
    reject_reason TEXT,
    payment_platform VARCHAR(50),      -- 新增：赞助平台 (e.g., 'wechat', 'alipay')
    transaction_id VARCHAR(255),       -- 新增：订单号
    is_resubmitted INTEGER DEFAULT 0,  -- 新增：标记为用户重新提交
    created_at TEXT DEFAULT (datetime('now')),
    reviewed_at TEXT,
    reviewed_by INTEGER,
    FOREIGN KEY (reviewed_by) REFERENCES admin_users(id)
);

-- 登录尝试记录表
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100),
    attempt_time TEXT DEFAULT (datetime('now')),
    success INTEGER DEFAULT 0,
    user_agent TEXT
);

-- 数据库迁移版本记录表
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version VARCHAR(255) NOT NULL UNIQUE,
    applied_at TEXT DEFAULT (datetime('now'))
);

-- 管理员操作日志表
CREATE TABLE IF NOT EXISTS admin_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    target TEXT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- 初始化管理员账户(密码将在安装时设置)
INSERT OR IGNORE INTO admin_users (username, password, email) VALUES 
('admin', '', 'admin@example.com');

-- 邮件验证码表
CREATE TABLE IF NOT EXISTS email_verifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 插件管理表
CREATE TABLE IF NOT EXISTS plugins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    identifier VARCHAR(50) NOT NULL UNIQUE, -- 插件唯一标识符，通常是插件目录名
    version VARCHAR(20),
    description TEXT,
    author VARCHAR(100),
    is_active INTEGER DEFAULT 0, -- 0 for inactive, 1 for active
    installed_at TEXT DEFAULT (datetime('now'))
);

-- 初始化基本配置
INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES
('site_name', 'Yuan-ICP'),
('site_url', 'http://localhost'),
('timezone', 'Asia/Shanghai'),
('icp_prefix', 'Yuan'),
('icp_digits', 8),
('number_auto_generate', '0'),
('system_version', '1.0.0');

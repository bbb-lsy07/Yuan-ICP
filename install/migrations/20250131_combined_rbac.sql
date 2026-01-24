-- Yuan-ICP 统一RBAC系统迁移
-- 创建时间: 2025-01-31
-- 说明: 合并所有角色权限相关的表结构和数据

-- 1. 为admin_users表添加role字段（如果不存在）
ALTER TABLE admin_users ADD COLUMN role VARCHAR(50) DEFAULT 'admin';

-- 2. 为icp_applications表添加amount字段（分析功能需要）
ALTER TABLE icp_applications ADD COLUMN amount DECIMAL(10,2) DEFAULT 0.00;

-- 3. 创建角色表
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_system INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 4. 创建权限表
CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    display_name VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at TEXT DEFAULT (datetime('now'))
);

-- 5. 创建角色权限关联表
CREATE TABLE IF NOT EXISTS role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE(role_id, permission_id)
);

-- 6. 创建用户角色关联表
CREATE TABLE IF NOT EXISTS user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    assigned_by INTEGER,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES admin_users(id),
    UNIQUE(user_id, role_id)
);

-- 7. 创建管理员操作日志表
CREATE TABLE IF NOT EXISTS admin_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    action VARCHAR(50) NOT NULL,
    target VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES admin_users(id)
);

-- 8. 创建索引
CREATE INDEX IF NOT EXISTS idx_role_permissions_role_id ON role_permissions(role_id);
CREATE INDEX IF NOT EXISTS idx_role_permissions_permission_id ON role_permissions(permission_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_user_id ON user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_user_roles_role_id ON user_roles(role_id);
CREATE INDEX IF NOT EXISTS idx_permissions_category ON permissions(category);
CREATE INDEX IF NOT EXISTS idx_admin_logs_user_id ON admin_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_admin_logs_action ON admin_logs(action);
CREATE INDEX IF NOT EXISTS idx_admin_logs_created_at ON admin_logs(created_at);

-- 9. 初始化系统角色
INSERT OR IGNORE INTO roles (name, display_name, description, is_system) VALUES
('super_admin', '超级管理员', '拥有所有权限，系统最高权限', 1),
('admin', '管理员', '拥有大部分管理权限', 1),
('moderator', '版主', '拥有部分管理权限，主要用于审核', 0),
('editor', '编辑', '拥有内容管理权限', 0);

-- 10. 初始化系统权限
INSERT OR IGNORE INTO permissions (name, display_name, description, category) VALUES
-- 系统管理权限
('system.settings', '系统设置', '管理系统配置', 'system'),
('system.backup', '系统备份', '执行系统备份操作', 'system'),
('system.logs', '查看日志', '查看系统日志', 'system'),
('system.themes', '主题管理', '管理主题', 'system'),
('system.plugins', '插件管理', '管理插件', 'system'),

-- 用户管理权限
('user.view', '查看用户', '查看用户列表', 'admin'),
('user.create', '创建用户', '创建新用户', 'admin'),
('user.edit', '编辑用户', '编辑用户信息', 'admin'),
('user.delete', '删除用户', '删除用户', 'admin'),
('user.roles', '管理角色', '分配和管理用户角色', 'admin'),

-- 申请管理权限
('application.view', '查看申请', '查看备案申请列表', 'admin'),
('application.create', '创建申请', '创建备案申请', 'admin'),
('application.edit', '编辑申请', '编辑备案申请', 'admin'),
('application.approve', '审核通过', '审核通过备案申请', 'admin'),
('application.reject', '审核拒绝', '拒绝备案申请', 'admin'),
('application.delete', '删除申请', '删除备案申请', 'admin'),

-- 公告管理权限
('announcement.view', '查看公告', '查看公告列表', 'admin'),
('announcement.create', '创建公告', '创建新公告', 'admin'),
('announcement.edit', '编辑公告', '编辑公告内容', 'admin'),
('announcement.delete', '删除公告', '删除公告', 'admin'),
('announcement.pin', '置顶公告', '设置公告置顶', 'admin');

-- 11. 为超级管理员角色分配所有权限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'super_admin'),
    id
FROM permissions;

-- 12. 为管理员角色分配常用权限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'admin'),
    id
FROM permissions
WHERE name NOT IN ('system.backup', 'user.delete');

-- 13. 为版主角色分配审核权限
INSERT OR IGNORE INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT id FROM roles WHERE name = 'moderator'),
    id
FROM permissions
WHERE name IN (
    'application.view', 'application.edit', 'application.approve', 'application.reject',
    'announcement.view', 'announcement.create', 'announcement.edit'
);

-- 14. 将现有管理员用户分配为超级管理员角色
INSERT OR IGNORE INTO user_roles (user_id, role_id)
SELECT 
    id,
    (SELECT id FROM roles WHERE name = 'super_admin')
FROM admin_users
WHERE id NOT IN (SELECT user_id FROM user_roles);

-- 15. 更新admin_users表的role字段
UPDATE admin_users SET role = 'super_admin' WHERE id IN (SELECT user_id FROM user_roles WHERE role_id = (SELECT id FROM roles WHERE name = 'super_admin'));
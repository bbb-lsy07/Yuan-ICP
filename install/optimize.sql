-- 数据库性能优化脚本
-- 为常用查询字段添加索引

-- icp_applications表索引
CREATE INDEX idx_icp_applications_status ON icp_applications(status);
CREATE INDEX idx_icp_applications_domain ON icp_applications(domain);
CREATE INDEX idx_icp_applications_icp_number ON icp_applications(icp_number);
CREATE INDEX idx_icp_applications_created_at ON icp_applications(created_at);

-- admin_users表索引
CREATE INDEX idx_admin_users_username ON admin_users(username);

-- announcements表索引 
CREATE INDEX idx_announcements_is_pinned ON announcements(is_pinned);
CREATE INDEX idx_announcements_created_at ON announcements(created_at);

-- plugins表索引
CREATE INDEX idx_plugins_is_active ON plugins(is_active);
CREATE INDEX idx_plugins_name ON plugins(name);

-- system_config表索引
CREATE INDEX idx_system_config_key ON system_config(config_key);

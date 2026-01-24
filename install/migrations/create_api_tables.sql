-- API相关数据库表创建脚本
-- Yuan-ICP API系统数据库迁移
-- SQLite兼容版本

-- 创建API密钥表
CREATE TABLE IF NOT EXISTS api_keys (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  api_key VARCHAR(64) NOT NULL,
  secret VARCHAR(32) NOT NULL,
  name VARCHAR(255) NOT NULL,
  permissions TEXT,
  status VARCHAR(20) DEFAULT 'active',
  last_used_at TEXT,
  expires_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  UNIQUE (api_key)
);

-- 创建API使用日志表
CREATE TABLE IF NOT EXISTS api_usage_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  api_key_id INTEGER,
  endpoint VARCHAR(255) NOT NULL,
  method VARCHAR(10) NOT NULL,
  status_code INTEGER NOT NULL,
  response_time INTEGER,
  ip_address VARCHAR(45),
  user_agent TEXT,
  request_size INTEGER,
  response_size INTEGER,
  created_at TEXT DEFAULT (datetime('now'))
);

-- 创建API速率限制表
CREATE TABLE IF NOT EXISTS api_rate_limits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  api_key_id INTEGER NOT NULL,
  endpoint VARCHAR(255) NOT NULL,
  limit_count INTEGER NOT NULL,
  window_size INTEGER NOT NULL,
  current_count INTEGER DEFAULT 0,
  reset_at TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

-- 创建API访问统计表
CREATE TABLE IF NOT EXISTS api_access_stats (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  api_key_id INTEGER NOT NULL,
  date DATE NOT NULL,
  total_requests INTEGER DEFAULT 0,
  successful_requests INTEGER DEFAULT 0,
  failed_requests INTEGER DEFAULT 0,
  total_response_time INTEGER DEFAULT 0,
  avg_response_time REAL DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  UNIQUE (api_key_id, date)
);

-- 创建API错误日志表
CREATE TABLE IF NOT EXISTS api_error_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  api_key_id INTEGER,
  endpoint VARCHAR(255) NOT NULL,
  method VARCHAR(10) NOT NULL,
  error_code INTEGER NOT NULL,
  error_message TEXT,
  stack_trace TEXT,
  request_data TEXT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

-- 创建API监控表
CREATE TABLE IF NOT EXISTS api_monitoring (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  api_key_id INTEGER NOT NULL,
  metric_type VARCHAR(50) NOT NULL,
  metric_value REAL NOT NULL,
  metric_unit VARCHAR(20),
  recorded_at TEXT DEFAULT (datetime('now'))
);

-- 创建索引
CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_status ON api_keys(status);
CREATE INDEX IF NOT EXISTS idx_api_usage_logs_user_id ON api_usage_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_api_usage_logs_api_key_id ON api_usage_logs(api_key_id);
CREATE INDEX IF NOT EXISTS idx_api_usage_logs_created_at ON api_usage_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_api_key_id ON api_rate_limits(api_key_id);
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_endpoint ON api_rate_limits(endpoint);
CREATE INDEX IF NOT EXISTS idx_api_access_stats_api_key_id ON api_access_stats(api_key_id);
CREATE INDEX IF NOT EXISTS idx_api_access_stats_date ON api_access_stats(date);
CREATE INDEX IF NOT EXISTS idx_api_error_logs_api_key_id ON api_error_logs(api_key_id);
CREATE INDEX IF NOT EXISTS idx_api_error_logs_created_at ON api_error_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_api_monitoring_api_key_id ON api_monitoring(api_key_id);
CREATE INDEX IF NOT EXISTS idx_api_monitoring_recorded_at ON api_monitoring(recorded_at);

-- 插入默认API配置数据
INSERT OR IGNORE INTO system_config (config_key, config_value) VALUES
('api_rate_limit_enabled', '1'),
('api_rate_limit_requests', '1000'),
('api_rate_limit_window', '3600'),
('api_logging_enabled', '1'),
('api_monitoring_enabled', '1'),
('api_error_threshold', '100'),
('api_response_time_threshold', '5000');

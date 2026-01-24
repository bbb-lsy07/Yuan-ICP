-- Analytics模块数据库迁移
-- 创建时间: 2025-10-28
-- SQLite兼容版本

-- 1. 自定义报表表
CREATE TABLE IF NOT EXISTS custom_reports (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    config TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 2. 自定义报表模板表
CREATE TABLE IF NOT EXISTS custom_report_templates (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    config TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 3. 调度报表表
CREATE TABLE IF NOT EXISTS scheduled_reports (
    id VARCHAR(255) PRIMARY KEY,
    template_id VARCHAR(255) NOT NULL,
    schedule TEXT NOT NULL,
    recipients TEXT,
    is_active INTEGER DEFAULT 1,
    last_run_at TEXT,
    next_run_at TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 4. 报表分享表
CREATE TABLE IF NOT EXISTS report_shares (
    id VARCHAR(255) PRIMARY KEY,
    report_id VARCHAR(255) NOT NULL,
    share_config TEXT NOT NULL,
    access_count INT DEFAULT 0,
    max_access_count INT,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 5. 已生成报表表
CREATE TABLE IF NOT EXISTS generated_reports (
    id VARCHAR(255) PRIMARY KEY,
    template_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    data TEXT NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    export_format VARCHAR(50),
    created_by VARCHAR(255),
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 6. 分析缓存表
CREATE TABLE IF NOT EXISTS analytics_cache (
    id VARCHAR(255) PRIMARY KEY,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    cache_data TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

-- 7. 用户仪表盘配置表
CREATE TABLE IF NOT EXISTS user_dashboard_configs (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    dashboard_name VARCHAR(255) NOT NULL,
    config TEXT NOT NULL,
    is_default INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- 8. 报表权限表
CREATE TABLE IF NOT EXISTS report_permissions (
    id VARCHAR(255) PRIMARY KEY,
    report_id VARCHAR(255) NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    permission_type VARCHAR(50) NOT NULL,
    granted_by VARCHAR(255) NOT NULL,
    created_at TEXT DEFAULT (datetime('now'))
);

-- 9. 创建索引
CREATE INDEX IF NOT EXISTS idx_custom_reports_created_at ON custom_reports(created_at);
CREATE INDEX IF NOT EXISTS idx_custom_report_templates_created_at ON custom_report_templates(created_at);
CREATE INDEX IF NOT EXISTS idx_scheduled_reports_next_run ON scheduled_reports(next_run_at, is_active);
CREATE INDEX IF NOT EXISTS idx_report_shares_expires ON report_shares(expires_at);
CREATE INDEX IF NOT EXISTS idx_generated_reports_created_at ON generated_reports(created_at);
CREATE INDEX IF NOT EXISTS idx_analytics_cache_expires ON analytics_cache(expires_at);
CREATE INDEX IF NOT EXISTS idx_user_dashboard_configs_user ON user_dashboard_configs(user_id);
CREATE INDEX IF NOT EXISTS idx_report_permissions_report ON report_permissions(report_id);
CREATE INDEX IF NOT EXISTS idx_report_permissions_user ON report_permissions(user_id);

-- 10. 插入默认报表模板数据
INSERT OR IGNORE INTO custom_report_templates (id, name, description, config) VALUES
('template_daily_summary', '日报摘要', '每日业务数据摘要报表', '{
    "type": "summary",
    "schedule": "daily",
    "charts": [
        {
            "type": "line",
            "title": "今日申请趋势",
            "config": {
                "metric": "applications",
                "dateRange": {
                    "start": "2025-10-29",
                    "end": "2025-10-29"
                }
            }
        },
        {
            "type": "pie",
            "title": "申请状态分布",
            "config": {
                "category": "status",
                "dateRange": {
                    "start": "2025-10-29",
                    "end": "2025-10-29"
                }
            }
        }
    ],
    "tables": [
        {
            "title": "今日申请详情",
            "query": "SELECT * FROM icp_applications WHERE DATE(created_at) = DATE(''now'') ORDER BY created_at DESC LIMIT 10"
        }
    ]
}'),
('template_weekly_analysis', '周度分析', '每周业务数据分析报表', '{
    "type": "analysis",
    "schedule": "weekly",
    "charts": [
        {
            "type": "line",
            "title": "周度申请趋势",
            "config": {
                "metric": "applications",
                "dateRange": {
                    "start": "2025-10-22",
                    "end": "2025-10-29"
                },
                "interval": "day"
            }
        },
        {
            "type": "bar",
            "title": "地区分布",
            "config": {
                "category": "region",
                "dateRange": {
                    "start": "2025-10-22",
                    "end": "2025-10-29"
                }
            }
        }
    ],
    "tables": [
        {
            "title": "周度统计汇总",
            "query": "SELECT status, COUNT(*) as count, SUM(CASE WHEN status = ''approved'' THEN amount ELSE 0 END) as total_amount FROM icp_applications WHERE created_at >= datetime(''now'', ''-7 days'') GROUP BY status"
        }
    ]
}'),
('template_monthly_report', '月度报表', '月度综合业务报表', '{
    "type": "comprehensive",
    "schedule": "monthly",
    "charts": [
        {
            "type": "line",
            "title": "月度申请趋势",
            "config": {
                "metric": "applications",
                "dateRange": {
                    "start": "2025-10-01",
                    "end": "2025-10-29"
                },
                "interval": "day"
            }
        },
        {
            "type": "doughnut",
            "title": "申请类型分布",
            "config": {
                "category": "application_type",
                "dateRange": {
                    "start": "2025-10-01",
                    "end": "2025-10-29"
                }
            }
        },
        {
            "type": "radar",
            "title": "业务指标雷达",
            "config": {
                "metrics": ["applications", "approvals", "revenue"],
                "labels": ["申请数", "通过数", "收入"],
                "datasets": [
                    {
                        "label": "本月",
                        "metric": "applications",
                        "dateRange": {
                            "start": "2025-10-01",
                            "end": "2025-10-29"
                        }
                    }
                ]
            }
        }
    ],
    "tables": [
        {
            "title": "月度统计汇总",
            "query": "SELECT COUNT(*) as total_applications, SUM(CASE WHEN status = ''approved'' THEN 1 ELSE 0 END) as approved_count, SUM(CASE WHEN status = ''rejected'' THEN 1 ELSE 0 END) as rejected_count, SUM(CASE WHEN status = ''approved'' THEN amount ELSE 0 END) as total_revenue FROM icp_applications WHERE created_at >= strftime(''%Y-%m-01'', ''now'')"
        },
        {
            "title": "地区排名",
            "query": "SELECT region, COUNT(*) as applications, ROUND(AVG(CASE WHEN status = ''approved'' THEN 1 ELSE 0 END) * 100, 2) as approval_rate FROM icp_applications WHERE created_at >= strftime(''%Y-%m-01'', ''now'') AND region IS NOT NULL GROUP BY region ORDER BY applications DESC LIMIT 10"
        }
    ]
}'),
('template_performance_dashboard', '性能仪表盘', '实时性能监控仪表盘', '{
    "type": "dashboard",
    "schedule": "realtime",
    "charts": [
        {
            "type": "gauge",
            "title": "今日申请数",
            "config": {
                "metric": "total_applications",
                "max": 1000
            }
        },
        {
            "type": "gauge",
            "title": "通过率",
            "config": {
                "metric": "success_rate",
                "max": 100
            }
        },
        {
            "type": "heatmap",
            "title": "申请热力图",
            "config": {
                "dateRange": {
                    "start": "2025-10-22",
                    "end": "2025-10-29"
                }
            }
        }
    ]
}'),
('template_financial_summary', '财务摘要', '财务数据汇总报表', '{
    "type": "financial",
    "schedule": "monthly",
    "charts": [
        {
            "type": "area",
            "title": "收入趋势",
            "config": {
                "metric": "revenue",
                "dateRange": {
                    "start": "2025-10-01",
                    "end": "2025-10-29"
                }
            }
        },
        {
            "type": "bar",
            "title": "月度收入对比",
            "config": {
                "category": "application_type",
                "dateRange": {
                    "start": "2025-10-01",
                    "end": "2025-10-29"
                }
            }
        }
    ],
    "tables": [
        {
            "title": "收入明细",
            "query": "SELECT application_type, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount, MIN(amount) as min_amount, MAX(amount) as max_amount FROM icp_applications WHERE status = ''approved'' AND created_at >= strftime(''%Y-%m-01'', ''now'') GROUP BY application_type ORDER BY total_amount DESC"
        }
    ]
}');

-- 11. 清理过期数据的注释
-- 注意：清理过期缓存和分享链接的功能需要在应用程序层面实现
-- 示例清理SQL：
-- DELETE FROM analytics_cache WHERE expires_at < datetime('now');
-- DELETE FROM report_shares WHERE expires_at < datetime('now');

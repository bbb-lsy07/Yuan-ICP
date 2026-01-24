CREATE TABLE IF NOT EXISTS plugin_leap_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_site_id INTEGER, -- 来源站ID
    to_site_id INTEGER,   -- 目标站ID
    from_domain VARCHAR(255),
    to_domain VARCHAR(255),
    jump_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

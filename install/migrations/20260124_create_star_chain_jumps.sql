CREATE TABLE IF NOT EXISTS plugin_star_chain_jumps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_domain VARCHAR(255),  -- 来源域名
    to_domain VARCHAR(255),    -- 目标域名
    target_name VARCHAR(100),  -- 目标站名
    jump_time DATETIME DEFAULT CURRENT_TIMESTAMP
);

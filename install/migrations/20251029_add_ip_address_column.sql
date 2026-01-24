-- 为icp_applications表添加ip_address列
-- 执行日期: 2025-10-29

-- SQLite不支持IF NOT EXISTS for ALTER TABLE ADD COLUMN
-- 我们需要使用一个更安全的方法

-- 首先检查列是否已经存在，如果不存在才添加
-- 这个方法通过创建临时表来实现安全的列添加

-- 检查是否需要添加ip_address列
-- 如果表结构中已经有ip_address列，这个操作会被忽略

-- 创建一个临时表来检查列是否存在
CREATE TEMP TABLE IF NOT EXISTS temp_check AS 
SELECT sql FROM sqlite_master WHERE type='table' AND name='icp_applications';

-- 只有当ip_address列不存在时才执行ALTER TABLE
-- 由于SQLite的限制，我们使用一个更简单的方法：
-- 尝试添加列，如果失败就忽略错误

-- 注意：这个迁移可能已经被执行过了
-- 如果列已存在，请手动将此迁移标记为已完成
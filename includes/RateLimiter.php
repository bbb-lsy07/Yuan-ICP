<?php
/**
 * Rate Limiter Class
 * Handles API rate limiting using SQLite
 */

class RateLimiter {
    private $db;
    private $table = 'rate_limits';

    public function __construct() {
        $this->db = db();
        $this->initTable();
    }

    /**
     * Initialize the rate limits table
     */
    private function initTable() {
        // Create table if not exists
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                key TEXT PRIMARY KEY,
                hits INTEGER DEFAULT 0,
                reset_at INTEGER
            )
        ");
    }

    /**
     * Check if the request is allowed
     * 
     * @param string $key Unique identifier (e.g., IP address + action)
     * @param int $maxAttempts Maximum number of attempts allowed
     * @param int $decaySeconds Time window in seconds
     * @return bool True if allowed, False if limit exceeded
     */
    public function attempt($key, $maxAttempts, $decaySeconds) {
        $now = time();
        $stmt = $this->db->prepare("SELECT hits, reset_at FROM {$this->table} WHERE key = ?");
        $stmt->execute([$key]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // First attempt
            $resetAt = $now + $decaySeconds;
            $insert = $this->db->prepare("INSERT INTO {$this->table} (key, hits, reset_at) VALUES (?, 1, ?)");
            $insert->execute([$key, $resetAt]);
            return true;
        }

        if ($now > $record['reset_at']) {
            // Window expired, reset counter
            $resetAt = $now + $decaySeconds;
            $update = $this->db->prepare("UPDATE {$this->table} SET hits = 1, reset_at = ? WHERE key = ?");
            $update->execute([$resetAt, $key]);
            return true;
        }

        if ($record['hits'] < $maxAttempts) {
            // Increment counter
            $update = $this->db->prepare("UPDATE {$this->table} SET hits = hits + 1 WHERE key = ?");
            $update->execute([$key]);
            return true;
        }

        // Limit exceeded
        return false;
    }

    /**
     * Get the number of seconds until the rate limit resets
     */
    public function availableIn($key) {
        $stmt = $this->db->prepare("SELECT reset_at FROM {$this->table} WHERE key = ?");
        $stmt->execute([$key]);
        $resetAt = $stmt->fetchColumn();
        
        return max(0, $resetAt - time());
    }

    /**
     * Clean up expired records to keep the table small
     * Can be called periodically or via a cron job
     */
    public function cleanup() {
        // 1% chance to run cleanup on instantiation/usage to avoid performance hit on every request
        if (rand(1, 100) <= 1) {
            $this->db->exec("DELETE FROM {$this->table} WHERE reset_at < " . time());
        }
    }
}

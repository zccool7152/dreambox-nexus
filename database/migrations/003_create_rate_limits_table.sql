-- 003_create_rate_limits_table.sql
CREATE TABLE IF NOT EXISTS rate_limits (
    key_id VARCHAR(191) PRIMARY KEY,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    reset_at INT UNSIGNED NOT NULL,
    INDEX idx_rate_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


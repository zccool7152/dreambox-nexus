-- 011_create_cache_store_table.sql
CREATE TABLE IF NOT EXISTS cache_store (
    cache_key VARCHAR(191) PRIMARY KEY,
    cache_value LONGTEXT NOT NULL,
    expiration INT UNSIGNED NOT NULL,
    INDEX idx_cache_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 012_create_users_table.sql
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(64) PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    name VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NULL,
    auth_provider VARCHAR(32) NOT NULL DEFAULT 'email',
    provider_id VARCHAR(191) NULL,
    avatar_url TEXT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    is_2fa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_provider (auth_provider, provider_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

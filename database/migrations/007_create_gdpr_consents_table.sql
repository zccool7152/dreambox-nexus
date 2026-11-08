-- 007_create_gdpr_consents_table.sql
CREATE TABLE IF NOT EXISTS user_consents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NULL,
    purpose VARCHAR(64) NOT NULL,
    version VARCHAR(32) NOT NULL,
    status ENUM('granted', 'revoked') NOT NULL DEFAULT 'granted',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    INDEX idx_consent_user_purpose (user_id, purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


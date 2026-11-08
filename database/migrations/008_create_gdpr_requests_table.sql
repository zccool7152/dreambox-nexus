-- 008_create_gdpr_requests_table.sql
CREATE TABLE IF NOT EXISTS gdpr_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    type ENUM('export', 'erase', 'rectify') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    export_path VARCHAR(255) NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_gdpr_status (status),
    INDEX idx_gdpr_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


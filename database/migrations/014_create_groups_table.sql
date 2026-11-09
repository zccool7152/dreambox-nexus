-- 014_create_groups_table.sql
CREATE TABLE IF NOT EXISTS groups (
    id VARCHAR(64) PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS group_roles (
    group_id VARCHAR(64) NOT NULL,
    role_id VARCHAR(64) NOT NULL,
    PRIMARY KEY (group_id, role_id),
    INDEX idx_gr_group (group_id),
    INDEX idx_gr_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_groups (
    user_id VARCHAR(64) NOT NULL,
    group_id VARCHAR(64) NOT NULL,
    PRIMARY KEY (user_id, group_id),
    INDEX idx_ug_user (user_id),
    INDEX idx_ug_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

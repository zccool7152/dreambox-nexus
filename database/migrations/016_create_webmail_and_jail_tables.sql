-- 016_create_webmail_and_jail_tables.sql
ALTER TABLE users
    ADD COLUMN mail_access TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN mailbox_address VARCHAR(191) NULL AFTER mail_access,
    ADD UNIQUE INDEX idx_users_mailbox_address (mailbox_address);

CREATE TABLE IF NOT EXISTS mailboxes (
    id VARCHAR(64) PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL UNIQUE,
    address VARCHAR(191) NOT NULL UNIQUE,
    local_part VARCHAR(64) NOT NULL,
    domain VARCHAR(128) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mailboxes_user (user_id),
    INDEX idx_mailboxes_address (address),
    CONSTRAINT fk_mailboxes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mail_messages (
    id VARCHAR(64) PRIMARY KEY,
    owner_user_id VARCHAR(64) NOT NULL,
    mailbox_address VARCHAR(191) NOT NULL,
    folder VARCHAR(32) NOT NULL DEFAULT 'inbox',
    from_name VARCHAR(191) NOT NULL,
    from_address VARCHAR(191) NOT NULL,
    to_address VARCHAR(191) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    attachments JSON NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_starred TINYINT(1) NOT NULL DEFAULT 0,
    scan_status VARCHAR(32) NOT NULL DEFAULT 'clean',
    scan_threats JSON NULL,
    scanned_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mail_owner_folder (owner_user_id, folder),
    INDEX idx_mail_mailbox (mailbox_address),
    INDEX idx_mail_created (created_at),
    CONSTRAINT fk_mail_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


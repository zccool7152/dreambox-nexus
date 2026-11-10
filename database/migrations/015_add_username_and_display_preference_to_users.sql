-- 015_add_username_and_display_preference_to_users.sql
ALTER TABLE users ADD COLUMN username VARCHAR(64) NULL AFTER name;
ALTER TABLE users ADD COLUMN display_preference VARCHAR(16) NOT NULL DEFAULT 'name' AFTER username;
ALTER TABLE users ADD UNIQUE INDEX idx_users_username (username);


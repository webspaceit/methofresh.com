-- Apply once to an existing installation before deploying the matching code.
-- Safe to re-run: the column is only added when it is missing.

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_orders'
      AND COLUMN_NAME  = 'access_token_hash'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE wsit_orders ADD COLUMN access_token_hash CHAR(64) DEFAULT NULL AFTER transaction_id',
    'DO 0');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS wsit_password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user
      FOREIGN KEY (user_id) REFERENCES wsit_users(id) ON DELETE CASCADE,
    INDEX idx_password_resets_user_created (user_id, created_at),
    INDEX idx_password_resets_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

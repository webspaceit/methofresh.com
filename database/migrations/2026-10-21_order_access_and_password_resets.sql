-- Apply once to an existing installation before deploying the matching code.
ALTER TABLE wsit_orders
    ADD COLUMN access_token_hash CHAR(64) DEFAULT NULL AFTER transaction_id;

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
) ENGINE=InnoDB;

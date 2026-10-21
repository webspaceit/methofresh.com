-- Auto-register guest customers at checkout.
--
-- Background: a guest order used to leave the customer without an account.
-- The order was stored with user_id = NULL and the e-mail only in
-- shipping_email, so the order never showed up in "My Orders" and the
-- customer could never sign in.
--
-- Guest checkout now creates the account automatically and links the order
-- to it. The customer never chose a password, so the account is flagged with
-- auto_registered = 1 until they set one (via the "set your password" link
-- that travels with the order confirmation).
--
-- Run once. Safe to re-run: the column is only added when it is missing.

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_users'
      AND COLUMN_NAME  = 'auto_registered'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE wsit_users
        ADD COLUMN auto_registered TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin',
    'DO 0');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill: accounts that already exist are normal accounts.
UPDATE wsit_users SET auto_registered = 0 WHERE auto_registered IS NULL;

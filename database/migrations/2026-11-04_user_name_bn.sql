-- Bilingual user names.
--
-- Customers and staff can have their name stored in both English and
-- Bengali, so the dashboard and storefront show the right language. The
-- column is nullable and defaults to NULL; existing rows fall back to
-- `name` (English) when no Bengali value is present.

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_users'
      AND COLUMN_NAME  = 'name_bn'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE wsit_users
        ADD COLUMN name_bn VARCHAR(190) NULL DEFAULT NULL AFTER name',
    'DO 0');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Hide the price and the order button for a whole category.
--
-- Some categories are listed in the shop but are not orderable yet (for
-- example a category that is still being sourced). hide_price = 1 keeps the
-- products visible while hiding their price and the "Order Now" button, so
-- customers see a "Price on request" notice instead.
--
-- Run once. Safe to re-run: the column is only added when it is missing.

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_categories'
      AND COLUMN_NAME  = 'hide_price'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE wsit_categories
        ADD COLUMN hide_price TINYINT(1) NOT NULL DEFAULT 0 AFTER active',
    'DO 0');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Existing categories keep their price visible.
UPDATE wsit_categories SET hide_price = 0 WHERE hide_price IS NULL;

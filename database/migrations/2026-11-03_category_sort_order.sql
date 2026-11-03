-- Display order for categories.
--
-- Categories used to be listed alphabetically. sort_order lets the shop owner
-- decide the order in which categories appear in the shop navigation and on
-- the homepage "Our Products" block. Lower numbers come first; ties fall back
-- to the category name.

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_categories'
      AND COLUMN_NAME  = 'sort_order'
);

SET @sql := IF(@col = 0,
    'ALTER TABLE wsit_categories
        ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER hide_price',
    'DO 0');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE wsit_categories SET sort_order = 0 WHERE sort_order IS NULL;

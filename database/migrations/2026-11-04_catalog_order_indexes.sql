-- Indexes for the storefront and the order dashboards.
--
-- Catalog pages filter active products and sort by created_at or sold.
-- Order lists and revenue charts filter and sort by created_at and status.
-- Guest-order linking looks up shipping_email. Category growth joins
-- order lines by product_id. Each statement is safe to re-run.

-- wsit_products -------------------------------------------------------------
SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_products'
      AND INDEX_NAME   = 'idx_products_active_created'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_products ADD INDEX idx_products_active_created (active, created_at)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_products'
      AND INDEX_NAME   = 'idx_products_active_sold'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_products ADD INDEX idx_products_active_sold (active, sold)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_products'
      AND INDEX_NAME   = 'idx_products_sku'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_products ADD INDEX idx_products_sku (sku)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- wsit_orders ---------------------------------------------------------------
SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_orders'
      AND INDEX_NAME   = 'idx_orders_created'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_orders ADD INDEX idx_orders_created (created_at)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_orders'
      AND INDEX_NAME   = 'idx_orders_status_created'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_orders ADD INDEX idx_orders_status_created (status, created_at)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_orders'
      AND INDEX_NAME   = 'idx_orders_shipping_email'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_orders ADD INDEX idx_orders_shipping_email (shipping_email)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- wsit_order_items ----------------------------------------------------------
SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wsit_order_items'
      AND INDEX_NAME   = 'idx_order_items_product'
);
SET @sql := IF(@idx = 0,
    'ALTER TABLE wsit_order_items ADD INDEX idx_order_items_product (product_id)',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

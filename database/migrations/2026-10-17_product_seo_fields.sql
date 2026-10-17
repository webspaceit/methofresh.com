-- ---------------------------------------------------------------------------
-- Migration: per-product SEO fields
-- Date: 2026-10-17
--
-- Adds meta_description and og_image columns to wsit_products so each product
-- can have its own SEO meta description and Open Graph image. Both are
-- optional — when empty, the system auto-generates from the product data.
--
-- Idempotent — safe to run more than once.
-- ---------------------------------------------------------------------------

ALTER TABLE wsit_products
    ADD COLUMN IF NOT EXISTS seo_description TEXT DEFAULT NULL AFTER description_bn,
    ADD COLUMN IF NOT EXISTS seo_og_image   VARCHAR(255) DEFAULT NULL AFTER seo_description;

-- ---------------------------------------------------------------------------
-- Migration: newsletter subscribers
-- Date: 2026-10-17
--
-- Adds the table behind the homepage newsletter ("subscribe") form. The
-- contact form already persists to wsit_contact_messages, so only this one
-- new table is required.
--
-- Idempotent — safe to run more than once.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS wsit_newsletter_subscribers (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(190) NOT NULL UNIQUE,
    locale      VARCHAR(5)   NOT NULL DEFAULT 'en',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ns_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

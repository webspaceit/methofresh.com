-- Convert every remaining latin1 table to utf8mb4.
--
-- Background: the host database was created with a latin1 default charset, and
-- tables created without an explicit charset inherited it. Any non-Latin-1
-- text (Bengali, emoji, CJK) written into those columns was silently
-- converted to '?' at write time, because MySQL maps unrepresentable
-- characters to a question mark when converting to the column charset.
--
-- The application connection is already utf8mb4 (see app/Core/wsit_Database.php).
-- These tables still need converting so the column charset can represent the
-- data the application sends.
--
-- Run once. Safe to re-run: already-utf8mb4 tables are left untouched.

-- 1. Make utf8mb4 the default for any table created later in this database.
--    Replace `<db_name>` with the actual database name before running.
-- ALTER DATABASE `<db_name>` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Convert the existing tables.
ALTER TABLE wsit_checkout_visits
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE wsit_conversations
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE wsit_message_logs
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE wsit_newsletter_subscribers
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE wsit_password_resets
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE wsit_search_logs
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

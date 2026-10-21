-- Messaging automation: WhatsApp Cloud API + Facebook Messenger.
-- Apply once to an existing installation before deploying the matching code.

CREATE TABLE IF NOT EXISTS wsit_message_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel     ENUM('whatsapp','messenger') NOT NULL,
    direction   ENUM('outbound','inbound') NOT NULL,
    recipient   VARCHAR(50) NOT NULL,
    message_id  VARCHAR(120) DEFAULT NULL,
    template    VARCHAR(60) DEFAULT NULL,
    order_id    INT UNSIGNED DEFAULT NULL,
    payload     JSON DEFAULT NULL,
    status      VARCHAR(30) DEFAULT NULL,
    error       TEXT DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_message_logs_message_id (message_id),
    INDEX idx_message_logs_channel_dir (channel, direction),
    INDEX idx_message_logs_order (order_id),
    INDEX idx_message_logs_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wsit_conversations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel         ENUM('whatsapp','messenger') NOT NULL DEFAULT 'whatsapp',
    customer_id     INT UNSIGNED DEFAULT NULL,
    phone           VARCHAR(30) DEFAULT NULL,
    psid            VARCHAR(60) DEFAULT NULL,
    display_name    VARCHAR(120) DEFAULT NULL,
    status          ENUM('open','closed','human') NOT NULL DEFAULT 'open',
    last_message_at TIMESTAMP NULL DEFAULT NULL,
    last_inbound_at TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_conversations_channel_phone (channel, phone),
    UNIQUE KEY uniq_conversations_channel_psid (channel, psid),
    INDEX idx_conversations_status (status),
    INDEX idx_conversations_last (last_message_at)
) ENGINE=InnoDB;

<?php

declare(strict_types=1);

/**
 * Daily delivery reminders — run from cron:
 *
 *   php /path/to/methofresh.com/bin/messaging-reminders.php
 *
 * Sends the `delivery_reminder` WhatsApp template to orders that are
 * processing/shipped and have not been reminded in the last 20 hours.
 * Safe to run more than once a day (duplicates are skipped).
 *
 * Optional argument: max orders to process in one run (default 50).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__) . '/app/autoload.php';
require_once dirname(__DIR__) . '/app/wsit_helpers.php';

use App\Core\wsit_App;
use App\Core\wsit_Database;
use App\Services\Messaging\MessagingConfig;
use App\Services\Messaging\MessagingDispatcher;

// The HTTP front controller connects the DB inside wsit_App::run(); CLI scripts
// must do it themselves before any model or MessagingConfig call.
try {
    wsit_App::set('db', wsit_Database::connect(db_config()));
} catch (\Throwable $e) {
    fwrite(STDERR, '[messaging-reminders] database connection failed: ' . $e->getMessage() . "\n");
    exit(2);
}

$config = new MessagingConfig();

if (!$config->isEnabled()) {
    echo "[messaging-reminders] automation disabled — nothing to do.\n";
    exit(0);
}

if (!$config->whatsAppReady()) {
    echo "[messaging-reminders] WhatsApp is not configured — nothing to do.\n";
    exit(0);
}

$limit  = isset($argv[1]) ? max(1, (int)$argv[1]) : 50;
$result = (new MessagingDispatcher($config))->sendDeliveryReminders($limit);

echo sprintf(
    "[messaging-reminders] sent=%d failed=%d skipped=%d\n",
    $result['sent'],
    $result['failed'],
    $result['skipped']
);

exit($result['failed'] > 0 ? 1 : 0);

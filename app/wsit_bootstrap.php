<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Bootstrap: load autoloader, helpers, config, run the application.
// ---------------------------------------------------------------------------

require_once dirname(__DIR__) . '/vendor/autoload.php';

require_once dirname(__DIR__) . '/app/wsit_helpers.php';

use App\Core\wsit_App;
use App\Core\wsit_Session;
use App\Core\wsit_Validator;

// Start PHP session early (needed for locale cookie + auth + cart).
start_session();

// Boot the application container (also registers global locale).
$app = app();

// NOTE: the front controller registers its routes in public/wsit_index.php, so
// $app->run() is invoked there — NOT here.
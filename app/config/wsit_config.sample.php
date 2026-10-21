<?php

return [
    'app' => [
        'name'       => 'MethoFresh',
        'base_url'   => 'http://localhost/methofresh.com',
        'env'        => 'production',
        'debug'      => false,
        'default_locale' => 'en',
        'locales'    => ['en', 'bn'],
    ],

    'database' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'methofresh',
        'username' => 'CHANGE_ME',
        'password' => 'CHANGE_ME',
        'charset'  => 'utf8mb4',
        'prefix'   => 'wsit_',
    ],

    'session' => [
        'name'     => 'methofresh_session',
        'lifetime' => 60 * 60 * 24,
    ],

    // Messaging automation defaults. In production these are managed from
    // Dashboard → Messaging Setup (stored in wsit_settings with a
    // `messaging_` prefix). The values here are only fallbacks for fresh
    // installs; MF_MESSAGING_* environment variables are read as well.
    'messaging' => [
        'enabled'                   => '0',
        'api_version'               => 'v21.0',
        'wa_phone_number_id'        => '',
        'wa_access_token'           => '',
        'wa_business_account_id'    => '',
        'wa_app_secret'             => '',
        'wa_verify_token'           => '',
        'wa_template_lang'          => 'bn',
        'wa_tpl_order_confirmation' => 'order_confirmation',
        'wa_tpl_status_update'      => 'order_status_update',
        'wa_tpl_delivery_reminder'  => 'delivery_reminder',
        'wa_tpl_order_delivered'    => 'order_delivered',
        'fb_page_access_token'      => '',
        'fb_app_secret'             => '',
        'fb_verify_token'           => '',
        'admin_alert_phone'         => '',
    ],
];

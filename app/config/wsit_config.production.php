<?php
/**
 * Production overrides — loaded automatically on the live server.
 * DO NOT commit this file to git.
 */
return [
    'app' => [
        'base_url'       => 'https://methofresh.com',
        'env'            => 'production',
        'debug'          => false,
        'default_locale' => 'en',
        'locales'        => ['en', 'bn'],
    ],
    'database' => [
        'driver'   => 'mysql',
        'host'     => 'sdb-87.hosting.stackcp.net',
        'port'     => 3306,
        'name'     => 'methofresh-35313133365b',
        'username' => 'methofresh-35313133365b',
        'password' => '{.evyO<Qh,wM',
        'charset'  => 'utf8mb4',
        'prefix'   => 'wsit_',
    ],
    'session' => [
        'name'     => 'methofresh_session',
        'lifetime' => 60 * 60 * 24,
    ],
];

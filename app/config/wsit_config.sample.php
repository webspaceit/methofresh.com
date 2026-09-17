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
];

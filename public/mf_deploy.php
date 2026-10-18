<?php
// One-time deploy helper — pulls latest files from GitHub raw URLs.
// DELETE THIS FILE immediately after use.
header('Content-Type: text/plain; charset=utf-8');

$token = $_GET['t'] ?? '';
if ($token !== 'mf_deploy_2026') { http_response_code(403); exit('forbidden'); }

$base = dirname(__DIR__);
$raw  = 'https://raw.githubusercontent.com/webspaceit/methofresh.com/master/';

$files = [
    'app/Views/layouts/wsit_admin_material.php',
    'public/assets/fonts/material-symbols-outlined.woff2',
];

foreach ($files as $file) {
    $url     = $raw . $file;
    $dest    = $base . '/' . $file;
    $dir     = dirname($dest);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "mkdir: $dir\n";
    }

    $content = file_get_contents($url);
    if ($content === false) {
        echo "FAILED (fetch): $file\n";
        continue;
    }

    if (file_put_contents($dest, $content) === false) {
        echo "FAILED (write): $file\n";
        continue;
    }

    echo "OK (" . strlen($content) . " bytes): $file\n";
}

// Clear OPcache so PHP picks up changed files immediately.
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "opcache_reset: OK\n";
}

echo "DONE\n";

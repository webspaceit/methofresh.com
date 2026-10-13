<?php

/**
 * app/autoload.php — Custom PSR-4 autoloader.
 *
 * Replaces the Composer-generated vendor/autoload.php so the project
 * can run on a server where `composer install` is not available.
 * The project has zero external dependencies (composer.lock has no
 * packages), so only the App\ namespace needs to be autoloaded.
 */

spl_autoload_register(function (string $class): void {
    // Only handle classes in the App\ namespace.
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
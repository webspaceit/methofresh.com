<?php

namespace App\Core;

use PDOException;

class wsit_App
{
    private static array $services = [];
    private static array $flashes = [];
    private static string $locale = 'en';

    public wsit_Router $router;
    public wsit_Request $request;

    public function __construct()
    {
        $this->request = new wsit_Request();
        $this->router = new wsit_Router(base_url_path());
        self::$locale = requested_locale();
    }

    public static function set(string $key, mixed $value): void
    {
        self::$services[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$services[$key] ?? $default;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function setFlash(string $key, string $message): void
    {
        self::$flashes[$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        return self::$flashes[$key] ?? null;
    }

    public static function clearFlash(): void
    {
        self::$flashes = [];
    }

    public function run(): void
    {
        try {
            $db = wsit_Database::connect(db_config());
            self::set('db', $db);

            // ------------------------------------------------------------------
            // Locale resolution — single source of truth is the DB setting.
            //
            // Priority (lowest → highest):
            //   1. DB settings.default_locale  — admin-controlled site-wide default
            //   2. URL locale prefix (/en/ or /bn/) — visitor's explicit request
            //
            // Per-user and per-browser cookie overrides have been removed so that
            // the admin dashboard setting applies to everyone equally.
            // ------------------------------------------------------------------

            // Load the site-wide default from DB and apply it immediately.
            // This replaces whatever requested_locale() resolved in __construct
            // (which only had access to the cookie/URL at that point).
            $stmt = $db->get()->prepare(
                "SELECT `value` FROM " . table('settings') . " WHERE `key` = 'default_locale' LIMIT 1"
            );
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && is_locale($row['value'])) {
                self::setLocale($row['value']);
            }

            // If the URL carries an explicit locale prefix, that takes priority
            // over the global default (visitor can still switch for their session).
            $urlPath  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $base     = base_url_path();
            if ($base !== '' && str_starts_with($urlPath, $base . '/')) {
                $urlPath = substr($urlPath, strlen($base));
            }
            $firstSeg = explode('/', trim($urlPath, '/'))[0] ?? '';
            if (is_locale($firstSeg)) {
                self::setLocale($firstSeg);
            }

            $this->router->dispatch($this->request);
        } catch (PDOException $e) {
            if (app_debug()) {
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            } else {
                http_response_code(500);
                echo 'Database error. Could not establish connection.';
            }
        } catch (\Throwable $e) {
            if (app_debug()) {
                echo '<pre style="background:#1f2937;color:#f87171;padding:1rem;border-radius:8px;">'
                    . 'Error: ' . htmlspecialchars($e->getMessage())
                    . "\n" . htmlspecialchars($e->getFile() . ':' . $e->getLine())
                    . "\n\n" . htmlspecialchars($e->getTraceAsString())
                    . '</pre>';
            } else {
                http_response_code(500);
                echo '<h1>500 - Internal Server Error</h1>';
            }
        }
    }
}
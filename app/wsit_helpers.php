<?php

use App\Core\wsit_App;
use App\Core\wsit_Database;

// ---------------------------------------------------------------------------
// Application container
// ---------------------------------------------------------------------------

function app(): wsit_App
{
    static $app;
    if ($app === null) {
        $app = new wsit_App();
    }
    return $app;
}

function app_name(): string
{
    return (string) config('app.name');
}

function app_debug(): bool
{
    return (bool) config('app.debug', false);
}

function app_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    // Base config is developer-local and gitignored; fall back to the tracked
    // sample so a bare hosting upload (repo + production override) can boot.
    $base   = dirname(__DIR__) . '/app/config/wsit_config.php';
    $sample = dirname(__DIR__) . '/app/config/wsit_config.sample.php';
    if (file_exists($base)) {
        $cfg = require $base;
    } elseif (file_exists($sample)) {
        $cfg = require $sample;
    } else {
        $cfg = [];
    }
    // Load production overrides only in production env (never commit these files).
    // Also self-activates on any HTTPS request: a live server is always https,
    // so the production config can never be silently skipped after upload.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $override = dirname(__DIR__) . '/app/config/wsit_config.production.php';
    if (($cfg['app']['env'] ?? '') === 'production' || ($isHttps && file_exists($override))) {
        if (file_exists($override)) {
            $prod = require $override;
            $cfg = array_replace_recursive($cfg, $prod);
        }
    }
    return $cfg;
}

function config(string|null $key = null, mixed $default = null): mixed
{
    $config = app_config();
    if ($key === null) {
        return $config;
    }
    foreach (explode('.', $key) as $segment) {
        if (!is_array($config) || !array_key_exists($segment, $config)) {
            return $default;
        }
        $config = $config[$segment];
    }
    return $config;
}

function db_config(): array
{
    return config('database');
}

/**
 * Prefix a bare table name with the configured DB table prefix (once).
 * e.g. table('products') => 'wsit_products'
 */
function table(string $name): string
{
    static $prefix = null;
    if ($prefix === null) {
        $prefix = (string) config('database.prefix', '');
    }
    if ($prefix !== '' && !str_starts_with($name, $prefix)) {
        return $prefix . $name;
    }
    return $name;
}

// ---------------------------------------------------------------------------
// Paths
// ---------------------------------------------------------------------------

function base_path(string $append = ''): string
{
    return dirname(__DIR__) . ($append !== '' ? DIRECTORY_SEPARATOR . ltrim($append, '/\\') : '');
}

function views_path(string $append = ''): string
{
    return base_path('app/Views') . ($append !== '' ? DIRECTORY_SEPARATOR . ltrim($append, '/\\') : '');
}

function base_url_path(): string
{
    if (PHP_SAPI === 'cli') {
        return '';
    }

    // On a proper vhost (e.g. methofresh.com pointing to /public),
    // SCRIPT_NAME is /public/wsit_index.php → dirname = /public which is wrong.
    // Use the config base_url to derive the correct sub-path instead.
    $configUrl = (string) config('app.base_url', '');
    if ($configUrl !== '') {
        $parsed = parse_url($configUrl);
        $configHost = ($parsed['host'] ?? '');
        $serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        // Strip port from server host for comparison
        $serverHost = explode(':', $serverHost)[0];
        // If config host matches current host, use the config path as base
        if ($configHost !== '' && ($configHost === $serverHost || $serverHost === 'localhost')) {
            $path = rtrim($parsed['path'] ?? '', '/');
            // Remove /public suffix — the vhost document root points there
            if (str_ends_with($path, '/public')) {
                $path = substr($path, 0, -7);
            }
            return $path;
        }
        // Production: different host (e.g. methofresh.com) — base path is root
        if ($configHost !== '' && $configHost !== $serverHost) {
            return '';
        }
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Remove /public suffix when running from a vhost pointing at /public
    if (str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -7);
    }
    return rtrim($scriptDir, '/');
}

function public_url(string $path = ''): string
{
    $base = rtrim(base_url_path(), '/');
    return $base . '/' . ltrim($path, '/');
}

function public_path(string $path = ''): string
{
    return base_path('public') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
}

/**
 * Base-aware URL for a bundled asset under public/assets/ (e.g. 'css/app.css'),
 * with a cache-busting query string derived from the file's mtime.
 *
 * Bundled assets are served with a long, immutable Cache-Control (see the root
 * .htaccess), so the ?v=... token is what makes a fresh deploy show up
 * immediately instead of being served from a stale browser/edge cache.
 */
function asset_url(string $path): string
{
    $rel  = 'assets/' . ltrim($path, '/');
    $url  = public_url($rel);
    $file = public_path($rel);
    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }
    return $url;
}

function image_url(?string $path, string $fallbackText = '', int $size = 400): string
{
    $path = trim((string)$path);
    if ($path === '') {
        $letter = $fallbackText !== '' ? mb_substr($fallbackText, 0, 1) : '?';
        return 'https://placehold.co/' . $size . 'x' . $size . '/16a34a/ffffff?text=' . rawurlencode($letter);
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }
    if (!str_starts_with($path, 'assets/')) {
        $path = 'assets/img/' . basename($path);
    }
    return public_url($path);
}

/**
 * URL of the custom site logo uploaded from the admin Settings page.
 * Returns '' when no custom logo is set (views then fall back to the
 * brand-letter badge).
 */
function site_logo_url(): string
{
    static $url = false;
    if ($url !== false) {
        return $url;
    }

    $url = '';
    $db = wsit_App::get('db');
    if ($db !== null) {
        $row = $db->fetch("SELECT `value` FROM " . table('settings') . " WHERE `key` = 'site_logo' LIMIT 1");
        $path = trim((string)($row['value'] ?? ''));
        if ($path !== '') {
            $url = image_url($path);
        }
    }
    return $url;
}

/**
 * Returns ['height' => int, 'width' => int] for the logo image tag.
 * Defaults: height 40px, width auto (0 = auto).
 */
function site_logo_dimensions(): array
{
    $h = (int) site_setting('logo_height', '40');
    $w = (int) site_setting('logo_width',  '0');
    return [
        'height' => max(10, min(200, $h ?: 40)),
        'width'  => max(0,  min(600, $w)),
    ];
}

/**
 * Returns an inline style string for the logo img tag based on saved dimensions.
 * Width 0 means "auto".
 */
function site_logo_style(): string
{
    $d = site_logo_dimensions();
    // Constrain only ONE dimension and let the other follow the image's own
    // aspect ratio. Forcing both height and width turns the <img> into a fixed
    // box that "letterboxes" any non-square logo, which shows up as empty
    // (header-coloured) space above and below the artwork.
    if ($d['width'] > 0) {
        $style = 'width:' . $d['width'] . 'px;height:auto;';
    } else {
        $style = 'height:' . $d['height'] . 'px;width:auto;';
    }
    $style .= 'border-radius:17px;';
    return $style;
}

/**
 * URL of the custom favicon uploaded from the admin Settings page.
 * Returns '' when no custom favicon is set (views then fall back to the
 * built-in emoji icon).
 */
function site_favicon_url(): string
{
    $path = site_setting('site_favicon');
    return $path !== '' ? image_url($path) : '';
}

/**
 * Whether the math captcha is required on the storefront contact form.
 * Defaults to enabled until the admin turns it off in Settings.
 */
function contact_captcha_enabled(): bool
{
    return site_setting('contact_captcha', '1') === '1';
}

/**
 * Read an admin-managed site setting (footer text, logo, copyright, etc.)
 * with a fallback for when it has not been set. Cached per request.
 */
function site_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $db = wsit_App::get('db');
        if ($db !== null) {
            foreach ($db->fetchAll('SELECT `key`, `value` FROM ' . table('settings')) as $row) {
                $cache[$row['key']] = (string)$row['value'];
            }
        }
    }
    $value = trim($cache[$key] ?? '');
    return $value !== '' ? $value : $default;
}

/**
 * Announcement-bar offers: a list of
 * ['offer_en','offer_bn','coupon_en','coupon_bn','code'] rows, stored as JSON
 * under the `ann_offers` setting. If the admin never saved the JSON, fall back
 * to the legacy single-offer keys, then to the language files, so the bar
 * keeps working out of the box. An explicit (empty) JSON means "no offers".
 */
function announcement_offers(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $fields = ['offer_en', 'offer_bn', 'coupon_en', 'coupon_bn', 'code'];
    $raw = site_setting('ann_offers', '');

    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        $cache = [];
        foreach ((is_array($decoded) ? $decoded : []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($fields as $field) {
                $item[$field] = trim((string)($row[$field] ?? ''));
            }
            $cache[] = $item;
        }
        return $cache;
    }

    // No JSON yet: one offer built from the legacy keys / language defaults.
    $en = require base_path('app/Lang/wsit_en.php');
    $bn = require base_path('app/Lang/wsit_bn.php');
    $cache = [[
        'offer_en'  => site_setting('ann_offer_en', (string)($en['ann_offer'] ?? '')),
        'offer_bn'  => site_setting('ann_offer_bn', (string)($bn['ann_offer'] ?? '')),
        'coupon_en' => site_setting('ann_coupon_en', (string)($en['ann_coupon'] ?? '')),
        'coupon_bn' => site_setting('ann_coupon_bn', (string)($bn['ann_coupon'] ?? '')),
        'code'      => site_setting('ann_code', (string)($en['ann_code'] ?? '')),
    ]];
    return $cache;
}

/**
 * Footer "Quick Links" list. Falls back to the built-in links (Home /
 * Products / Categories / Cart) until the admin saves their own list.
 */
function footer_quick_links(): array
{
    $defaults = [
        ['label_en' => 'Home',       'label_bn' => 'হোম',     'url' => '/',          'enabled' => true],
        ['label_en' => 'Products',   'label_bn' => 'পণ্য',     'url' => '/products',  'enabled' => true],
        ['label_en' => 'Categories', 'label_bn' => 'ক্যাটাগরি', 'url' => '/categories','enabled' => true],
        ['label_en' => 'Cart',       'label_bn' => 'কার্ট',    'url' => '/cart',      'enabled' => true],
    ];

    $db = wsit_App::get('db');
    if ($db !== null) {
        try {
            $setting = new \App\Models\wsit_Setting();
            $raw = $setting->get('footer_quick_links', '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $defaults = $decoded;
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — fall back to built-in defaults.
        }
    }

    $template = ['label_en' => '', 'label_bn' => '', 'url' => '', 'enabled' => true];
    foreach ($defaults as &$link) {
        $link = array_merge($template, is_array($link) ? $link : []);
    }
    unset($link);

    return $defaults;
}

/**
 * The footer columns that can be shown and reordered from the Footer editor.
 * Order here is the default order; the admin can drag them into any order and
 * the result is stored as JSON under the `footer_block_order` setting.
 */
function footer_block_keys(): array
{
    return ['brand', 'links', 'contact', 'social', 'delivery', 'lang', 'payments'];
}

/**
 * Effective footer column layout: returns an array of columns, where each column
 * is an array of block keys. This allows multiple blocks to be stacked in the same column.
 * Falls back to default layout if saved data is invalid.
 */
function footer_column_layout(): array
{
    $known = footer_block_keys();
    $default = [['brand'], ['links'], ['contact', 'social'], ['delivery'], ['payments']];
    
    $db = wsit_App::get('db');
    if ($db !== null) {
        try {
            $setting = new \App\Models\wsit_Setting();
            $raw = $setting->get('footer_column_layout', '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    // Validate structure: array of arrays
                    $layout = [];
                    $usedKeys = [];
                    foreach ($decoded as $column) {
                        if (!is_array($column)) continue;
                        $validColumn = [];
                        foreach ($column as $key) {
                            if (is_string($key) && in_array($key, $known, true) && !in_array($key, $usedKeys, true)) {
                                $validColumn[] = $key;
                                $usedKeys[] = $key;
                            }
                        }
                        if (!empty($validColumn)) {
                            $layout[] = $validColumn;
                        }
                    }
                    // Add any missing blocks to the last column
                    foreach ($known as $key) {
                        if (!in_array($key, $usedKeys, true)) {
                            if (empty($layout)) {
                                $layout[] = [$key];
                            } else {
                                $layout[count($layout) - 1][] = $key;
                            }
                        }
                    }
                    if (!empty($layout)) {
                        return $layout;
                    }
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — fall back to the default layout.
        }
    }
    
    return $default;
}

/**
 * Legacy compatibility: returns flat array of all blocks in column order.
 */
function footer_block_order(): array
{
    $layout = footer_column_layout();
    $order = [];
    foreach ($layout as $column) {
        foreach ($column as $block) {
            $order[] = $block;
        }
    }
    return $order;
}

/**
 * Footer social links: a list of ['platform','url','enabled'] rows stored as
 * JSON under the `footer_social_links` setting. Empty until the admin saves
 * some from the Footer editor. Platforms map to the inline SVGs in the footer
 * partial (facebook, instagram, youtube, twitter/x, whatsapp, tiktok, linkedin).
 */
function footer_social_links(): array
{
    $links = [];

    $db = wsit_App::get('db');
    if ($db !== null) {
        try {
            $setting = new \App\Models\wsit_Setting();
            $raw = $setting->get('footer_social_links', '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $links = $decoded;
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — no social links.
        }
    }

    $template = ['platform' => '', 'url' => '', 'enabled' => true];
    foreach ($links as &$link) {
        $link = array_merge($template, is_array($link) ? $link : []);
    }
    unset($link);

    return $links;
}

/**
 * Material Symbols outlined icon names embedded as inline SVGs by the icon
 * engine in public/assets/js/icons.js (source: partials/wsit_icons.php).
 * MUST stay in sync with the ICONS map there.
 */
define('NAV_ICON_NAMES', [
    'account_balance_wallet', 'add', 'arrow_forward', 'bolt', 'calendar_today',
    'campaign', 'check', 'checklist', 'chevron_right', 'contact_support',
    'done_all', 'download', 'eco', 'expand_more', 'group', 'groups',
    'inventory', 'inventory_2', 'keyboard_arrow_down', 'language', 'local_mall',
    'local_shipping', 'location_city', 'logout', 'menu', 'more_vert',
    'notifications', 'notifications_off', 'open_in_new', 'payments',
    'pending_actions', 'percent', 'person', 'person_add', 'quiz', 'receipt',
    'receipt_long', 'repeat', 'savings', 'schedule', 'search',
    'share_location', 'shopping_basket', 'space_dashboard', 'storefront',
    'support_agent', 'task_alt', 'today', 'trending_down', 'trending_up',
    'tune', 'unfold_more', 'verified', 'visibility', 'workspace_premium',
    'add_box', 'article', 'call', 'category', 'help_center', 'home', 'mail',
    'map', 'north_east', 'translate', 'update', 'view_agenda',
]);

/**
 * Whether the given string is a valid inline-SVG icon name (see NAV_ICON_NAMES).
 * Frontend rendering only emits icon spans for known names so an unknown
 * icon never leaks raw text.
 */
function nav_icon_valid(string $icon): bool
{
    return in_array($icon, NAV_ICON_NAMES, true);
}

/**
 * Top navigation menu items for the frontend header. Empty until the admin
 * saves custom items from Settings → "Top Navigation Menu".
 */
function nav_menu_items(): array
{
    $db = wsit_App::get('db');
    if ($db !== null) {
        try {
            $setting = new \App\Models\wsit_Setting();
            $raw = $setting->get('nav_menu', '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $template = [
                        'label_en' => '',
                        'label_bn' => '',
                        'url'      => '',
                        'icon'     => '',
                        'target'   => 0,
                        'enabled'  => true,
                        'order'    => 0,
                    ];
                    foreach ($decoded as &$link) {
                        $link = array_merge($template, is_array($link) ? $link : []);
                    }
                    unset($link);
                    return $decoded;
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — no custom items.
        }
    }
    return [];
}

/**
 * Build the href for a top-nav menu item: internal paths get the current
 * locale prefix; anchors, mailto/tel and absolute URLs pass through.
 */
function nav_menu_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $first = $url[0];
    if ($first === '#' || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
        || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
        return $url;
    }
    return locale_url($url);
}

// ---------------------------------------------------------------------------
// Localization
// ---------------------------------------------------------------------------

function locales(): array
{
    return (array) config('app.locales');
}

function default_locale(): string
{
    // Prefer the site-wide setting stored in DB when a connection is available.
    $db = \App\Core\wsit_App::get('db');
    if ($db !== null) {
        $row = $db->fetch("SELECT `value` FROM " . table('settings') . " WHERE `key` = 'default_locale' LIMIT 1");
        if ($row && is_locale($row['value'])) {
            return $row['value'];
        }
    }
    // Fall back to config file (used before DB connects, e.g. in App::__construct).
    return (string) config('app.default_locale', 'en');
}

function is_locale(string $locale): bool
{
    return in_array($locale, locales(), true);
}

function requested_locale(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_url_path();
    if ($base !== '' && str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base));
    }
    $segments = explode('/', trim($path, '/'));
    $first = $segments[0] ?? '';

    if (is_locale($first)) {
        return $first;
    }

    // Fall back to cookie, then to the default locale.
    $cookie = $_COOKIE['methofresh_locale'] ?? '';
    return is_locale($cookie) ? $cookie : default_locale();
}

function app_locale(): string
{
    return wsit_App::locale();
}

function restore_locale(string $locale): void
{
    if (is_locale($locale)) {
        wsit_App::setLocale($locale);
    }
}

function locale_url(string $path = ''): string
{
    return public_url(app_locale() . $path);
}

function locale_url_for(string $locale, string $path = ''): string
{
    $base = rtrim(base_url_path(), '/');
    return $base . '/' . $locale . $path;
}

function current_page_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_url_path();
    if ($base !== '' && str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base));
    }
    $path = trim($path, '/');
    $segments = explode('/', $path);
    if (is_locale($segments[0] ?? '')) {
        array_shift($segments);
    }
    return implode('/', array_filter($segments));
}

function trans(string|null $key): string
{
    if ($key === null || $key === '') {
        return '';
    }
    $locale = app_locale();
    static $strings = [];
    if (!isset($strings[$locale])) {
        $file = base_path('app/Lang/wsit_' . $locale . '.php');
        $strings[$locale] = file_exists($file) ? require $file : [];
    }
    return $strings[$locale][$key] ?? $key;
}

// ---------------------------------------------------------------------------
// Homepage blocks (shop-by-category / featured / new arrivals)
// ---------------------------------------------------------------------------

function homepage_blocks(): array
{
    $blocks = [
        'categories' => ['id' => 'categories', 'label_key' => 'home_categories', 'visible' => true, 'order' => 1, 'title_en' => '', 'title_bn' => ''],
        'featured'   => ['id' => 'featured',   'label_key' => 'home_featured',   'visible' => true, 'order' => 2, 'title_en' => '', 'title_bn' => ''],
        'new'        => ['id' => 'new',        'label_key' => 'home_new',        'visible' => true, 'order' => 3, 'title_en' => '', 'title_bn' => ''],
    ];

    $db = wsit_App::get('db');
    if ($db === null) {
        return $blocks;
    }

    $settings = new \App\Models\wsit_Setting();
    foreach ($blocks as $key => &$definition) {
        $raw = $settings->get('home_block_' . $key, '');
        if ($raw === '') {
            continue;
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            continue;
        }
        foreach (['visible', 'order', 'title_en', 'title_bn'] as $field) {
            if (!array_key_exists($field, $json)) {
                continue;
            }
            if ($field === 'visible') {
                $definition[$field] = (bool)$json[$field];
            } elseif ($field === 'order') {
                $definition[$field] = (int)$json[$field];
            } else {
                $definition[$field] = trim((string)$json[$field]);
            }
        }
    }
    unset($definition);

    return $blocks;
}

function block_title(array $block, string $fallbackKey): string
{
    $title = app_locale() === 'bn' ? ($block['title_bn'] ?? '') : ($block['title_en'] ?? '');
    return trim((string)$title) !== '' ? (string)$title : trans($fallbackKey);
}

// ---------------------------------------------------------------------------
// Hero slides (homepage rotating banner)
// ---------------------------------------------------------------------------

/**
 * Return the homepage hero slide configuration.
 *
 * Defaults are returned when nothing has been saved yet. Stored slides
 * (JSON in the `hero_slides` setting) fully replace the defaults, and each
 * slide is merged with a template so every field always exists. The result
 * is sorted by `order`.
 */
function hero_slides(): array
{
    $defaults = [
        [
            'id'            => 1,
            'enabled'       => true,
            'order'         => 1,
            'emoji'         => '🥬',
            'title_en'      => 'Fresh & Healthy, Delivered Fast',
            'title_bn'      => 'তাজা ও স্বাস্থ্যকর, দ্রুত ডেলিভারি',
            'subtitle_en'   => 'Shop premium groceries, fruits and vegetables at great prices.',
            'subtitle_bn'   => 'সেরা মানের সবজি, ফল ও প্রতিদিনের পণ্য সাশ্রয়ী দামে কিনুন।',
            'btn1_text_en'  => 'Shop Now',
            'btn1_text_bn'  => 'এখনই কিনুন',
            'btn1_link'     => '/products',
            'btn2_text_en'  => 'Categories',
            'btn2_text_bn'  => 'ক্যাটাগরি',
            'btn2_link'     => '/categories',
            'image'         => '',
        ],
        [
            'id'            => 2,
            'enabled'       => true,
            'order'         => 2,
            'emoji'         => '🛵',
            'title_en'      => 'Same-Day Delivery',
            'title_bn'      => 'একই দিনে ডেলিভারি',
            'subtitle_en'   => 'Order before noon and get it delivered to your door today.',
            'subtitle_bn'   => 'দুপুরের আগে অর্ডার করুন, আজই দিনে পৌঁছে যাবে আপনার দরজায়।',
            'btn1_text_en'  => 'Order Now',
            'btn1_text_bn'  => 'এখনই অর্ডার করুন',
            'btn1_link'     => '/products',
            'btn2_text_en'  => 'Contact Us',
            'btn2_text_bn'  => 'যোগাযোগ',
            'btn2_link'     => '/contact',
            'image'         => '',
        ],
        [
            'id'            => 3,
            'enabled'       => true,
            'order'         => 3,
            'emoji'         => '🌿',
            'title_en'      => 'Straight From the Farm',
            'title_bn'      => 'সরাসরি খামার থেকে',
            'subtitle_en'   => 'Hand-picked produce at farm prices — no middlemen, no markup.',
            'subtitle_bn'   => 'মধ্যস্বত্বভোগী ছাড়াই খামার থেকে সরাসরি তাজা পণ্য, কৃষক দামে।',
            'btn1_text_en'  => 'Shop Now',
            'btn1_text_bn'  => 'এখনই কিনুন',
            'btn1_link'     => '/products',
            'btn2_text_en'  => 'Categories',
            'btn2_text_bn'  => 'ক্যাটাগরি',
            'btn2_link'     => '/categories',
            'image'         => '',
        ],
    ];

    $db = wsit_App::get('db');
    if ($db !== null) {
        try {
            $setting = new \App\Models\wsit_Setting();
            $raw = $setting->get('hero_slides', '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $defaults = $decoded;
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — fall back to built-in defaults.
        }
    }

    $template = [
        'id'            => 0,
        'enabled'       => true,
        'order'         => 1,
        'emoji'         => '🥬',
        'title_en'      => '',
        'title_bn'      => '',
        'subtitle_en'   => '',
        'subtitle_bn'   => '',
        'btn1_text_en'  => '',
        'btn1_text_bn'  => '',
        'btn1_link'     => '/products',
        'btn2_text_en'  => '',
        'btn2_text_bn'  => '',
        'btn2_link'     => '/categories',
        'image'         => '',
    ];

    foreach ($defaults as &$slide) {
        $slide = array_merge($template, is_array($slide) ? $slide : []);
    }
    unset($slide);

    usort($defaults, static function (array $a, array $b) {
        return ((int)($a['order'] ?? 99) <=> (int)($b['order'] ?? 99))
            ?: ((int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0));
    });

    return $defaults;
}

/**
 * Clamp a raw speed value (ms) to the allowed range.
 */
function hero_carousel_speed_clamped(string|int $value): int
{
    return min(20000, max(1000, (int)$value));
}

/**
 * Autoplay speed (in milliseconds) for the homepage hero carousel.
 * Admin-configurable, clamped to a sane range.
 */
function hero_carousel_speed(): int
{
    return hero_carousel_speed_clamped(site_setting('hero_speed', '5500'));
}

/**
 * Clamp a raw height value (px) to the allowed range.
 */
function hero_carousel_height_clamped(string|int $value): int
{
    return min(900, max(300, (int)$value));
}

/**
 * Fixed height (in px) of the homepage hero banner.
 * Admin-configurable, clamped to a sane range.
 */
function hero_carousel_height(): int
{
    return hero_carousel_height_clamped(site_setting('hero_height', '540'));
}

function e(string|null $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Render a tiny, safe subset of inline HTML (used for the editable footer
 * copyright bar, which admins may fill with e.g. a credit link).
 *
 * Only a small allow-list of inline tags survives; every attribute is dropped
 * except safe href/target/rel/class/title on <a>, and javascript: style URLs
 * are rejected. Anything else is removed, so this never echoes raw admin HTML.
 */
function safe_inline_html(string|null $html): string
{
    $html = (string) $html;
    if (trim($html) === '') {
        return '';
    }

    // 1) Drop every tag that is not on the allow-list.
    $html = strip_tags($html, '<a><b><strong><i><em><u><span><small><sub><sup><br>');

    // 2) Rebuild each remaining opening tag: anchors keep a few safe
    //    attributes, everything else loses all of them.
    return (string) preg_replace_callback('/<([a-zA-Z0-9]+)\b([^>]*)>/', static function (array $m): string {
        $tag = strtolower($m[1]);

        if ($tag !== 'a') {
            return '<' . $tag . '>';
        }

        $keep = ['href', 'target', 'rel', 'class', 'title'];
        $out  = '';
        if (preg_match_all(
            '/([a-zA-Z0-9_:-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/',
            $m[2],
            $attrs,
            PREG_SET_ORDER
        )) {
            foreach ($attrs as $attr) {
                $name  = strtolower($attr[1]);
                $value = $attr[2] ?? '';
                if ($value === '') {
                    $value = $attr[3] ?? '';
                }
                if ($value === '') {
                    $value = $attr[4] ?? '';
                }
                if (!in_array($name, $keep, true)) {
                    continue;
                }
                if ($name === 'href') {
                    $scheme = strtolower((string) parse_url(trim($value), PHP_URL_SCHEME));
                    if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                        continue;
                    }
                }
                $out .= ' ' . $name . '="' . e($value) . '"';
            }
        }

        return '<a' . $out . '>';
    }, $html);
}

// ---------------------------------------------------------------------------
// Session & authentication
// ---------------------------------------------------------------------------

function session(): ?\App\Core\wsit_Session
{
    return wsit_App::get('session');
}

function start_session(): void
{
    $name = config('session.name');
    $lifetime = (int) config('session.lifetime', 86400);
    session_name($name);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        // Send Secure automatically on HTTPS (live server), not on http dev.
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(string|null $token = null): bool
{
    $token = $token ?? ($_POST['_token'] ?? '');
    return hash_equals($_SESSION['csrf_token'] ?? '', (string)$token);
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $db = wsit_App::get('db');
        if ($db === null) {
            return null;
        }
        $stmt = $db->get()->prepare('SELECT * FROM ' . table('users') . ' WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function user_display_name(?array $user = null, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $user = $user ?? current_user();
    if (!$user) {
        return '';
    }
    $name = trim((string)($user['name'] ?? ''));
    if ($name === 'মেঠোফ্রেশ অ্যাডমিন' || $name === 'MethoFresh Admin' || ($user['email'] ?? '') === 'admin@methofresh.com') {
        return $locale === 'bn' ? 'মেঠোফ্রেশ অ্যাডমিন' : 'MethoFresh Admin';
    }
    return $name;
}

function customer_display_name(?string $name, ?string $email = null, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $name = trim((string)$name);
    if ($name === 'মেঠোফ্রেশ অ্যাডমিন' || $name === 'MethoFresh Admin' || $email === 'admin@methofresh.com') {
        return $locale === 'bn' ? 'মেঠোফ্রেশ অ্যাডমিন' : 'MethoFresh Admin';
    }
    return $name;
}

// ---------------------------------------------------------------------------
// Currency / formatting
// ---------------------------------------------------------------------------

function format_price(float $amount): string
{
    if (app_locale() === 'bn') {
        $formatted = number_format($amount, 2, '.', ',');
        // Use mb_str_split so multi-byte Bengali digits are not corrupted by str_split
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $result = str_replace($en, $bn, $formatted);
        return $result . ' টাকা';
    }
    return '৳' . number_format($amount, 2);
}

function format_number(int|float|string $number): string
{
    $formatted = (string)$number;
    if (app_locale() === 'bn') {
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $formatted = str_replace($en, $bn, $formatted);
    }
    return $formatted;
}

/**
 * Material-admin currency format matching the dashboard mockup:
 *   bn → '৳ ৩,৪২,৮৫০' (lakh grouping + Bengali digits)
 *   en → '৳ 342,850'  (thousands grouping, latin digits)
 */
function admin_price(int|float|string $amount): string
{
    $amount = (float)$amount;
    $int = (string)(int)$amount;

    if (app_locale() === 'bn') {
        // Indian/lakh grouping: 342850 => "3,42,850".
        if (strlen($int) > 3) {
            $last3 = substr($int, -3);
            $rest  = substr($int, 0, -3);
            $groups = [];
            while (strlen($rest) > 2) {
                array_unshift($groups, substr($rest, -2));
                $rest = substr($rest, 0, -2);
            }
            if ($rest !== '') {
                array_unshift($groups, $rest);
            }
            $int = implode(',', array_merge($groups, [$last3]));
        }
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return '৳ ' . str_replace($en, $bn, $int);
    }

    return '৳ ' . number_format($amount);
}

function format_date(string $date, string $format = 'Y-m-d H:i'): string
{
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }

    $formatted = date($format, $ts);

    // Convert to Bengali digits and month names when locale is bn.
    if (app_locale() === 'bn') {
        // Bengali month names matching PHP's 'M' (short) and 'F' (full) tokens.
        $monthsShort = [
            'Jan' => 'জানু', 'Feb' => 'ফেব',  'Mar' => 'মার্চ',
            'Apr' => 'এপ্রি', 'May' => 'মে',   'Jun' => 'জুন',
            'Jul' => 'জুলা', 'Aug' => 'আগ',   'Sep' => 'সেপ',
            'Oct' => 'অক্টো', 'Nov' => 'নভে',  'Dec' => 'ডিসে',
        ];
        $monthsFull = [
            'January'   => 'জানুয়ারি', 'February' => 'ফেব্রুয়ারি',
            'March'     => 'মার্চ',      'April'    => 'এপ্রিল',
            'May'       => 'মে',         'June'     => 'জুন',
            'July'      => 'জুলাই',      'August'   => 'আগস্ট',
            'September' => 'সেপ্টেম্বর', 'October'  => 'অক্টোবর',
            'November'  => 'নভেম্বর',    'December' => 'ডিসেম্বর',
        ];
        $formatted = str_replace(array_keys($monthsFull), array_values($monthsFull), $formatted);
        $formatted = str_replace(array_keys($monthsShort), array_values($monthsShort), $formatted);

        // Convert ASCII digits to Bengali digits.
        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $formatted = str_replace($en, $bn, $formatted);
    }

    return $formatted;
}

// ---------------------------------------------------------------------------
// Misc helpers shared by controllers
// ---------------------------------------------------------------------------

function slugify(string $text): string
{
    $base = strtolower(trim($text));
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim($base, '-');
    if ($base === '') {
        return 'item-' . substr(md5($text), 0, 6);
    }
    return $base;
}

// ---------------------------------------------------------------------------
// Bengali → Latin search normalization (romanized-Bengali search)
// ---------------------------------------------------------------------------

/**
 * Transliterate Bengali characters to a coarse Latin approximation so a query
 * typed in English letters ("shorisha") can match Bengali names ("সরিষা").
 * A consonant with no following vowel sign takes the Bengali inherent vowel
 * "o": তেল → "telo", সরিষা → "sorisa". Hasant joins consonants without a
 * vowel (গ্রাম → "gram…"), Bengali digits map to ASCII.
 */
function bn_to_latin(string $text): string
{
    if (!preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
        return $text; // Fast path: nothing Bengali to transliterate.
    }

    static $map = null;
    if ($map === null) {
        $map = [
            // Independent vowels
            'অ' => 'a',  'আ' => 'a',  'ই' => 'i',  'ঈ' => 'i',  'উ' => 'u',  'ঊ' => 'u',
            'ঋ' => 'ri', 'এ' => 'e',  'ঐ' => 'ai', 'ও' => 'o',  'ঔ' => 'au',
            // Consonants
            'ক' => 'k',  'খ' => 'kh', 'গ' => 'g',  'ঘ' => 'gh', 'ঙ' => 'ng',
            'চ' => 'c',  'ছ' => 'c',  'জ' => 'j',  'ঝ' => 'j',  'ঞ' => 'n',
            'ট' => 't',  'ঠ' => 't',  'ড' => 'd',  'ঢ' => 'd',  'ণ' => 'n',
            'ত' => 't',  'থ' => 't',  'দ' => 'd',  'ধ' => 'd',  'ন' => 'n',
            'প' => 'p',  'ফ' => 'f',  'ব' => 'b',  'ভ' => 'b',  'ম' => 'm',
            'য' => 'y',  'র' => 'r',  'ল' => 'l',
            'শ' => 's',  'ষ' => 's',  'স' => 's',  'হ' => 'h',
            // Nukta letters via \u escapes so the codepoints are unambiguous
            // regardless of how the literals in this file are encoded.
            "\u{09DC}" => 'r', "\u{09DD}" => 'r', "\u{09DF}" => 'y', 'ৎ' => 't',
            // Vowel signs (matras)
            'া' => 'a',  'ি' => 'i',  'ী' => 'i',  'ু' => 'u',  'ূ' => 'u',  'ৃ' => 'ri',
            'ে' => 'e',  'ৈ' => 'ai', 'ো' => 'o',  'ৌ' => 'au', 'ৗ' => 'au',
            // Signs, hasant, nukta (emitted as nothing)
            'ং' => 'ng', 'ঁ' => 'n',  'ঃ' => '',   '্' => '',   '়' => '',
            // Bengali digits
            '০' => '0',  '১' => '1',  '২' => '2',  '৩' => '3',  '৪' => '4',
            '৫' => '5',  '৬' => '6',  '৭' => '7',  '৮' => '8',  '৯' => '9',
        ];
    }

    static $consonants = null;
    if ($consonants === null) {
        $consonants = array_flip([
            'ক', 'খ', 'গ', 'ঘ', 'ঙ', 'চ', 'ছ', 'জ', 'ঝ', 'ঞ', 'ট', 'ঠ', 'ড', 'ঢ', 'ণ',
            'ত', 'থ', 'দ', 'ধ', 'ন', 'প', 'ফ', 'ব', 'ভ', 'ম', 'য', 'র', 'ল', 'শ', 'ষ',
            'স', 'হ', "\u{09DC}", "\u{09DD}", "\u{09DF}", 'ৎ',
        ]);
    }

    static $matras = null;
    if ($matras === null) {
        $matras = array_flip(['া', 'ি', 'ী', 'ু', 'ূ', 'ৃ', 'ে', 'ৈ', 'ো', 'ৌ', 'ৗ']);
    }

    // Canonicalize decomposed nukta letters (ড+়, ঢ+়, য+়) to their single
    // codepoints so both encodings of the same letter hit the same map entry.
    $text = str_replace(
        ["\u{09A1}\u{09BC}", "\u{09A2}\u{09BC}", "\u{09AF}\u{09BC}"],
        ["\u{09DC}", "\u{09DD}", "\u{09DF}"],
        $text
    );

    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    $n = count($chars);
    for ($i = 0; $i < $n; $i++) {
        $c = $chars[$i];
        if (!isset($map[$c])) {
            $out .= $c; // Latin passthrough (letters, spaces, punctuation).
            continue;
        }
        $out .= $map[$c];
        if (isset($consonants[$c])) {
            $next = $chars[$i + 1] ?? '';
            // Inherent "o" unless a vowel sign (matra) or hasant follows.
            if ($next === '' || ($next !== '্' && !isset($matras[$next]))) {
                $out .= 'o';
            }
        }
    }
    return $out;
}

/**
 * Normalize any text into the canonical search-key space. Both the product
 * side (stored in wsit_products.search_key) and the visitor's query pass
 * through this, so spelling variants collapse onto one key: "shorisha",
 * "sorisha", "sorisa" → "sorisa"; Bengali input is transliterated first.
 */
function search_normalize(string $text): string
{
    $text = bn_to_latin($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(
        ['sh', 'ch', 'th', 'kh', 'gh', 'ph', 'bh', 'dh', 'rh', 'jh', 'oo', 'ee', 'aa'],
        ['s',  'c',  't',  'k',  'g',  'f',  'b',  'd',  'r',  'j',  'u',  'i',  'a'],
        $text
    );
    $text = (string)preg_replace('/[^a-z0-9]+/', ' ', $text);
    return trim((string)preg_replace('/\s+/', ' ', $text));
}

/**
 * Canonical search key for a product: names (both locales), slug, SKU and
 * descriptions, normalized into one Latin string the storefront search and
 * the dashboard's search-log filter both LIKE against.
 */
function product_search_key(array $product): string
{
    $parts = [
        $product['name_en'] ?? '',
        $product['name_bn'] ?? '',
        $product['slug'] ?? '',
        $product['sku'] ?? '',
        $product['description_en'] ?? '',
        $product['description_bn'] ?? '',
    ];
    return search_normalize(implode(' ', $parts));
}

function price_display(array $product): array
{
    $price = (float)$product['price'];
    $sale = isset($product['sale_price']) && (float)$product['sale_price'] > 0 ? (float)$product['sale_price'] : null;
    $current = $sale ?? $price;
    $discount = ($sale !== null && $price > 0)
        ? (int)round((($price - $sale) / $price) * 100)
        : 0;
    return ['price' => $price, 'sale' => $sale, 'current' => $current, 'discount' => $discount];
}

function guest_cart(): array
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function guest_cart_count(): int
{
    $count = 0;
    foreach (guest_cart() as $item) {
        $count += (int)$item['quantity'];
    }
    return $count;
}

function guest_cart_subtotal(): float
{
    $total = 0.0;
    foreach (guest_cart() as $item) {
        $total += (float)$item['current_price'] * (int)$item['quantity'];
    }
    return $total;
}

// ---------------------------------------------------------------------------
// Payment method configuration helpers
// ---------------------------------------------------------------------------

function payment_methods_config(): array
{
    $default = [
        'cod' => [
            'key'         => 'cod',
            'enabled'     => true,
            'icon'        => '/assets/img/payment-methods/cash-on-delivery.png',
            'title_en'    => 'Cash on Delivery',
            'title_bn'    => 'ক্যাশ অন ডেলিভারি',
            'desc_en'     => 'Pay with cash upon delivery at your doorstep.',
            'desc_bn'     => 'দোরগোড়ায় পণ্য পৌঁছানোর পর নগদে মূল্য পরিশোধ করুন।',
            'account_no'  => '',
            'account_type'=> '',
            'require_trx' => false,
        ],
        'bkash' => [
            'key'         => 'bkash',
            'enabled'     => true,
            'icon'        => '/assets/img/payment-methods/bkash.png',
            'title_en'    => 'bKash',
            'title_bn'    => 'বিকাশ',
            'desc_en'     => 'Send money to our bKash number and enter the transaction ID.',
            'desc_bn'     => 'আমাদের বিকাশ নম্বরে টাকা পাঠিয়ে ট্রানজেকশন আইডি প্রদান করুন।',
            'account_no'  => '',
            'account_type'=> 'personal',
            'require_trx' => true,
        ],
        'nagad' => [
            'key'         => 'nagad',
            'enabled'     => false,
            'icon'        => '/assets/img/payment-methods/nagad.png',
            'title_en'    => 'Nagad',
            'title_bn'    => 'নগদ',
            'desc_en'     => 'Send money to our Nagad number and enter the transaction ID.',
            'desc_bn'     => 'আমাদের নগদ নম্বরে টাকা পাঠিয়ে ট্রানজেকশন আইডি প্রদান করুন।',
            'account_no'  => '',
            'account_type'=> 'personal',
            'require_trx' => true,
        ],
        'rocket' => [
            'key'         => 'rocket',
            'enabled'     => false,
            'icon'        => '/assets/img/payment-methods/rocket.png',
            'title_en'    => 'Rocket',
            'title_bn'    => 'রকেট',
            'desc_en'     => 'Send money to our DBBL Rocket account number.',
            'desc_bn'     => 'আমাদের রকেট অ্যাকাউন্ট নম্বরে টাকা পাঠিয়ে ট্রানজেকশন আইডি দিন।',
            'account_no'  => '',
            'account_type'=> 'personal',
            'require_trx' => true,
        ],
        'bank' => [
            'key'         => 'bank',
            'enabled'     => false,
            'icon'        => '/assets/img/payment-methods/bank-transfer.svg',
            'title_en'    => 'Bank Transfer',
            'title_bn'    => 'ব্যাংক ট্রান্সফার',
            'desc_en'     => 'Direct deposit to our corporate bank account.',
            'desc_bn'     => 'সরাসরি আমাদের ব্যাংক অ্যাকাউন্টে টাকা স্থানান্তর করুন।',
            'bank_name'   => '',
            'account_name'=> '',
            'account_no'  => '',
            'branch_name' => '',
            'routing_no'  => '',
            'require_trx' => false,
        ],
    ];

    try {
        $settingModel = new \App\Models\wsit_Setting();
        $raw = $settingModel->get('payment_methods', '');
        if ($raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default;
        }
        foreach ($default as $key => $val) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                $default[$key] = array_merge($val, $decoded[$key]);
            }
        }
    } catch (\Throwable $e) {
        // Fallback to default in case of DB connection unavailability during early bootstrap
    }

    return $default;
}

function active_payment_methods(string|null $locale = null): array
{
    $locale = $locale ?? app_locale();
    $all = payment_methods_config();
    $active = [];
    foreach ($all as $key => $method) {
        if (!empty($method['enabled'])) {
            // A mobile wallet must never be offered with an example or empty
            // destination number. Set the real merchant number in the admin.
            if (in_array($key, ['bkash', 'nagad', 'rocket'], true) && trim((string)($method['account_no'] ?? '')) === '') {
                continue;
            }
            $method['title'] = $locale === 'bn' ? ($method['title_bn'] ?: $method['title_en']) : $method['title_en'];
            $method['desc']  = $locale === 'bn' ? ($method['desc_bn'] ?: $method['desc_en']) : $method['desc_en'];
            $active[$key] = $method;
        }
    }
    return $active;
}

/**
 * Get all payment methods for footer display (regardless of enabled status)
 */
function all_payment_methods_for_footer(string|null $locale = null): array
{
    $locale = $locale ?? app_locale();
    $all = payment_methods_config();
    $methods = [];
    foreach ($all as $key => $method) {
        $method['title'] = $locale === 'bn' ? ($method['title_bn'] ?: $method['title_en']) : $method['title_en'];
        $method['desc']  = $locale === 'bn' ? ($method['desc_bn'] ?: $method['desc_en']) : $method['desc_en'];
        $methods[$key] = $method;
    }
    return $methods;
}

function payment_method_label(string $key, string|null $locale = null): string
{
    $locale = $locale ?? app_locale();
    $all = payment_methods_config();
    if (isset($all[$key])) {
        return $locale === 'bn' ? ($all[$key]['title_bn'] ?: $all[$key]['title_en']) : $all[$key]['title_en'];
    }
    return strtoupper($key);
}

// ---------------------------------------------------------------------------
// Shipping method configuration — conditional by locality and cart weight
// ---------------------------------------------------------------------------

/**
 * Admin-managed shipping settings (setting key: `shipping_config`, JSON).
 *
 * Shape:
 *   enabled   — master switch; off = delivery free on every order
 *   free_over — subtotal at/above which every method charges 0 (0 = never)
 *   methods   — list of {id, name_en, name_bn, enabled, base, per_kg,
 *                areas, exclude}
 *                 base/per_kg  → charge = base + per_kg × cart weight (kg)
 *                 areas        — comma keywords; when set, the method is only
 *                                offered when the entered city matches one
 *                 exclude      — comma keywords; the method is skipped when
 *                                the city matches one
 *
 * Factory default reproduces the original hardcoded rule exactly
 * (৳60 under ৳500 subtotal, free at ৳500+), so behaviour is unchanged
 * until the admin saves a configuration.
 */
function shipping_config(): array
{
    $default = [
        'enabled'   => true,
        'free_over' => 500.0,
        'methods'   => [
            [
                'id'      => 'std',
                'name_en' => 'Standard Delivery',
                'name_bn' => 'স্ট্যান্ডার্ড ডেলিভারি',
                'enabled' => true,
                'base'    => 60.0,
                'per_kg'  => 0.0,
                'areas'   => '',
                'exclude' => '',
            ],
        ],
    ];

    try {
        $raw = site_setting('shipping_config');
        if ($raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $default;
        }

        $cfg = [
            'enabled'   => !empty($decoded['enabled']),
            'free_over' => max(0.0, (float)($decoded['free_over'] ?? 0)),
            'methods'   => [],
        ];
        foreach ((array)($decoded['methods'] ?? []) as $m) {
            if (!is_array($m)) {
                continue;
            }
            $cfg['methods'][] = [
                'id'      => trim((string)($m['id'] ?? '')) !== '' ? trim((string)$m['id']) : ('m' . uniqid()),
                'name_en' => trim((string)($m['name_en'] ?? '')),
                'name_bn' => trim((string)($m['name_bn'] ?? '')),
                'enabled' => !empty($m['enabled']),
                'base'    => max(0.0, (float)($m['base'] ?? 0)),
                'per_kg'  => max(0.0, (float)($m['per_kg'] ?? 0)),
                'areas'   => trim((string)($m['areas'] ?? '')),
                'exclude' => trim((string)($m['exclude'] ?? '')),
            ];
        }
        return $cfg;
    } catch (\Throwable) {
        return $default;
    }
}

/**
 * Built-in starting list for the checkout city dropdown: all 64 districts of
 * Bangladesh (bilingual). Used until the list is first saved in the dashboard
 * (Settings → Checkout Cities).
 */
function default_checkout_cities(): array
{
    return [
        // Dhaka division
        ['name_en' => 'Dhaka', 'name_bn' => 'ঢাকা'],
        ['name_en' => 'Gazipur', 'name_bn' => 'গাজীপুর'],
        ['name_en' => 'Kishoreganj', 'name_bn' => 'কিশোরগঞ্জ'],
        ['name_en' => 'Manikganj', 'name_bn' => 'মানিকগঞ্জ'],
        ['name_en' => 'Munshiganj', 'name_bn' => 'মুন্সিগঞ্জ'],
        ['name_en' => 'Narayanganj', 'name_bn' => 'নারায়ণগঞ্জ'],
        ['name_en' => 'Narsingdi', 'name_bn' => 'নরসিংদী'],
        ['name_en' => 'Tangail', 'name_bn' => 'টাঙ্গাইল'],
        ['name_en' => 'Faridpur', 'name_bn' => 'ফরিদপুর'],
        ['name_en' => 'Gopalganj', 'name_bn' => 'গোপালগঞ্জ'],
        ['name_en' => 'Madaripur', 'name_bn' => 'মাদারীপুর'],
        ['name_en' => 'Rajbari', 'name_bn' => 'রাজবাড়ী'],
        ['name_en' => 'Shariatpur', 'name_bn' => 'শরীয়তপুর'],
        // Chattogram division
        ['name_en' => 'Chattogram', 'name_bn' => 'চট্টগ্রাম'],
        ['name_en' => "Cox's Bazar", 'name_bn' => 'কক্সবাজার'],
        ['name_en' => 'Bandarban', 'name_bn' => 'বান্দরবান'],
        ['name_en' => 'Rangamati', 'name_bn' => 'রাঙ্গামাটি'],
        ['name_en' => 'Khagrachhari', 'name_bn' => 'খাগড়াছড়ি'],
        ['name_en' => 'Feni', 'name_bn' => 'ফেনী'],
        ['name_en' => 'Lakshmipur', 'name_bn' => 'লক্ষ্মীপুর'],
        ['name_en' => 'Noakhali', 'name_bn' => 'নোয়াখালী'],
        ['name_en' => 'Cumilla', 'name_bn' => 'কুমিল্লা'],
        ['name_en' => 'Brahmanbaria', 'name_bn' => 'ব্রাহ্মণবাড়িয়া'],
        ['name_en' => 'Chandpur', 'name_bn' => 'চাঁদপুর'],
        // Rajshahi division
        ['name_en' => 'Rajshahi', 'name_bn' => 'রাজশাহী'],
        ['name_en' => 'Joypurhat', 'name_bn' => 'জয়পুরহাট'],
        ['name_en' => 'Naogaon', 'name_bn' => 'নওগাঁ'],
        ['name_en' => 'Natore', 'name_bn' => 'নাটোর'],
        ['name_en' => 'Chapainawabganj', 'name_bn' => 'চাঁপাইনবাবগঞ্জ'],
        ['name_en' => 'Pabna', 'name_bn' => 'পাবনা'],
        ['name_en' => 'Sirajganj', 'name_bn' => 'সিরাজগঞ্জ'],
        ['name_en' => 'Bogura', 'name_bn' => 'বগুড়া'],
        // Khulna division
        ['name_en' => 'Khulna', 'name_bn' => 'খুলনা'],
        ['name_en' => 'Bagerhat', 'name_bn' => 'বাগেরহাট'],
        ['name_en' => 'Satkhira', 'name_bn' => 'সাতক্ষীরা'],
        ['name_en' => 'Jashore', 'name_bn' => 'যশোর'],
        ['name_en' => 'Jhenaidah', 'name_bn' => 'ঝিনাইদহ'],
        ['name_en' => 'Magura', 'name_bn' => 'মাগুরা'],
        ['name_en' => 'Meherpur', 'name_bn' => 'মেহেরপুর'],
        ['name_en' => 'Narail', 'name_bn' => 'নড়াইল'],
        ['name_en' => 'Kushtia', 'name_bn' => 'কুষ্টিয়া'],
        ['name_en' => 'Chuadanga', 'name_bn' => 'চুয়াডাঙ্গা'],
        // Barishal division
        ['name_en' => 'Barishal', 'name_bn' => 'বরিশাল'],
        ['name_en' => 'Patuakhali', 'name_bn' => 'পটুয়াখালী'],
        ['name_en' => 'Bhola', 'name_bn' => 'ভোলা'],
        ['name_en' => 'Pirojpur', 'name_bn' => 'পিরোজপুর'],
        ['name_en' => 'Barguna', 'name_bn' => 'বরগুনা'],
        ['name_en' => 'Jhalokathi', 'name_bn' => 'ঝালকাঠি'],
        // Sylhet division
        ['name_en' => 'Sylhet', 'name_bn' => 'সিলেট'],
        ['name_en' => 'Moulvibazar', 'name_bn' => 'মৌলভীবাজার'],
        ['name_en' => 'Habiganj', 'name_bn' => 'হবিগঞ্জ'],
        ['name_en' => 'Sunamganj', 'name_bn' => 'সুনামগঞ্জ'],
        // Rangpur division
        ['name_en' => 'Rangpur', 'name_bn' => 'রংপুর'],
        ['name_en' => 'Dinajpur', 'name_bn' => 'দিনাজপুর'],
        ['name_en' => 'Thakurgaon', 'name_bn' => 'ঠাকুরগাঁও'],
        ['name_en' => 'Panchagarh', 'name_bn' => 'পঞ্চগড়'],
        ['name_en' => 'Nilphamari', 'name_bn' => 'নীলফামারী'],
        ['name_en' => 'Lalmonirhat', 'name_bn' => 'লালমনিরহাট'],
        ['name_en' => 'Kurigram', 'name_bn' => 'কুড়িগ্রাম'],
        ['name_en' => 'Gaibandha', 'name_bn' => 'গাইবান্ধা'],
        // Mymensingh division
        ['name_en' => 'Mymensingh', 'name_bn' => 'ময়মনসিংহ'],
        ['name_en' => 'Jamalpur', 'name_bn' => 'জামালপুর'],
        ['name_en' => 'Netrokona', 'name_bn' => 'নেত্রকোণা'],
        ['name_en' => 'Sherpur', 'name_bn' => 'শেরপুর'],
    ];
}

/**
 * Checkout city dropdown options (dashboard → Settings → Checkout Cities).
 * Setting absent = the built-in 64 districts; stored empty list = the admin
 * cleared every row (the checkout field then falls back to a text input).
 */
function checkout_cities(): array
{
    try {
        $raw = site_setting('checkout_cities');
        if ($raw === '') {
            return default_checkout_cities();
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return default_checkout_cities();
        }
        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $en = trim((string)($row['name_en'] ?? ''));
            $bn = trim((string)($row['name_bn'] ?? ''));
            if ($en === '' && $bn === '') {
                continue;
            }
            $out[] = ['name_en' => $en, 'name_bn' => $bn];
        }
        return $out;
    } catch (\Throwable) {
        return default_checkout_cities();
    }
}

/** Dropdown value → every spelling of that district ([bn, en]) — for the JS preview. */
function checkout_city_alts(): array
{
    $map = [];
    foreach (checkout_cities() as $c) {
        $pair = [];
        foreach (['name_bn', 'name_en'] as $field) {
            $v = trim((string)$c[$field]);
            if ($v !== '' && !in_array($v, $pair, true)) {
                $pair[] = $v;
            }
        }
        foreach ($pair as $val) {
            $map[$val] = $pair;
        }
    }
    return $map;
}

/**
 * The entered city plus its BN/EN counterparts from the dropdown list —
 * so shipping keywords written in the other language still match.
 */
function checkout_city_spellings(string $city): array
{
    $spellings = [mb_strtolower(trim($city))];
    $entered = trim($city);
    if ($entered !== '') {
        foreach (checkout_cities() as $c) {
            $bn = trim((string)$c['name_bn']);
            $en = trim((string)$c['name_en']);
            $hit = ($bn !== '' && strcasecmp($bn, $entered) === 0)
                || ($en !== '' && strcasecmp($en, $entered) === 0);
            if (!$hit) {
                continue;
            }
            foreach ([$bn, $en] as $s) {
                $low = mb_strtolower($s);
                if ($s !== '' && !in_array($low, $spellings, true)) {
                    $spellings[] = $low;
                }
            }
            break;
        }
    }
    return $spellings;
}

/** Does the entered city match any comma-separated keyword? (case-insensitive)
 * Cities from the dropdown also match via their other-language spelling —
 * "Dhaka" hits a "ঢাকা" keyword and vice versa. */
function shipping_city_matches(string $city, string $keywords): bool
{
    if (trim($city) === '' || trim($keywords) === '') {
        return false;
    }
    $spellings = checkout_city_spellings($city);
    foreach (explode(',', $keywords) as $keyword) {
        $keyword = mb_strtolower(trim($keyword));
        if ($keyword === '') {
            continue;
        }
        foreach ($spellings as $spelling) {
            if (mb_strpos($spelling, $keyword) !== false) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Is this method offered for the given city? A null city means "locality
 * unknown" (cart page estimate) → only the enabled flag is checked.
 */
function shipping_method_available(array $method, ?string $city): bool
{
    if (empty($method['enabled'])) {
        return false;
    }
    if ($city === null) {
        return true;
    }
    $areas = (string)($method['areas'] ?? '');
    if (trim($areas) !== '' && !shipping_city_matches($city, $areas)) {
        return false;
    }
    $exclude = (string)($method['exclude'] ?? '');
    if (trim($exclude) !== '' && shipping_city_matches($city, $exclude)) {
        return false;
    }
    return true;
}

/** Enabled methods (master switch on) available for the city — [] = free. */
function shipping_available_methods(?string $city = null): array
{
    $cfg = shipping_config();
    if (!$cfg['enabled']) {
        return [];
    }
    $available = [];
    foreach ($cfg['methods'] as $method) {
        if (shipping_method_available($method, $city)) {
            $available[] = $method;
        }
    }
    return $available;
}

/** Charge for one method: base + per_kg × weight, 0 when the subtotal is at/above the free threshold. */
function shipping_method_charge(array $method, float $subtotal, float $weight, float|null $freeOver = null): float
{
    $freeOver = $freeOver ?? shipping_config()['free_over'];
    if ($freeOver > 0 && $subtotal >= $freeOver) {
        return 0.0;
    }
    return round(max(0.0, (float)$method['base'] + (float)$method['per_kg'] * max(0.0, $weight)), 2);
}

/** Total weight (kg) of the guest cart — items carry `weight` after hydration. */
function cart_total_weight(): float
{
    $weight = 0.0;
    foreach (guest_cart() as $item) {
        $weight += (float)($item['weight'] ?? 0) * max(1, (int)($item['quantity'] ?? 1));
    }
    return round($weight, 3);
}

/** Method name in the given (or current) locale, falling back to the other language. */
function shipping_method_name(array $method, string|null $locale = null): string
{
    $locale = $locale ?? app_locale();
    $en = trim((string)($method['name_en'] ?? ''));
    $bn = trim((string)($method['name_bn'] ?? ''));
    if ($locale === 'bn') {
        return $bn !== '' ? $bn : ($en !== '' ? $en : trans('shipping'));
    }
    return $en !== '' ? $en : ($bn !== '' ? $bn : trans('shipping'));
}

/** The address all storefront mail is sent from and replies to. */
function mail_from_address(): string
{
    $email = trim((string)site_setting('contact_email', 'support@methofresh.com'));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'support@methofresh.com';
}

/**
 * RFC 5322 `Date:` value. PHP's mail() omits the header, and its absence is a
 * spam signal — so every message we build carries an explicit one.
 */
function mail_rfc_date(): string
{
    return date('D, j M Y H:i:s O');
}

/**
 * Unique `Message-ID:` value rooted in the sending domain. Missing Message-ID
 * is another strong spam signal, so the header is always emitted.
 */
function mail_message_id(): string
{
    $domain = 'methofresh.com';
    $from = mail_from_address();
    if (preg_match('/@([A-Za-z0-9.-]+)$/', $from, $m)) {
        $domain = $m[1];
    }
    $host = php_uname('n');
    if ($host === '' || $host === false) {
        $host = $domain;
    }
    return '<' . bin2hex(random_bytes(16)) . '.' . time() . '@' . $host . '>';
}

/**
 * Strip HTML down to a readable plain-text alternative. Spam filters penalise
 * HTML-only mail, so every HTML message ships a text/plain part alongside it.
 */
function mail_html_to_text(string $html): string
{
    $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
    $text = preg_replace('#<br\s*/?>#i', "\n", $text);
    // Cells become tab-separated so a row reads as a line, not stacked words.
    $text = preg_replace('#</t[dh]>#i', "\t", $text);
    $text = preg_replace('#</(p|div|tr|table|h[1-6]|li)>#i', "\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xc2\xa0", ' ', $text);
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/ *\n */', "\n", $text);
    $text = preg_replace('/\t+/', "\t", $text);
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}

/**
 * Encode one MIME header value, folding long ASCII runs so no line exceeds the
 * RFC 2047 / 5322 limits (long Subjects otherwise get truncated or garbled).
 */
function mail_encode_header(string $value): string
{
    $value = trim(str_replace(["\r", "\n"], ' ', $value));
    if ($value === '') {
        return '';
    }
    if (preg_match('/[\x80-\xFF]/', $value)) {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
    return preg_replace('/(.{76})(?=.)/', "$1\r\n ", $value);
}

/**
 * Single place that builds and sends storefront mail.
 *
 * Adds the headers PHP's mail() leaves out (Date, Message-ID), sends a
 * multipart/alternative body when HTML is supplied, and adds List-Unsubscribe
 * for bulk/marketing mail. Every caller is best-effort: this returns false
 * instead of throwing so a mail failure can never break a request.
 *
 * @param string        $to      Recipient address.
 * @param string        $subject Subject line, encoded as needed.
 * @param string        $html    HTML body, or '' for a plain-text message.
 * @param string        $text    Plain-text body; derived from $html when ''.
 * @param string        $replyTo Reply-To address, ignored when not valid.
 * @param string        $from    From address; defaults to the storefront's.
 * @param string[]|null $unsub   Endpoints for List-Unsubscribe, or null.
 */
function mf_send_mail(
    string $to,
    string $subject,
    string $html = '',
    string $text = '',
    string $replyTo = '',
    string $from = '',
    ?array $unsub = null
): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = mail_from_address();
    }

    $html = trim($html);
    $text = trim($text);
    if ($text === '' && $html !== '') {
        $text = mail_html_to_text($html);
    }
    if ($text === '') {
        $text = trim(strip_tags($html));
    }
    if ($text === '') {
        return false;
    }

    $hasHtml = $html !== '';

    $headers = [];
    $headers[] = 'From: ' . mail_encode_header(app_name()) . ' <' . $from . '>';
    if (filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: <' . $replyTo . '>';
    }
    $headers[] = 'Date: ' . mail_rfc_date();
    $headers[] = 'Message-ID: ' . mail_message_id();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    if ($unsub !== null && $unsub) {
        $wrapped = [];
        foreach ($unsub as $endpoint) {
            $endpoint = trim((string)$endpoint);
            if ($endpoint === '') {
                continue;
            }
            // Tolerate callers that already supplied the angle brackets.
            $wrapped[] = ($endpoint[0] === '<') ? $endpoint : '<' . $endpoint . '>';
        }
        if ($wrapped) {
            $headers[] = 'List-Unsubscribe: ' . implode(', ', $wrapped);
        }
    }

    if ($hasHtml) {
        // text/plain first, as RFC 2046 says the least-faithful part goes first.
        $boundary = '=_mf_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($text), 76, "\r\n") . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html), 76, "\r\n") . "\r\n"
                . "--{$boundary}--";
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $body = chunk_split(base64_encode($text), 76, "\r\n");
    }

    $subject = mail_encode_header($subject);

    // No $additional_params: the host's sendmail wrapper does not tolerate
    // envelope flags (-f/-r) and fails the whole call with them, so the
    // message is sent with mail() defaults. Deliverability comes from the
    // headers above plus DKIM at the mail host, not from the envelope.
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function send_order_status_email(array $order, string $oldStatus, string $newStatus): bool
{
    $email = trim((string)($order['shipping_email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName = app_name();
    $locale = app_locale();
    $statusLabels = [
        'pending'     => $locale === 'bn' ? 'পেন্ডিং' : 'Pending',
        'processing'  => $locale === 'bn' ? 'প্রসেসিং' : 'Processing',
        'shipped'     => $locale === 'bn' ? 'শিপড' : 'Shipped',
        'delivered'   => $locale === 'bn' ? 'ডেলিভারড' : 'Delivered',
        'cancelled'   => $locale === 'bn' ? 'ক্যান্সেলড' : 'Cancelled',
    ];

    $itemsHtml = '';
    foreach (($order['items'] ?? []) as $item) {
        $itemsHtml .= '<tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;">' . e($item['product_name']) . '</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;text-align:center;">' . (int)$item['quantity'] . '</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;text-align:right;">' . e(format_price((float)$item['total'])) . '</td>
        </tr>';
    }

    $subject = '[' . $siteName . '] ' . ($locale === 'bn' ? 'আপনার অর্ডারের স্ট্যাটাস পরিবর্তন' : 'Your Order Status Has Been Updated') . ' — ' . e($order['order_number']);

    $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
    $newLabel = $statusLabels[$newStatus] ?? $newStatus;

    $body = '
    <div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:#16a34a; color:#fff; padding:16px 20px; border-radius:8px 8px 0 0; font-size:18px; font-weight:bold;">
            ' . e($siteName) . '
        </div>
        <div style="border:1px solid #e2e8f0; border-top:none; padding:24px; background:#fff; border-radius:0 0 8px 8px;">
            <p style="font-size:15px; color:#333; margin:0 0 16px;">' . sprintf(
                $locale === 'bn' ? 'আপনার অর্ডার %s এর স্ট্যাটাস পরিবর্তন হয়েছে।' : 'Your order %s status has been updated.',
                '<strong>' . e($order['order_number']) . '</strong>'
            ) . '</p>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px 16px; margin-bottom:20px;">
                <p style="font-size:13px; color:#555; margin:0 0 4px;">' . ($locale === 'bn' ? 'পূর্ববর্তী স্ট্যাটাস' : 'Previous Status') . ': <strong>' . e($oldLabel) . '</strong></p>
                <p style="font-size:13px; color:#555; margin:0;">' . ($locale === 'bn' ? 'নতুন স্ট্যাটাস' : 'New Status') . ': <strong style="color:#16a34a;">' . e($newLabel) . '</strong></p>
            </div>

            <h2 style="font-size:15px; color:#333; margin:0 0 10px;">' . ($locale === 'bn' ? 'অর্ডার আইটেম' : 'Order Items') . '</h2>
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:8px 12px; text-align:left; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'পণ্য' : 'Item') . '</th>
                        <th style="padding:8px 12px; text-align:center; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'পরিমাণ' : 'Qty') . '</th>
                        <th style="padding:8px 12px; text-align:right; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'মোট' : 'Total') . '</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
            </table>

            <p style="font-size:14px; color:#333; margin:0 0 4px; text-align:right;">
                <strong>' . ($locale === 'bn' ? 'মোট মূল্য:' : 'Grand Total:') . '</strong> ' . e(format_price((float)$order['total'])) . '
            </p>

            <hr style="border:none; border-top:1px solid #e2e8f0; margin:20px 0;">

            <p style="font-size:12px; color:#94a3b8; margin:0;">
                ' . ($locale === 'bn' ? 'এই ইমেইলটি MethoFresh থেকে পাঠানো হয়েছে। আপনার প্রশ্নের জন্য ' : 'This email was sent by ' . $siteName . '. For any questions, please contact ') . '<a href="mailto:support@methofresh.com" style="color:#16a34a;">support@methofresh.com</a>.
            </p>
        </div>
    </div>';

    // Envelope sender = published From address, so bounces and SPF test the
    // same domain. -f is not offered here: $email is the envelope recipient.
    return mf_send_mail($email, $subject, $body, '', 'support@methofresh.com');
}

/**
 * Order confirmation for the customer, sent the moment checkout succeeds.
 *
 * Distinct from send_order_status_email(), which only fires when an admin
 * moves the order off `pending`; without this, a customer who just bought
 * something hears nothing at all. Best effort — returns false rather than
 * throwing so a mail failure can never affect checkout.
 */
function send_customer_order_confirmation_email(array $order): bool
{
    $email = trim((string)($order['shipping_email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName = app_name();
    $locale = app_locale();

    $itemsHtml = '';
    foreach (($order['items'] ?? []) as $item) {
        $itemsHtml .= '<tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;">' . e($item['product_name']) . '</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;text-align:center;">' . (int)$item['quantity'] . '</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;text-align:right;">' . e(format_price((float)$item['total'])) . '</td>
        </tr>';
    }

    $orderNumber = (string)($order['order_number'] ?? '');
    $subject = '[' . $siteName . '] ' . ($locale === 'bn' ? 'অর্ডার নিশ্চিত হয়েছে' : 'Order Confirmed') . ' — ' . $orderNumber;

    $row = static function (string $label, string $value): string {
        if (trim($value) === '') {
            return '';
        }
        return '<tr>'
            . '<td style="padding:6px 12px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;white-space:nowrap;vertical-align:top;">' . e($label) . '</td>'
            . '<td style="padding:6px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#333;">' . $value . '</td>'
            . '</tr>';
    };

    $address = trim((string)($order['shipping_address'] ?? ''));
    $cityLine = trim((string)($order['shipping_city'] ?? ''));
    if (trim((string)($order['shipping_postal'] ?? '')) !== '') {
        $cityLine .= ($cityLine !== '' ? ' - ' : '') . trim((string)$order['shipping_postal']);
    }

    $body = '
    <div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:#16a34a; color:#fff; padding:16px 20px; border-radius:8px 8px 0 0; font-size:18px; font-weight:bold;">
            ' . e($siteName) . '
        </div>
        <div style="border:1px solid #e2e8f0; border-top:none; padding:24px; background:#fff; border-radius:0 0 8px 8px;">
            <h2 style="font-size:17px; color:#166534; margin:0 0 12px;">' . ($locale === 'bn' ? 'আপনার অর্ডার নিশ্চিত হয়েছে' : 'Your order is confirmed') . '</h2>
            <p style="font-size:15px; color:#333; margin:0 0 6px;">' . sprintf(
                $locale === 'bn' ? 'ধন্যবাদ! আপনার অর্ডার %s সফলভাবে গ্রহণ করা হয়েছে।' : 'Thank you! We have received your order %s.',
                '<strong>' . e($orderNumber) . '</strong>'
            ) . '</p>
            <p style="font-size:14px; color:#555; margin:0 0 20px;">' . ($locale === 'bn'
                ? 'আমরা শীঘ্রই ডেলিভারি নিশ্চিত করতে আপনার সাথে যোগাযোগ করব।'
                : 'We will contact you shortly to confirm delivery.') . '</p>

            <h3 style="font-size:14px; color:#333; margin:0 0 8px;">' . ($locale === 'bn' ? 'ডেলিভারি ঠিকানা' : 'Delivery Details') . '</h3>
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                ' . $row($locale === 'bn' ? 'নাম' : 'Name', e((string)($order['shipping_name'] ?? ''))) . '
                ' . $row($locale === 'bn' ? 'ফোন' : 'Phone', e((string)($order['shipping_phone'] ?? ''))) . '
                ' . $row($locale === 'bn' ? 'ঠিকানা' : 'Address', nl2br(e($address))) . '
                ' . $row($locale === 'bn' ? 'শহর' : 'City', e($cityLine)) . '
                ' . $row($locale === 'bn' ? 'শিপিং' : 'Shipping', e((string)($order['shipping_method'] ?? ''))) . '
            </table>

            <h3 style="font-size:14px; color:#333; margin:0 0 8px;">' . ($locale === 'bn' ? 'অর্ডার আইটেম' : 'Order Items') . '</h3>
            <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:8px 12px; text-align:left; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'পণ্য' : 'Item') . '</th>
                        <th style="padding:8px 12px; text-align:center; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'পরিমাণ' : 'Qty') . '</th>
                        <th style="padding:8px 12px; text-align:right; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'মোট' : 'Total') . '</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
            </table>

            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    <td style="padding:4px 12px; font-size:13px; color:#64748b; text-align:right;">' . ($locale === 'bn' ? 'সাবটোটাল:' : 'Subtotal:') . '</td>
                    <td style="padding:4px 12px; font-size:13px; color:#333; text-align:right; width:120px;">' . e(format_price((float)($order['subtotal'] ?? 0))) . '</td>
                </tr>
                ' . ((float)($order['discount'] ?? 0) > 0 ? '<tr>
                    <td style="padding:4px 12px; font-size:13px; color:#64748b; text-align:right;">' . ($locale === 'bn' ? 'ছাড়:' : 'Discount:') . '</td>
                    <td style="padding:4px 12px; font-size:13px; color:#333; text-align:right;">- ' . e(format_price((float)$order['discount'])) . '</td>
                </tr>' : '') . '
                <tr>
                    <td style="padding:4px 12px; font-size:13px; color:#64748b; text-align:right;">' . ($locale === 'bn' ? 'শিপিং:' : 'Shipping:') . '</td>
                    <td style="padding:4px 12px; font-size:13px; color:#333; text-align:right;">' . e(format_price((float)($order['shipping'] ?? 0))) . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px; font-size:14px; color:#333; text-align:right; border-top:2px solid #e2e8f0;"><strong>' . ($locale === 'bn' ? 'সর্বমোট:' : 'Grand Total:') . '</strong></td>
                    <td style="padding:8px 12px; font-size:14px; color:#16a34a; text-align:right; border-top:2px solid #e2e8f0;"><strong>' . e(format_price((float)($order['total'] ?? 0))) . '</strong></td>
                </tr>
            </table>

            <hr style="border:none; border-top:1px solid #e2e8f0; margin:20px 0;">

            <p style="font-size:12px; color:#94a3b8; margin:0;">
                ' . ($locale === 'bn'
                    ? 'এই ইমেইলটি ' . e($siteName) . ' থেকে পাঠানো হয়েছে। কোনো প্রশ্ন থাকলে '
                    : 'This email was sent by ' . e($siteName) . '. For any questions, please contact ')
                . '<a href="mailto:support@methofresh.com" style="color:#16a34a;">support@methofresh.com</a>.
            </p>
        </div>
    </div>';

    return mf_send_mail($email, $subject, $body, '', 'support@methofresh.com');
}

/**
 * Notify the shop owner that a new order came in.
 *
 * Sent to the same address as contact-form mail (Dashboard → Settings →
 * Contact Notification Email), so the owner has one place to configure where
 * store notifications land. Best effort: returns false rather than throwing so
 * a mail failure can never affect checkout.
 */
function send_admin_new_order_email(array $order): bool
{
    $to = contact_recipient_email();
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName = app_name();
    $locale = app_locale();

    $itemsHtml = '';
    foreach (($order['items'] ?? []) as $item) {
        $itemsHtml .= '<tr>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;">' . e($item['product_name']) . '</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;text-align:center;">' . (int)$item['quantity'] . '</td>
            <td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#444;text-align:right;">' . e(format_price((float)$item['total'])) . '</td>
        </tr>';
    }

    $orderNumber = (string)($order['order_number'] ?? '');
    $subject = '[' . $siteName . '] ' . ($locale === 'bn' ? 'নতুন অর্ডার' : 'New Order') . ' — ' . $orderNumber;

    // Label => value rows for the customer/shipping block.
    $row = static function (string $label, string $value): string {
        if (trim($value) === '') {
            return '';
        }
        return '<tr>'
            . '<td style="padding:6px 12px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;white-space:nowrap;vertical-align:top;">' . e($label) . '</td>'
            . '<td style="padding:6px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#333;">' . $value . '</td>'
            . '</tr>';
    };

    $customerEmail = trim((string)($order['shipping_email'] ?? ''));
    $emailCell = filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
        ? '<a href="mailto:' . e($customerEmail) . '" style="color:#16a34a;">' . e($customerEmail) . '</a>'
        : e($customerEmail);

    $address = trim((string)($order['shipping_address'] ?? ''));
    $cityLine = trim((string)($order['shipping_city'] ?? ''));
    if (trim((string)($order['shipping_postal'] ?? '')) !== '') {
        $cityLine .= ($cityLine !== '' ? ' - ' : '') . trim((string)$order['shipping_postal']);
    }

    $paymentLabel = (string)($order['payment_method'] ?? '');
    $trxId = trim((string)($order['transaction_id'] ?? ''));

    $notes = trim((string)($order['notes'] ?? ''));

    $body = '
    <div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:#16a34a; color:#fff; padding:16px 20px; border-radius:8px 8px 0 0; font-size:18px; font-weight:bold;">
            ' . e($siteName) . ' — ' . ($locale === 'bn' ? 'নতুন অর্ডার' : 'New Order') . '
        </div>
        <div style="border:1px solid #e2e8f0; border-top:none; padding:24px; background:#fff; border-radius:0 0 8px 8px;">
            <p style="font-size:15px; color:#333; margin:0 0 6px;">' . sprintf(
                $locale === 'bn' ? 'নতুন অর্ডার %s পাওয়া গেছে।' : 'A new order %s has been placed.',
                '<strong>' . e($orderNumber) . '</strong>'
            ) . '</p>
            <p style="font-size:13px; color:#64748b; margin:0 0 20px;">' . e(date('Y-m-d H:i')) . '</p>

            <h2 style="font-size:14px; color:#333; margin:0 0 8px;">' . ($locale === 'bn' ? 'গ্রাহকের তথ্য' : 'Customer') . '</h2>
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                ' . $row($locale === 'bn' ? 'নাম' : 'Name', e((string)($order['shipping_name'] ?? ''))) . '
                ' . $row($locale === 'bn' ? 'ফোন' : 'Phone', e((string)($order['shipping_phone'] ?? ''))) . '
                ' . $row($locale === 'bn' ? 'ইমেইল' : 'Email', $emailCell) . '
                ' . $row($locale === 'bn' ? 'ঠিকানা' : 'Address', nl2br(e($address))) . '
                ' . $row($locale === 'bn' ? 'শহর' : 'City', e($cityLine)) . '
                ' . $row($locale === 'bn' ? 'শিপিং' : 'Shipping', e((string)($order['shipping_method'] ?? ''))) . '
                ' . $row($locale === 'bn' ? 'পেমেন্ট' : 'Payment', e($paymentLabel) . ($trxId !== '' ? ' / ' . e($trxId) : '')) . '
                ' . $row($locale === 'bn' ? 'নোট' : 'Notes', nl2br(e($notes))) . '
            </table>

            <h2 style="font-size:14px; color:#333; margin:0 0 8px;">' . ($locale === 'bn' ? 'অর্ডার আইটেম' : 'Order Items') . '</h2>
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:8px 12px; text-align:left; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'পণ্য' : 'Item') . '</th>
                        <th style="padding:8px 12px; text-align:center; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'পরিমাণ' : 'Qty') . '</th>
                        <th style="padding:8px 12px; text-align:right; font-size:12px; color:#64748b; border-bottom:2px solid #e2e8f0; text-transform:uppercase;">' . ($locale === 'bn' ? 'মোট' : 'Total') . '</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
            </table>

            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                <tr>
                    <td style="padding:4px 12px; font-size:13px; color:#64748b; text-align:right;">' . ($locale === 'bn' ? 'সাবটোটাল:' : 'Subtotal:') . '</td>
                    <td style="padding:4px 12px; font-size:13px; color:#333; text-align:right; width:120px;">' . e(format_price((float)($order['subtotal'] ?? 0))) . '</td>
                </tr>
                ' . ((float)($order['discount'] ?? 0) > 0 ? '<tr>
                    <td style="padding:4px 12px; font-size:13px; color:#64748b; text-align:right;">' . ($locale === 'bn' ? 'ছাড়:' : 'Discount:') . '</td>
                    <td style="padding:4px 12px; font-size:13px; color:#333; text-align:right;">- ' . e(format_price((float)$order['discount'])) . '</td>
                </tr>' : '') . '
                <tr>
                    <td style="padding:4px 12px; font-size:13px; color:#64748b; text-align:right;">' . ($locale === 'bn' ? 'শিপিং:' : 'Shipping:') . '</td>
                    <td style="padding:4px 12px; font-size:13px; color:#333; text-align:right;">' . e(format_price((float)($order['shipping'] ?? 0))) . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px; font-size:14px; color:#333; text-align:right; border-top:2px solid #e2e8f0;"><strong>' . ($locale === 'bn' ? 'সর্বমোট:' : 'Grand Total:') . '</strong></td>
                    <td style="padding:8px 12px; font-size:14px; color:#16a34a; text-align:right; border-top:2px solid #e2e8f0;"><strong>' . e(format_price((float)($order['total'] ?? 0))) . '</strong></td>
                </tr>
            </table>

            <hr style="border:none; border-top:1px solid #e2e8f0; margin:20px 0;">
            <p style="font-size:12px; color:#94a3b8; margin:0;">
                ' . ($locale === 'bn' ? 'ড্যাশবোর্ড থেকে এই অর্ডারটি প্রসেস করুন।' : 'Process this order from the dashboard.') . '
            </p>
        </div>
    </div>';

    return mf_send_mail($to, $subject, $body, '', $customerEmail);
}

function contact_recipient_email(): string
{
    $email = trim((string)site_setting('contact_email', 'support@methofresh.com'));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'support@methofresh.com';
}

function send_contact_message_email(array $data): bool
{
    $to = contact_recipient_email();
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $siteName = app_name();
    $locale = app_locale();
    $name    = trim((string)($data['name'] ?? ''));
    $email   = trim((string)($data['email'] ?? ''));
    $phone   = trim((string)($data['phone'] ?? ''));
    $subject = trim((string)($data['subject'] ?? ''));
    $message = trim((string)($data['message'] ?? ''));

    $mailSubject = '[' . $siteName . '] ' . ($locale === 'bn' ? 'নতুন যোগাযোগ বার্তা' : 'New Contact Message') . ': ' . $subject;

    $row = static function (string $label, string $value): string {
        return '<tr>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:12px;color:#64748b;text-transform:uppercase;width:120px;vertical-align:top;">' . e($label) . '</td>'
            . '<td style="padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:14px;color:#333;">' . $value . '</td>'
            . '</tr>';
    };

    $body = '
    <div style="font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width:600px; margin:0 auto; padding:20px;">
        <div style="background:#16a34a; color:#fff; padding:16px 20px; border-radius:8px 8px 0 0; font-size:18px; font-weight:bold;">
            ' . e($siteName) . '
        </div>
        <div style="border:1px solid #e2e8f0; border-top:none; padding:24px; background:#fff; border-radius:0 0 8px 8px;">
            <h2 style="font-size:16px; color:#333; margin:0 0 16px;">' . ($locale === 'bn' ? 'নতুন যোগাযোগ বার্তা' : 'New Contact Message') . '</h2>
            <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                ' . $row($locale === 'bn' ? 'নাম' : 'Name', e($name)) . '
                ' . $row($locale === 'bn' ? 'ইমেইল' : 'Email', filter_var($email, FILTER_VALIDATE_EMAIL) ? '<a href="mailto:' . e($email) . '" style="color:#16a34a;">' . e($email) . '</a>' : e($email)) . '
                ' . ($phone !== '' ? $row($locale === 'bn' ? 'ফোন' : 'Phone', e($phone)) : '') . '
                ' . $row($locale === 'bn' ? 'বিষয়' : 'Subject', e($subject)) . '
            </table>
            <h3 style="font-size:14px; color:#333; margin:0 0 8px;">' . ($locale === 'bn' ? 'বার্তা' : 'Message') . '</h3>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:12px 16px; font-size:14px; color:#333; white-space:pre-wrap;">' . nl2br(e($message)) . '</div>
            <hr style="border:none; border-top:1px solid #e2e8f0; margin:20px 0;">
            <p style="font-size:12px; color:#94a3b8; margin:0;">
                ' . ($locale === 'bn' ? 'এই ইমেইলটি ওয়েবসাইটের যোগাযোগ ফর্ম থেকে পাঠানো হয়েছে।' : 'This email was sent from the website contact form.') . '
            </p>
        </div>
    </div>';

    return mf_send_mail(
        $to,
        $mailSubject,
        $body,
        '',
        filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
        // Contact-form mail is correspondence, not bulk: no List-Unsubscribe.
    );
}

function whatsapp_order_url(): string
{
    $phone = trim((string) site_setting('footer_phone', ''));
    if ($phone === '') {
        return '';
    }
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if ($phone === '') {
        return '';
    }
    return 'https://wa.me/' . ltrim($phone, '+');
}

function homepage_sections(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $enFile = base_path('app/Lang/wsit_en.php');
    $bnFile = base_path('app/Lang/wsit_bn.php');
    $enStrings = file_exists($enFile) ? (array) require $enFile : [];
    $bnStrings = file_exists($bnFile) ? (array) require $bnFile : [];

    $allKeys = [
        'section_land_trust' => [
            'label_key' => 'Home Trust Badges',
            'icon' => '🛡️',
            'fields' => ['land_trust_1', 'land_trust_2', 'land_trust_3', 'land_trust_4'],
        ],
        'section_land_value' => [
            'label_key' => 'Home Value Cards',
            'icon' => '🚚',
            'fields' => [
                'land_value_1_icon', 'land_value_1', 'land_value_1_text',
                'land_value_2_icon', 'land_value_2', 'land_value_2_text',
                'land_value_3_icon', 'land_value_3', 'land_value_3_text',
                'land_value_4_icon', 'land_value_4', 'land_value_4_text',
            ],
        ],
        'section_land_story' => [
            'label_key' => 'Home Story',
            'icon' => '📖',
            'fields' => [
                'land_story_badge', 'land_story_title', 'land_story_par',
                'land_story_image_label',
                'land_story_1',
                'land_story_2',
                'land_story_3',
                'land_story_4',
            ],
        ],
        'section_land_why' => [
            'label_key' => 'Home Why Us',
            'icon' => '🏆',
            'fields' => [
                'land_why_title',
                'land_why_1_icon', 'land_why_1', 'land_why_1_text',
                'land_why_2_icon', 'land_why_2', 'land_why_2_text',
                'land_why_3_icon', 'land_why_3', 'land_why_3_text',
                'land_why_4_icon', 'land_why_4', 'land_why_4_text',
            ],
        ],
        'section_land_how' => [
            'label_key' => 'Home How To Order',
            'icon' => '📦',
            'fields' => [
                'land_how_title',
                'land_how_1_num', 'land_how_1', 'land_how_1_text',
                'land_how_2_num', 'land_how_2', 'land_how_2_text',
                'land_how_3_num', 'land_how_3', 'land_how_3_text',
            ],
        ],
        'section_hero_content' => [
            'label_key' => 'Hero Content',
            'icon' => '🌟',
            'fields' => ['land_hero_highlight', 'land_hero_1', 'land_hero_2', 'land_hero_3', 'land_stamp_label', 'land_stamp_text'],
        ],
        'section_land_products' => [
            'label_key' => 'Products Heading',
            'icon' => '🛒',
            'fields' => ['land_products', 'land_products_text', 'land_tab_all'],
        ],
        'section_land_testimonials' => [
            'label_key' => 'Home Testimonials',
            'icon' => '⭐',
            'fields' => [
                'land_testimonials',
                'land_testimonial_1_stars', 'land_testimonial_1', 'land_testimonial_1_by', 'land_testimonial_1_loc',
                'land_testimonial_2_stars', 'land_testimonial_2', 'land_testimonial_2_by', 'land_testimonial_2_loc',
                'land_testimonial_3_stars', 'land_testimonial_3', 'land_testimonial_3_by', 'land_testimonial_3_loc',
            ],
        ],
        'section_land_faq' => [
            'label_key' => 'Home FAQ',
            'icon' => '❓',
            'fields' => [
                'land_faq_title',
                'land_faq_1_q', 'land_faq_1_a',
                'land_faq_2_q', 'land_faq_2_a',
                'land_faq_3_q', 'land_faq_3_a',
                'land_faq_4_q', 'land_faq_4_a',
            ],
        ],
        'section_land_cta' => [
            'label_key' => 'Home CTA Banner',
            'icon' => '🔔',
            'fields' => ['land_cta_title', 'land_cta_text', 'land_cta_btn', 'land_cta_wa', 'land_cta_call'],
        ],
        'section_footer_newsletter' => [
            'label_key' => 'Home Newsletter',
            'icon' => '📰',
            'fields' => ['footer_newsletter', 'footer_newsletter_text', 'newsletter_placeholder', 'newsletter_btn'],
        ],
    ];

    // Human-readable labels for the homepage content fields (used by the admin
    // editor; falls back to the raw key when a field is missing from this map).
    $fieldLabels = [
        // Home Trust Badges
        'land_trust_1'            => 'Trust Badge 1',
        'land_trust_2'            => 'Trust Badge 2',
        'land_trust_3'            => 'Trust Badge 3',
        'land_trust_4'            => 'Trust Badge 4',
        // Home Value Cards
        'land_value_1_icon'       => 'Value Card 1 – Icon',
        'land_value_1'            => 'Value Card 1 – Title',
        'land_value_1_text'       => 'Value Card 1 – Text',
        'land_value_2_icon'       => 'Value Card 2 – Icon',
        'land_value_2'            => 'Value Card 2 – Title',
        'land_value_2_text'       => 'Value Card 2 – Text',
        'land_value_3_icon'       => 'Value Card 3 – Icon',
        'land_value_3'            => 'Value Card 3 – Title',
        'land_value_3_text'       => 'Value Card 3 – Text',
        'land_value_4_icon'       => 'Value Card 4 – Icon',
        'land_value_4'            => 'Value Card 4 – Title',
        'land_value_4_text'       => 'Value Card 4 – Text',
        // Home Story
        'land_story_badge'        => 'Story Badge',
        'land_story_title'        => 'Story Title',
        'land_story_par'          => 'Story Paragraph',
        'land_story_image_label'  => 'Story Image Caption',
        'land_story_1'            => 'Story Point 1',
        'land_story_2'            => 'Story Point 2',
        'land_story_3'            => 'Story Point 3',
        'land_story_4'            => 'Story Point 4',
        // Home Why Us
        'land_why_title'          => 'Why Us Title',
        'land_why_1_icon'         => 'Why Us 1 – Icon',
        'land_why_1'              => 'Why Us 1 – Title',
        'land_why_1_text'         => 'Why Us 1 – Text',
        'land_why_2_icon'         => 'Why Us 2 – Icon',
        'land_why_2'              => 'Why Us 2 – Title',
        'land_why_2_text'         => 'Why Us 2 – Text',
        'land_why_3_icon'         => 'Why Us 3 – Icon',
        'land_why_3'              => 'Why Us 3 – Title',
        'land_why_3_text'         => 'Why Us 3 – Text',
        'land_why_4_icon'         => 'Why Us 4 – Icon',
        'land_why_4'              => 'Why Us 4 – Title',
        'land_why_4_text'         => 'Why Us 4 – Text',
        // Home How To Order
        'land_how_title'          => 'How To Order Title',
        'land_how_1_num'          => 'Step 1 – Number',
        'land_how_1'              => 'Step 1 – Title',
        'land_how_1_text'         => 'Step 1 – Text',
        'land_how_2_num'          => 'Step 2 – Number',
        'land_how_2'              => 'Step 2 – Title',
        'land_how_2_text'         => 'Step 2 – Text',
        'land_how_3_num'          => 'Step 3 – Number',
        'land_how_3'              => 'Step 3 – Title',
        'land_how_3_text'         => 'Step 3 – Text',
        // Hero Content
        'land_hero_highlight'     => 'Hero Highlight',
        'land_hero_1'             => 'Hero Badge 1',
        'land_hero_2'             => 'Hero Badge 2',
        'land_hero_3'             => 'Hero Badge 3',
        'land_stamp_label'        => 'Stamp Label',
        'land_stamp_text'         => 'Stamp Text',
        // Products Heading
        'land_products'           => 'Products Heading',
        'land_products_text'      => 'Products Subtitle',
        'land_tab_all'            => 'All Products Tab Label',
        // Home Testimonials
        'land_testimonials'       => 'Testimonials Heading',
        'land_testimonial_1_stars' => 'Testimonial 1 – Stars',
        'land_testimonial_1'      => 'Testimonial 1 – Text',
        'land_testimonial_1_by'   => 'Testimonial 1 – Author',
        'land_testimonial_1_loc'  => 'Testimonial 1 – Location',
        'land_testimonial_2_stars' => 'Testimonial 2 – Stars',
        'land_testimonial_2'      => 'Testimonial 2 – Text',
        'land_testimonial_2_by'   => 'Testimonial 2 – Author',
        'land_testimonial_2_loc'  => 'Testimonial 2 – Location',
        'land_testimonial_3_stars' => 'Testimonial 3 – Stars',
        'land_testimonial_3'      => 'Testimonial 3 – Text',
        'land_testimonial_3_by'   => 'Testimonial 3 – Author',
        'land_testimonial_3_loc'  => 'Testimonial 3 – Location',
        // Home FAQ
        'land_faq_title'          => 'FAQ Heading',
        'land_faq_1_q'            => 'FAQ 1 – Question',
        'land_faq_1_a'            => 'FAQ 1 – Answer',
        'land_faq_2_q'            => 'FAQ 2 – Question',
        'land_faq_2_a'            => 'FAQ 2 – Answer',
        'land_faq_3_q'            => 'FAQ 3 – Question',
        'land_faq_3_a'            => 'FAQ 3 – Answer',
        'land_faq_4_q'            => 'FAQ 4 – Question',
        'land_faq_4_a'            => 'FAQ 4 – Answer',
        // Home CTA Banner
        'land_cta_title'          => 'CTA Title',
        'land_cta_text'           => 'CTA Text',
        'land_cta_btn'            => 'CTA Button Label',
        'land_cta_wa'             => 'CTA WhatsApp Number',
        'land_cta_call'           => 'CTA Call Number',
        // Home Newsletter
        'footer_newsletter'       => 'Newsletter Heading',
        'footer_newsletter_text'  => 'Newsletter Text',
        'newsletter_placeholder'  => 'Newsletter Email Placeholder',
        'newsletter_btn'          => 'Newsletter Subscribe Button',
    ];

    $defaults = [];
    foreach ($allKeys as $sectionId => $section) {
        foreach ($section['fields'] as $key) {
            $en = $enStrings[$key] ?? $key;
            $bn = $bnStrings[$key] ?? $en;
            $defaults[$key] = ['en' => (string) $en, 'bn' => (string) $bn];
        }
    }

    $db = wsit_App::get('db');
    if ($db !== null) {
        try {
            $setting = new \App\Models\wsit_Setting();
            $raw = $setting->get('home_content', '');
            if ($raw !== '') {
                $overrides = json_decode($raw, true);
                if (is_array($overrides)) {
                    foreach ($overrides as $key => $value) {
                        if (isset($defaults[$key]) && is_array($value)) {
                            if (isset($value['en'])) {
                                $defaults[$key]['en'] = (string) $value['en'];
                            }
                            if (isset($value['bn'])) {
                                $defaults[$key]['bn'] = (string) $value['bn'];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — use defaults
        }
    }

    $cache = ['defaults' => $defaults, 'sections' => $allKeys, 'field_labels' => $fieldLabels];
    return $cache;
}

function homepage_section(string $key, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $sections = homepage_sections();
    return (string) ($sections['defaults'][$key][$locale] ?? ($sections['defaults'][$key]['en'] ?? $key));
}

// ---------------------------------------------------------------------------
// SEO helpers
// ---------------------------------------------------------------------------

/**
 * Return the canonical base URL (scheme + host + base subpath) as an absolute URL.
 */
function canonical_base_url(): string
{
    $configUrl = rtrim((string) config('app.base_url', ''), '/');
    if ($configUrl !== '') {
        $parsed = parse_url($configUrl);
        $configHost = $parsed['host'] ?? '';
        $serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $serverHost = explode(':', $serverHost)[0];
        if ($configHost !== '' && ($configHost === $serverHost || $serverHost === 'localhost' || $serverHost === '')) {
            return $configUrl;
        }
        if ($serverHost !== '' && $configHost !== $serverHost) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            return rtrim($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? $serverHost) . base_url_path(), '/');
        }
        return $configUrl;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    return rtrim($scheme . '://' . $host . base_url_path(), '/');
}

/**
 * Convert any path or relative URL into a fully-qualified absolute URL.
 */
function absolute_url(string $path = ''): string
{
    $path = trim($path);
    if ($path === '' || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return $path;
    }
    $base = canonical_base_url();
    $basePath = rtrim(base_url_path(), '/');
    if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
        $path = substr($path, strlen($basePath));
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Return the site-wide default SEO description.
 * Checks Dashboard → SEO override, then falls back to tagline.
 */
function seo_default_description(): string
{
    $adminDesc = trim((string) site_setting('seo_default_description', ''));
    if ($adminDesc !== '') {
        return $adminDesc;
    }
    return (string) site_setting('tagline_' . app_locale(), trans('tagline'));
}

/**
 * Build the canonical absolute URL for the current request.
 */
function seo_canonical_url(): string
{
    $path = current_page_path();
    $locale = app_locale();
    $prefix = $locale ? '/' . $locale : '';
    $rel = $prefix . ($path ? '/' . $path : '');
    return absolute_url($rel ?: '/');
}

/**
 * Get the default OG image as an absolute URL (Dashboard → SEO, or site logo).
 */
function seo_default_og_image(): string
{
    $img = (string) site_setting('seo_og_image', '');
    if ($img !== '') {
        return absolute_url($img);
    }
    $logo = site_logo_url();
    if ($logo !== '') {
        return absolute_url($logo);
    }
    return '';
}

/**
 * Build hreflang alternate links for all locales, including x-default.
 *
 * @return list<array{lang:string,url:string}>
 */
function seo_hreflang_links(string $canonicalUrl = ''): array
{
    $links = [];
    $path = current_page_path();

    foreach (locales() as $loc) {
        $prefix = $loc ? '/' . $loc : '';
        $rel = $prefix . ($path ? '/' . $path : '');
        $links[] = [
            'lang' => $loc,
            'url'  => absolute_url($rel ?: '/'),
        ];
    }

    $defaultLoc = (string) config('app.default_locale', 'en');
    $defaultRel = ($defaultLoc ? '/' . $defaultLoc : '') . ($path ? '/' . $path : '');
    $links[] = [
        'lang' => 'x-default',
        'url'  => absolute_url($defaultRel ?: '/'),
    ];

    return $links;
}

/**
 * Generate BreadcrumbList JSON-LD structured data.
 *
 * @param list<array{name:string,url:string}> $crumbs
 * @return array<string,mixed>
 */
function seo_breadcrumb_jsonld(array $crumbs): array
{
    $listItems = [];
    $position = 1;

    foreach ($crumbs as $crumb) {
        $name = trim((string)($crumb['name'] ?? ''));
        $url  = trim((string)($crumb['url'] ?? ''));
        if ($name === '') {
            continue;
        }
        $item = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => $name,
        ];
        if ($url !== '') {
            $item['item'] = absolute_url($url);
        }
        $listItems[] = $item;
    }

    if (empty($listItems)) {
        return [];
    }

    return [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $listItems,
    ];
}

/**
 * Generate default JSON-LD structured data (Organization + WebSite with SearchAction).
 *
 * @return array<string,mixed>
 */
function seo_default_jsonld(string $title, string $description, string $url, string $image = ''): array
{
    $schemas = [];
    $base = canonical_base_url();

    // Determine official organization logo (never a product photo)
    $orgLogo = seo_default_og_image();
    if ($orgLogo === '') {
        $logo = site_logo_url();
        if ($logo !== '') {
            $orgLogo = absolute_url($logo);
        }
    }

    $orgName = site_setting('seo_org_name', app_name());
    $phone   = site_setting('footer_phone', '');
    $email   = site_setting('footer_email', '');
    $address = site_setting('footer_address_' . app_locale(), site_setting('footer_address_en', ''));

    $org = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => $orgName,
        'url'      => rtrim($base, '/') . '/',
    ];
    if ($orgLogo !== '') {
        $org['logo'] = $orgLogo;
    }
    if ($email !== '') {
        $org['email'] = $email;
    }
    if ($phone !== '') {
        $org['telephone'] = $phone;
    }
    if ($address !== '') {
        $org['address'] = [
            '@type'          => 'PostalAddress',
            'streetAddress'  => $address,
            'addressCountry' => 'BD',
        ];
    }
    $schemas['organization'] = $org;

    // WebSite schema with Sitelinks Searchbox
    $schemas['website'] = [
        '@context'        => 'https://schema.org',
        '@type'           => 'WebSite',
        'name'            => $orgName,
        'url'             => rtrim($base, '/') . '/',
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => absolute_url('/' . app_locale() . '/products?q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ],
    ];

    return $schemas;
}

/**
 * Generate Product JSON-LD structured data.
 *
 * @return array<string,mixed>
 */
function seo_product_jsonld(array $product, string $description = '', string $image = ''): array
{
    $productName = $product['name'] ?? ($product['name_en'] ?? app_name());
    $productUrl  = seo_canonical_url();
    $absImage    = $image !== '' ? absolute_url($image) : '';

    $schemas = seo_default_jsonld($productName, $description, $productUrl, $absImage);

    // Calculate actual active price
    $hasSale = isset($product['sale_price']) && (float)$product['sale_price'] > 0;
    $finalPrice = $hasSale ? (float)$product['sale_price'] : (float)($product['price'] ?? 0);

    $schemas['product'] = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $productName,
        'description' => $description,
        'image'       => $absImage ?: $productUrl,
        'url'         => $productUrl,
        'sku'         => !empty($product['sku']) ? (string)$product['sku'] : ('PROD-' . ($product['id'] ?? '0')),
        'brand'       => [
            '@type' => 'Brand',
            'name'  => app_name(),
        ],
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => number_format($finalPrice, 2, '.', ''),
            'priceCurrency' => 'BDT',
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability'  => ((int)($product['stock'] ?? 0) > 0)
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url'           => $productUrl,
        ],
    ];

    return $schemas;
}

/**
 * Return the story section image path (stored as a standalone setting so it
 * can be uploaded independently of the hero slides).
 */
function story_image_path(): string
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $setting = new \App\Models\wsit_Setting();
        $cache = trim($setting->get('story_image', ''));
    } catch (\Throwable $e) {
        $cache = '';
    }
    return $cache;
}

// ---------------------------------------------------------------------------
// Facebook Pixel
// ---------------------------------------------------------------------------

/**
 * Return the merged Facebook Pixel configuration with defaults.
 * Shape: ['pixel_id' => '', 'enabled' => bool, 'test_mode' => bool,
 *          'events' => ['view_content'=>bool, 'add_to_cart'=>bool,
 *                       'initiate_checkout'=>bool, 'purchase'=>bool]]
 */
function facebook_pixel_config(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $default = [
        'pixel_id' => '',
        'enabled'  => false,
        'test_mode' => false,
        'events'   => [
            'view_content'      => true,
            'add_to_cart'       => true,
            'initiate_checkout' => true,
            'purchase'          => true,
        ],
    ];

    try {
        $setting = new \App\Models\wsit_Setting();
        $raw = $setting->get('facebook_pixel', '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($default as $key => $val) {
                    if (!array_key_exists($key, $decoded)) {
                        continue;
                    }
                    if (is_array($val)) {
                        if (is_array($decoded[$key])) {
                            $default[$key] = array_merge($val, $decoded[$key]);
                        }
                    } elseif (is_bool($val)) {
                        $default[$key] = (bool)$decoded[$key];
                    } else {
                        $default[$key] = trim((string)$decoded[$key]);
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        // DB unavailable — fall back to defaults.
    }

    // Pixel is only "enabled" when a pixel ID is present and the master
    // switch is on; an empty ID always disables the snippet.
    $cache = $default;
    $cache['enabled'] = $default['enabled'] && trim((string)$default['pixel_id']) !== '';
    return $cache;
}

/** Whether the Facebook Pixel snippet should render at all. */
function facebook_pixel_enabled(): bool
{
    $cfg = facebook_pixel_config();
    return !empty($cfg['enabled']) && trim((string)$cfg['pixel_id']) !== '';
}

/** The raw pixel ID (empty when not configured). */
function facebook_pixel_id(): string
{
    return trim((string)(facebook_pixel_config()['pixel_id'] ?? ''));
}

/** True when the given event name is toggled on in the admin. */
function facebook_pixel_event_enabled(string $event): bool
{
    $events = facebook_pixel_config()['events'] ?? [];
    return !empty($events[$event]);
}

/** True when test mode is on (events logged, not sent to Facebook). */
function facebook_pixel_test_mode(): bool
{
    return !empty(facebook_pixel_config()['test_mode']);
}

/**
 * Build the standard <script> base snippet for the <head>.
 * Returns an empty string when the pixel is disabled.
 */
function facebook_pixel_base_script(): string
{
    if (!facebook_pixel_enabled()) {
        return '';
    }

    $pixelId = facebook_pixel_id();
    $testMode = facebook_pixel_test_mode() ? 'true' : 'false';

    return <<<HTML
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);
t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$pixelId}', {test_mode: {$testMode}});
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1"
 /></noscript>
HTML;
}

/**
 * Render a standard event call for the given event name + parameters.
 * Returns '' when the pixel is disabled or the event is toggled off.
 */
function facebook_pixel_track(string $event, array $params = []): string
{
    if (!facebook_pixel_enabled() || !facebook_pixel_event_enabled($event)) {
        return '';
    }

    $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return "fbq('track', '" . e($event) . "', " . $json . ');';
}

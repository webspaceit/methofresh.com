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
    return require dirname(__DIR__) . '/app/config/wsit_config.php';
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
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
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
        'secure' => false,
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
            'icon'        => '💵',
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
            'icon'        => '📱',
            'title_en'    => 'bKash',
            'title_bn'    => 'বিকাশ',
            'desc_en'     => 'Send money to our bKash number and enter the transaction ID.',
            'desc_bn'     => 'আমাদের বিকাশ নম্বরে টাকা পাঠিয়ে ট্রানজেকশন আইডি প্রদান করুন।',
            'account_no'  => '01711-111111',
            'account_type'=> 'personal',
            'require_trx' => true,
        ],
        'nagad' => [
            'key'         => 'nagad',
            'enabled'     => false,
            'icon'        => '💳',
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
            'icon'        => '🚀',
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
            'icon'        => '🏦',
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
            $method['title'] = $locale === 'bn' ? ($method['title_bn'] ?: $method['title_en']) : $method['title_en'];
            $method['desc']  = $locale === 'bn' ? ($method['desc_bn'] ?: $method['desc_en']) : $method['desc_en'];
            $active[$key] = $method;
        }
    }
    return $active;
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

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . $siteName . " <support@methofresh.com>\r\n";
    $headers .= "Reply-To: support@methofresh.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($email, $subject, $body, $headers);
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

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "From: " . $siteName . " <support@methofresh.com>\r\n";
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $headers .= "Reply-To: " . ($name !== '' ? $name . ' ' : '') . "<" . $email . ">\r\n";
    }
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $mailSubject, $body, $headers);
}
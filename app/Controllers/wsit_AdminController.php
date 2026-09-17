<?php

namespace App\Controllers;

use App\Core\wsit_App;
use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Order;
use App\Models\wsit_Product;
use App\Models\wsit_Setting;
use App\Models\wsit_User;

class wsit_AdminController extends wsit_Controller
{
    private wsit_Order $orderModel;
    private wsit_Product $productModel;
    private wsit_User $userModel;
    private wsit_Setting $settingModel;

    protected function before(): void
    {
        $this->requireAdmin();
    }

    public function __construct()
    {
        parent::__construct();
        $this->orderModel   = new wsit_Order();
        $this->productModel = new wsit_Product();
        $this->userModel    = new wsit_User();
        $this->settingModel = new wsit_Setting();
    }

    public function dashboard(): string
    {
        return $this->view('admin/wsit_dashboard', [
            'pageTitle'    => trans('admin_dashboard') . ' | ' . trans('admin'),
            'revenue'      => $this->orderModel->revenue(),
            'ordersCount'  => $this->orderModel->countAll(),
            'productsCount'=> $this->productModel->countAll(),
            'customers'    => $this->userModel->count(),
            'recentOrders' => $this->orderModel->recent(6),
        ], 'wsit_admin');
    }

    public function orders(wsit_Request $request): string
    {
        $status = (string)$request->query('status', 'all');
        $search = trim((string)$request->query('q', ''));
        $perPage = 20;
        $page = max(1, (int)$request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        $orders = $this->orderModel->all($status, $search, $perPage, $offset);
        $total = $this->orderModel->countAll($status);
        $pages = max(1, (int)ceil($total / $perPage));

        return $this->view('admin/orders/wsit_index', [
            'pageTitle' => trans('admin_orders') . ' | ' . trans('admin'),
            'orders'    => $orders,
            'status'    => $status,
            'search'    => $search,
            'page'      => $page,
            'pages'     => $pages,
            'total'     => $total,
        ], 'wsit_admin');
    }

    public function orderShow(int $id): string
    {
        $order = $this->orderModel->findWithItems($id);

        if ($order === null) {
            $this->flash('error', trans('order_not_found'));
            $this->redirect('/mf-dashboard/orders');
        }

        return $this->view('admin/orders/wsit_show', [
            'pageTitle' => trans('admin_orders') . ' #' . $order['order_number'],
            'order'     => $order,
        ], 'wsit_admin');
    }

    public function ordersJson(wsit_Request $request): void
    {
        $status = (string)$request->query('status', 'all');
        $search = trim((string)$request->query('q', ''));
        $orders = $this->orderModel->all($status, $search, 100, 0);
        $count = count($orders);
        $html = $this->render('admin/orders/wsit__rows', ['orders' => $orders]);

        $this->json([
            'count'       => $count,
            'count_label' => format_number($count) . ' ' . trans('admin_orders_count'),
            'html'        => $html,
        ]);
    }

    public function updateStatus(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/orders/' . $id);
        }

        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $status = $request->input('status');

        if (in_array($status, $allowed, true)) {
            $order = $this->orderModel->findWithItems($id);
            $oldStatus = $order ? ($order['status'] ?? '') : '';

            $this->orderModel->updateStatus($id, $status);

            if ($oldStatus === 'pending' && $oldStatus !== $status && $order !== null) {
                send_order_status_email($order, $oldStatus, $status);
            }

            $this->flash('success', trans('admin_updated'));
        }

        $this->redirect('/mf-dashboard/orders/' . $id);
    }

    public function settings(): string
    {
        $globalLocale = $this->settingModel->get('default_locale', default_locale());

        return $this->view('admin/wsit_settings', [
            'pageTitle'     => trans('admin_settings') . ' | ' . trans('admin'),
            'globalLocale'  => $globalLocale,
            'contactEmail'  => $this->settingModel->get('contact_email', 'support@methofresh.com'),
        ], 'wsit_admin');
    }

    public function updateSettings(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $locale = $request->input('locale', 'en');

        if (is_locale($locale)) {
            // Save as the site-wide global default — applies to everyone
            // immediately, logged in or not, on every browser.
            $this->settingModel->set('default_locale', $locale);
            restore_locale($locale);
            $this->flash('success', trans('admin_settings_saved'));
        }

        $this->redirect('/mf-dashboard/settings');
    }

    public function updateLogo(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        // Remove the current logo (same endpoint, form button).
        if ($request->input('remove_logo') === '1') {
            $this->removeStoredLogo();
            $this->flash('success', trans('admin_logo_removed'));
            $this->redirect('/mf-dashboard/settings');
        }

        $file = $request->file('logo');
        $hasFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        if (!$hasFile) {
            $this->flash('error', trans('admin_logo_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $allowed = [
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/gif'     => 'gif',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
        ];
        $mime = (string)($file['type'] ?? '');
        $ext = $allowed[$mime] ?? null;

        if ($ext === null || (int)($file['size'] ?? 0) > 2 * 1024 * 1024) {
            $this->flash('error', trans('admin_logo_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $dir = public_path('assets/img/uploads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->flash('error', trans('admin_logo_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $name = 'logo-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
            $this->flash('error', trans('admin_logo_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $this->removeStoredLogo();
        $this->settingModel->set('site_logo', 'assets/img/uploads/' . $name);
        $this->flash('success', trans('admin_logo_saved'));
        $this->redirect('/mf-dashboard/settings');
    }

    private function removeStoredLogo(): void
    {
        $current = $this->settingModel->get('site_logo', '');
        if ($current !== '' && str_starts_with($current, 'assets/img/uploads/')) {
            $file = public_path($current);
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->settingModel->set('site_logo', '');
    }

    public function updateFavicon(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        // Remove the current favicon (same endpoint, form button).
        if ($request->input('remove_favicon') === '1') {
            $this->removeStoredFavicon();
            $this->flash('success', trans('admin_favicon_removed'));
            $this->redirect('/mf-dashboard/settings');
        }

        $file = $request->file('favicon');
        $hasFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        if (!$hasFile) {
            $this->flash('error', trans('admin_favicon_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $allowed = [
            'image/jpeg'               => 'jpg',
            'image/png'                => 'png',
            'image/gif'                => 'gif',
            'image/webp'               => 'webp',
            'image/svg+xml'            => 'svg',
            'image/x-icon'             => 'ico',
            'image/vnd.microsoft.icon' => 'ico',
        ];
        $mime = (string)($file['type'] ?? '');
        $ext = $allowed[$mime] ?? null;

        if ($ext === null || (int)($file['size'] ?? 0) > 1024 * 1024) {
            $this->flash('error', trans('admin_favicon_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $dir = public_path('assets/img/uploads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->flash('error', trans('admin_favicon_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $name = 'favicon-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
            $this->flash('error', trans('admin_favicon_upload_error'));
            $this->redirect('/mf-dashboard/settings');
        }

        $this->removeStoredFavicon();
        $this->settingModel->set('site_favicon', 'assets/img/uploads/' . $name);
        $this->flash('success', trans('admin_favicon_saved'));
        $this->redirect('/mf-dashboard/settings');
    }

    private function removeStoredFavicon(): void
    {
        $current = $this->settingModel->get('site_favicon', '');
        if ($current !== '' && str_starts_with($current, 'assets/img/uploads/')) {
            $file = public_path($current);
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->settingModel->set('site_favicon', '');
    }

    public function updateContactCaptcha(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $enabled = $request->input('contact_captcha') === '1' ? '1' : '0';
        $this->settingModel->set('contact_captcha', $enabled);
        $this->flash('success', trans('admin_contact_captcha_saved'));
        $this->redirect('/mf-dashboard/settings');
    }

    public function updateContactEmail(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $email = trim((string)$request->input('contact_email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', trans('admin_contact_email_invalid'));
            $this->redirect('/mf-dashboard/settings');
        }

        $this->settingModel->set('contact_email', $email);
        $this->flash('success', trans('admin_contact_email_saved'));
        $this->redirect('/mf-dashboard/settings');
    }

    public function homepage(): string
    {
        return $this->view('admin/wsit_homepage', [
            'pageTitle' => trans('admin_homepage') . ' | ' . trans('admin'),
            'blocks'    => homepage_blocks(),
            'heroSlides'=> hero_slides(),
        ], 'wsit_admin');
    }

    public function updateHomepage(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/homepage');
        }

        $this->saveHeroSlides($request);

        $this->settingModel->set('hero_speed', (string)hero_carousel_speed_clamped((string)$request->input('hero_speed', '5500')));
        $this->settingModel->set('hero_height', (string)hero_carousel_height_clamped((string)$request->input('hero_height', '540')));

        $this->settingModel->set('tagline_en', trim((string)$request->input('tagline_en', '')));
        $this->settingModel->set('tagline_bn', trim((string)$request->input('tagline_bn', '')));

        foreach (array_keys(homepage_blocks()) as $key) {
            $this->settingModel->set('home_block_' . $key, json_encode([
                'visible'  => $request->input($key . '_visible') === '1' ? 1 : 0,
                'order'    => max(1, (int)$request->input($key . '_order', 1)),
                'title_en' => trim((string)$request->input($key . '_title_en', '')),
                'title_bn' => trim((string)$request->input($key . '_title_bn', '')),
            ], JSON_UNESCAPED_UNICODE));
        }

        $this->flash('success', trans('admin_saved'));
        $this->redirect('/mf-dashboard/homepage');
    }

    /**
     * Persist the hero slides array (title/subtitle/button text per language,
     * emoji, order, visibility) including optional per-slide image uploads,
     * image removal and slide deletion.
     */
    private function saveHeroSlides(wsit_Request $request): void
    {
        $rawSlides = $request->inputArray('hero_slides');
        $slides = [];

        foreach ($rawSlides as $key => $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $currentImage = trim((string)($raw['image'] ?? ''));

            if (!empty($raw['delete'])) {
                $this->removeHeroImage($currentImage);
                continue;
            }

            $image = $currentImage;
            $upload = $this->heroSlideUpload((string)$key);

            if ($upload !== null) {
                // New upload replaces any existing image.
                $this->removeHeroImage($image);
                $image = $upload;
            } elseif (!empty($raw['remove_image'])) {
                $this->removeHeroImage($image);
                $image = '';
            }

            $slides[] = [
                'id'            => (int)($raw['id'] ?? 0),
                'enabled'       => !empty($raw['enabled']) ? 1 : 0,
                'order'         => max(1, (int)($raw['order'] ?? 1)),
                'emoji'         => trim((string)($raw['emoji'] ?? '')),
                'title_en'      => trim((string)($raw['title_en'] ?? '')),
                'title_bn'      => trim((string)($raw['title_bn'] ?? '')),
                'subtitle_en'   => trim((string)($raw['subtitle_en'] ?? '')),
                'subtitle_bn'   => trim((string)($raw['subtitle_bn'] ?? '')),
                'btn1_text_en'  => trim((string)($raw['btn1_text_en'] ?? '')),
                'btn1_text_bn'  => trim((string)($raw['btn1_text_bn'] ?? '')),
                'btn1_link'     => trim((string)($raw['btn1_link'] ?? '/products')),
                'btn2_text_en'  => trim((string)($raw['btn2_text_en'] ?? '')),
                'btn2_text_bn'  => trim((string)($raw['btn2_text_bn'] ?? '')),
                'btn2_link'     => trim((string)($raw['btn2_link'] ?? '/categories')),
                'image'         => $image,
            ];
        }

        $this->settingModel->set('hero_slides', json_encode($slides, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Pull the uploaded file for hero_slides[<index>][image_file] out of PHP's
     * nested $_FILES structure. Returns the move-ready descriptor or null.
     */
    private function heroSlideUpload(string $index): ?string
    {
        $files = $_FILES['hero_slides'] ?? null;
        if (!is_array($files)) {
            return null;
        }

        $item = [];
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
            $item[$field] = $files[$field][$index]['image_file'] ?? null;
        }

        if (($item['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $mime = (string)($item['type'] ?? '');
        $ext = $allowed[$mime] ?? null;

        if ($ext === null || (int)($item['size'] ?? 0) > 4 * 1024 * 1024) {
            return null;
        }

        $dir = public_path('assets/img/uploads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return null;
        }

        $name = 'hero-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file((string)$item['tmp_name'], $dest)) {
            return null;
        }

        return 'assets/img/uploads/' . $name;
    }

    /**
     * Delete an uploaded hero image. Only removes files we uploaded ourselves
     * (assets/img/uploads/hero-*) and ignores everything else.
     */
    private function removeHeroImage(string $path): void
    {
        $path = trim($path);
        if ($path === '' || !str_starts_with($path, 'assets/img/uploads/hero-')) {
            return;
        }
        $file = public_path($path);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function footer(): string
    {
        return $this->view('admin/wsit_footer', [
            'pageTitle' => trans('admin_footer') . ' | ' . trans('admin'),
        ], 'wsit_admin');
    }

    public function updateFooter(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/footer');
        }

        $keys = [
            'footer_about_en', 'footer_about_bn',
            'footer_email', 'footer_phone',
            'footer_address_en', 'footer_address_bn',
            'copyright_en', 'copyright_bn',
        ];
        foreach ($keys as $key) {
            $this->settingModel->set($key, trim((string)$request->input($key, '')));
        }

        $this->saveFooterQuickLinks($request);

        $this->flash('success', trans('admin_saved'));
        $this->redirect('/mf-dashboard/footer');
    }

    private function saveFooterQuickLinks(wsit_Request $request): void
    {
        $raw     = $request->inputArray('quick_links', []);
        $removes = $request->inputArray('quick_links_remove', []);
        $links   = [];

        foreach ($raw as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!empty($removes[$key])) {
                continue;
            }
            $labelEn = trim((string)($item['label_en'] ?? ''));
            $labelBn = trim((string)($item['label_bn'] ?? ''));
            $url     = trim((string)($item['url'] ?? ''));
            if ($labelEn === '' && $labelBn === '' && $url === '') {
                continue;
            }
            $links[] = [
                'label_en' => $labelEn,
                'label_bn' => $labelBn,
                'url'      => $url,
                'enabled'  => empty($item['enabled']) ? 0 : 1,
            ];
        }

        $this->settingModel->set('footer_quick_links', json_encode($links, JSON_UNESCAPED_UNICODE));
    }

    public function translate(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->json(['ok' => false, 'error' => trans('validate_token')]);
        }

        $text   = trim((string)$request->input('text', ''));
        $target = trim((string)$request->input('target', 'bn'));

        if ($text === '' || mb_strlen($text) > 4000) {
            $this->json(['ok' => false, 'error' => trans('admin_translate_empty')]);
        }

        if (!is_locale($target)) {
            $target = 'bn';
        }

        $translated = trim($this->translateText($text, 'en', $target));

        if ($translated === '') {
            $this->json(['ok' => false, 'error' => trans('admin_translate_failed')]);
        }

        $this->json(['ok' => true, 'translated' => $translated]);
    }

    private function translateText(string $text, string $source, string $target): string
    {
        $result = $this->googleTranslate($text, $source, $target);
        if ($result !== '') {
            return $result;
        }
        return $this->mymemoryTranslate($text, $source, $target);
    }

    private function googleTranslate(string $text, string $source, string $target): string
    {
        if (!function_exists('curl_init')) {
            return '';
        }

        $url = 'https://translate.googleapis.com/translate_a/single'
            . '?client=gtx&sl=' . urlencode($source)
            . '&tl=' . urlencode($target)
            . '&dt=t&q=' . urlencode($text);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!is_string($response) || $response === '') {
            return '';
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data[0]) || !is_array($data[0])) {
            return '';
        }

        $out = '';
        foreach ($data[0] as $segment) {
            if (isset($segment[0]) && is_string($segment[0])) {
                $out .= $segment[0];
            }
        }
        return trim($out);
    }

    private function mymemoryTranslate(string $text, string $source, string $target): string
    {
        if (!function_exists('curl_init')) {
            return '';
        }

        $url = 'https://api.mymemory.translated.net/get'
            . '?q=' . urlencode($text)
            . '&langpair=' . urlencode($source . '|' . $target);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!is_string($response) || $response === '') {
            return '';
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['responseData']['translatedText'])) {
            return '';
        }

        return trim((string) $data['responseData']['translatedText']);
    }

    public function paymentMethods(): string
    {
        return $this->view('admin/wsit_payment-methods', [
            'pageTitle' => trans('admin_payment_methods') . ' | ' . trans('admin'),
            'methods'   => payment_methods_config(),
        ], 'wsit_admin');
    }

    public function updatePaymentMethods(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/payment-methods');
        }

        $inputMethods = (array)$request->input('methods', []);
        $defaults = payment_methods_config();
        $cleaned = [];

        foreach ($defaults as $key => $def) {
            $in = $inputMethods[$key] ?? [];
            $enabled = !empty($in['enabled']);

            $cleaned[$key] = [
                'key'         => $key,
                'enabled'     => $enabled,
                'icon'        => $def['icon'] ?? '',
                'title_en'    => trim((string)($in['title_en'] ?? $def['title_en'] ?? '')),
                'title_bn'    => trim((string)($in['title_bn'] ?? $def['title_bn'] ?? '')),
                'desc_en'     => trim((string)($in['desc_en'] ?? '')),
                'desc_bn'     => trim((string)($in['desc_bn'] ?? '')),
                'account_no'  => trim((string)($in['account_no'] ?? '')),
                'account_type'=> trim((string)($in['account_type'] ?? '')),
                'require_trx' => !empty($in['require_trx']),
                'bank_name'   => trim((string)($in['bank_name'] ?? '')),
                'account_name'=> trim((string)($in['account_name'] ?? '')),
                'branch_name' => trim((string)($in['branch_name'] ?? '')),
                'routing_no'  => trim((string)($in['routing_no'] ?? '')),
            ];
        }

        $this->settingModel->set('payment_methods', json_encode($cleaned, JSON_UNESCAPED_UNICODE));

        $this->flash('success', trans('payment_methods_saved'));
        $this->redirect('/mf-dashboard/payment-methods');
    }
}
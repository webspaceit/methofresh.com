<?php

namespace App\Controllers;

use App\Core\wsit_App;
use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_CheckoutVisit;
use App\Models\wsit_Order;
use App\Models\wsit_Product;
use App\Models\wsit_SearchLog;
use App\Models\wsit_Setting;
use App\Models\wsit_User;

class wsit_AdminController extends wsit_Controller
{
    private wsit_Order $orderModel;
    private wsit_Product $productModel;
    private wsit_User $userModel;
    private wsit_Setting $settingModel;
    private wsit_SearchLog $searchLogModel;
    private wsit_CheckoutVisit $checkoutVisitModel;

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
        $this->searchLogModel = new wsit_SearchLog();
        $this->checkoutVisitModel = new wsit_CheckoutVisit();
    }

    public function dashboard(wsit_Request $request): string
    {
        $period = (string)$request->query('period', 'daily');
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $period = 'daily';
        }

        $monthStart = date('Y-m-01 00:00:00');
        $prevStart  = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $todayStart = date('Y-m-d 00:00:00');
        $yesterday  = date('Y-m-d 00:00:00', strtotime('-1 day'));

        $statusCounts = $this->orderModel->statusCounts();

        $monthRevenue = $this->orderModel->revenueBetween($monthStart);
        $prevRevenue  = $this->orderModel->revenueBetween($prevStart, $monthStart);
        $revenueChange = $prevRevenue > 0 ? round((($monthRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0.0;

        $todayOrders = $this->orderModel->countBetween($todayStart);
        $yesterdayOrders = $this->orderModel->countBetween($yesterday);
        $todayChange = $yesterdayOrders > 0 ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1) : 0.0;

        // Storefront search widget — paginated, 10 terms per page.
        $searchPerPage = 10;
        $searchPage = max(1, (int)$request->query('search_page', 1));
        $searchTermsTotal = $this->searchLogModel->countTerms(30);
        $searchPages = max(1, (int)ceil($searchTermsTotal / $searchPerPage));
        if ($searchPage > $searchPages) {
            $searchPage = $searchPages;
        }

        return $this->view('admin/wsit_dashboard', [
            'pageTitle'       => trans('admin_dashboard') . ' | ' . trans('admin'),
            'revenue'         => $this->orderModel->revenue(),
            'ordersCount'     => $this->orderModel->countAll(),
            'productsCount'   => $this->productModel->countAll(),
            'customers'       => $this->userModel->count(),
            'monthRevenue'    => $monthRevenue,
            'revenueChange'   => $revenueChange,
            'todayOrders'     => $todayOrders,
            'yesterdayOrders' => $yesterdayOrders,
            'todayChange'     => $todayChange,
            'aov'             => $this->orderModel->averageOrderValue(),
            'delivery'        => $this->orderModel->deliveryRate(),
            'daily'           => $this->orderModel->dailySeries(7),
            'chart'           => [
                'period' => $period,
                'daily'   => $this->orderModel->timeSeries('daily', 7),
                'weekly'  => $this->orderModel->timeSeries('weekly', 8),
                'monthly' => $this->orderModel->timeSeries('monthly', 12),
                'yearly'  => $this->orderModel->timeSeries('yearly', 5),
            ],
            'payRaw'          => $this->orderModel->paymentMethodTotals(),
            'statusCounts'    => $statusCounts,
            'topProducts'     => $this->productModel->topSellers(5),
            'regions'         => $this->orderModel->regionBreakdown(),
            'growth'          => $this->productModel->topCategoryGrowth(30),
            'searchStats'     => $this->searchLogModel->topTerms(30, $searchPerPage, ($searchPage - 1) * $searchPerPage),
            'searchCount30'   => $this->searchLogModel->countSearches(30),
            'searchCountToday'=> $this->searchLogModel->countSince(date('Y-m-d 00:00:00')),
            'searchPage'      => $searchPage,
            'searchPages'     => $searchPages,
            'searchPerPage'   => $searchPerPage,
            'searchTermsTotal'=> $searchTermsTotal,
            'checkoutFunnel'  => $this->checkoutVisitModel->funnel(),
            'checkoutGuests'  => $this->checkoutVisitModel->recentGuests(6),
            'recentOrders'    => $this->orderModel->recentWithItems(6),
            'totalOrders'     => (int)($statusCounts['pending'] + $statusCounts['processing'] + $statusCounts['shipped'] + $statusCounts['delivered'] + $statusCounts['cancelled']),
            'pendingCount'    => (int)$statusCounts['pending'],
        ], 'wsit_admin_material');
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
        ], 'wsit_admin_material');
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
        ], 'wsit_admin_material');
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

    /**
     * JSON for the admin header live-search dropdown. Returns locale-ready
     * (label/badge/price already formatted) lightweight order results.
     */
    public function searchJson(wsit_Request $request): void
    {
        $q = trim((string)$request->query('q', ''));

        $statusBadges = [
            'pending'    => 'bg-amber-100 text-amber-700',
            'processing' => 'bg-sky-100 text-sky-700',
            'shipped'    => 'bg-indigo-100 text-indigo-700',
            'delivered'  => 'bg-green-100 text-green-700',
            'cancelled'  => 'bg-red-100 text-red-700',
        ];
        $statusLabels = [
            'pending'    => trans('status_pending'),
            'processing' => trans('status_processing'),
            'shipped'    => trans('status_shipped'),
            'delivered'  => trans('status_delivered'),
            'cancelled'  => trans('status_cancelled'),
        ];

        $results = [];
        if ($q !== '') {
            foreach ($this->orderModel->searchLight($q, 8) as $row) {
                $results[] = [
                    'id'            => (int)$row['id'],
                    'order_number'  => (string)$row['order_number'],
                    'shipping_name' => (string)($row['shipping_name'] ?? ''),
                    'shipping_phone'=> (string)($row['shipping_phone'] ?? ''),
                    'total_label'   => '৳ ' . format_number((float)($row['total'] ?? 0)),
                    'status'        => (string)$row['status'],
                    'status_label'  => $statusLabels[$row['status']] ?? (string)$row['status'],
                    'status_badge'  => $statusBadges[$row['status']] ?? 'bg-surface-container text-on-surface-variant',
                ];
            }
        }

        $this->json(['results' => $results]);
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

    public function customers(): string
    {
        $metrics = $this->orderModel->customerMetrics();

        return $this->view('admin/wsit_customers', [
            'pageTitle'    => trans('admin_customer_analytics') . ' | ' . trans('admin'),
            'metrics'      => $metrics,
            'topCustomers' => $this->orderModel->topCustomers(8),
            'cities'       => $this->orderModel->customersByCity(8),
        ], 'wsit_admin_material');
    }

    public function deliveryTracking(wsit_Request $request): string
    {
        $filter = (string)$request->query('filter', 'queue');
        if (!in_array($filter, ['queue', 'delivered', 'all'], true)) {
            $filter = 'queue';
        }

        $queue = ($filter === 'queue' || $filter === 'all')
            ? $this->orderModel->deliveryQueue(15)
            : [];
        $delivered = ($filter === 'delivered' || $filter === 'all')
            ? $this->orderModel->recentlyDelivered(15)
            : [];

        return $this->view('admin/wsit_delivery-tracking', [
            'pageTitle'   => trans('admin_delivery_tracking') . ' | ' . trans('admin'),
            'filter'      => $filter,
            'queue'       => $queue,
            'delivered'   => $delivered,
            'statusCounts'=> $this->orderModel->statusCounts(),
            'delivery'    => $this->orderModel->deliveryRate(),
        ], 'wsit_admin_material');
    }

    public function support(): string
    {
        return $this->view('admin/wsit_support', [
            'pageTitle'    => trans('admin_support') . ' | ' . trans('admin'),
            'contactEmail' => $this->settingModel->get('contact_email', 'support@methofresh.com'),
            'phone'        => $this->settingModel->get('footer_phone', '+880 1711-111111'),
            'address'      => $this->settingModel->get(
                app_locale() === 'bn' ? 'footer_address_bn' : 'footer_address_en',
                $this->settingModel->get(app_locale() === 'bn' ? 'footer_address_en' : 'footer_address_bn', '')
            ),
        ], 'wsit_admin_material');
    }

    public function settings(): string
    {
        $globalLocale = $this->settingModel->get('default_locale', default_locale());

        return $this->view('admin/wsit_settings', [
            'pageTitle'     => trans('admin_settings') . ' | ' . trans('admin'),
            'globalLocale'  => $globalLocale,
            'contactEmail'  => $this->settingModel->get('contact_email', 'support@methofresh.com'),
        ], 'wsit_admin_material');
    }

    public function updateSettings(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $locale = $request->input('locale', 'en');

        if (is_locale($locale)) {
            $this->settingModel->set('default_locale', $locale);
            restore_locale($locale);
            $this->flash('success', trans('admin_settings_saved'));
            // Redirect to the settings page under the newly selected locale URL
            // so the URL prefix in the browser matches the saved default.
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path   = locale_url_for($locale, '/mf-dashboard/settings');
            header('Location: ' . $scheme . '://' . $host . $path);
            exit;
        }

        $this->redirect('/mf-dashboard/settings');
    }

    public function updateLogo(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        // Remove the current logo.
        if ($request->input('remove_logo') === '1') {
            $this->removeStoredLogo();
            $this->flash('success', trans('admin_logo_removed'));
            $this->redirect('/mf-dashboard/settings');
        }

        // Always save dimensions — admin can resize without re-uploading.
        $h = max(10, min(200, (int)$request->input('logo_height', '40')));
        $w = max(0,  min(600, (int)$request->input('logo_width',  '0')));
        $this->settingModel->set('logo_height', (string)$h);
        $this->settingModel->set('logo_width',  (string)$w);

        $file = $request->file('logo');
        $hasFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        if (!$hasFile) {
            // No file chosen — dimensions saved, redirect back.
            $this->flash('success', trans('admin_logo_saved'));
            $this->redirect('/mf-dashboard/settings');
        }

        $allowed = [
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/gif'     => 'gif',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
        ];
        $tmpName = (string)($file['tmp_name'] ?? '');
        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpName) ?: (string)($file['type'] ?? '');
        $ext     = $allowed[$mime] ?? null;

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

        if (!move_uploaded_file($tmpName, $dest)) {
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
        // Use finfo for real MIME detection — browser-supplied type is unreliable on Windows.
        $tmpName = (string)($file['tmp_name'] ?? '');
        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpName) ?: (string)($file['type'] ?? '');
        // .ico files may be detected as application/octet-stream by finfo; fall back to browser MIME.
        if (!isset($allowed[$mime])) {
            $mime = (string)($file['type'] ?? '');
        }
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

        if (!move_uploaded_file($tmpName, $dest)) {
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

    public function updateAnnouncement(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $offers = [];
        foreach ($request->inputArray('offers') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $item = [];
            foreach (['offer_en', 'offer_bn', 'coupon_en', 'coupon_bn', 'code'] as $field) {
                $item[$field] = trim((string)($row[$field] ?? ''));
            }
            if (implode('', $item) === '') {
                continue; // skip rows with nothing in them
            }
            $offers[] = $item;
        }

        $this->settingModel->set('ann_offers', json_encode($offers, JSON_UNESCAPED_UNICODE));
        $this->flash('success', trans('admin_announcement_saved'));
        $this->redirect('/mf-dashboard/settings');
    }

    public function updateCheckoutCities(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $cities = [];
        foreach ($request->inputArray('cities') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $en = trim((string)($row['name_en'] ?? ''));
            $bn = trim((string)($row['name_bn'] ?? ''));
            if ($en === '' && $bn === '') {
                continue; // rows with nothing in them are not stored
            }
            $cities[] = ['name_en' => $en, 'name_bn' => $bn];
        }

        $this->settingModel->set('checkout_cities', json_encode($cities, JSON_UNESCAPED_UNICODE));
        $this->flash('success', trans('admin_cities_saved'));
        $this->redirect('/mf-dashboard/settings');
    }

    public function homepage(): string
    {
        return $this->view('admin/wsit_homepage', [
            'pageTitle' => trans('admin_homepage') . ' | ' . trans('admin'),
            'blocks'    => homepage_blocks(),
            'heroSlides'=> hero_slides(),
        ], 'wsit_admin_material');
    }

    public function updateHomepage(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/homepage');
        }

        $this->saveHeroSlides($request);
        $this->saveStoryImage($request);

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

        $homeContent = $request->inputArray('home_content');
        $allowedKeys = array_merge(
            ...array_values(array_map(fn($s) => $s['fields'], homepage_sections()['sections']))
        );
        $cleanContent = [];
        foreach ($homeContent as $fieldKey => $value) {
            if (!in_array($fieldKey, $allowedKeys, true) || !is_array($value)) {
                continue;
            }
            $en = trim((string)($value['en'] ?? ''));
            $bn = trim((string)($value['bn'] ?? ''));
            $cleanContent[$fieldKey] = ['en' => $en, 'bn' => $bn];
        }
        if (array_key_exists('home_content', $request->all())) {
            $this->settingModel->set('home_content', json_encode($cleanContent, JSON_UNESCAPED_UNICODE));
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
        $tmpName = (string)($item['tmp_name'] ?? '');
        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpName) ?: (string)($item['type'] ?? '');
        $ext     = $allowed[$mime] ?? null;

        if ($ext === null || (int)($item['size'] ?? 0) > 4 * 1024 * 1024) {
            return null;
        }

        $dir = public_path('assets/img/uploads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return null;
        }

        $name = 'hero-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($tmpName, $dest)) {
            return null;
        }

        return 'assets/img/uploads/' . $name;
    }

    /**
     * Handle the dedicated "Our Story" section image upload and removal.
     */
    private function saveStoryImage(wsit_Request $request): void
    {
        // Explicit removal requested.
        if ($request->input('remove_story_image') === '1') {
            $this->removeStoryImage();
            return;
        }

        $file = $_FILES['story_image_file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return; // No upload — leave existing setting untouched.
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $tmpName = (string)($file['tmp_name'] ?? '');
        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($tmpName) ?: (string)($file['type'] ?? '');
        $ext     = $allowed[$mime] ?? null;

        if ($ext === null || (int)($file['size'] ?? 0) > 4 * 1024 * 1024) {
            $this->flash('error', trans('admin_story_image_upload_error'));
            return;
        }

        $dir = public_path('assets/img/uploads');
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return;
        }

        $name = 'story-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($tmpName, $dest)) {
            return;
        }

        $this->removeStoryImage(); // Delete old file before saving new path.
        $this->settingModel->set('story_image', 'assets/img/uploads/' . $name);
    }

    /**
     * Delete the uploaded story image file and clear its setting.
     * Only removes files stored under assets/img/uploads/story-*.
     */
    private function removeStoryImage(): void
    {
        $current = $this->settingModel->get('story_image', '');
        if ($current !== '' && str_starts_with($current, 'assets/img/uploads/story-')) {
            $file = public_path($current);
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $this->settingModel->set('story_image', '');
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
        ], 'wsit_admin_material');
    }

    public function updateFooter(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/footer');
        }

        $keys = [
            'footer_brand_en', 'footer_brand_bn',
            'footer_about_en', 'footer_about_bn',
            'footer_email', 'footer_phone',
            'footer_address_en', 'footer_address_bn',
            'copyright_en', 'copyright_bn',
            'footer_heading_links_en', 'footer_heading_links_bn',
            'footer_heading_contact_en', 'footer_heading_contact_bn',
            'footer_heading_delivery_en', 'footer_heading_delivery_bn',
            'footer_delivery_text_en', 'footer_delivery_text_bn',
            'footer_payments_label_en', 'footer_payments_label_bn',
            'footer_payments_text_en', 'footer_payments_text_bn',
        ];
        foreach ($keys as $key) {
            $this->settingModel->set($key, trim((string)$request->input($key, '')));
        }

        // Appearance — colours accept only a hex value; sizes are clamped.
        $colorKeys = [
            'footer_bg_color', 'footer_heading_color', 'footer_text_color',
            'footer_link_color', 'footer_link_hover_color', 'footer_border_color',
        ];
        foreach ($colorKeys as $key) {
            $val = trim((string)$request->input($key, ''));
            if ($val !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                $this->settingModel->set($key, $val);
            }
        }

        $numRanges = [
            'footer_font_size'    => [11, 22],
            'footer_heading_size' => [11, 26],
            'footer_padding'      => [12, 72],
        ];
        foreach ($numRanges as $key => $range) {
            $val = (int)$request->input($key, 0);
            if ($val > 0) {
                $this->settingModel->set($key, (string)max($range[0], min($range[1], $val)));
            }
        }

        // Visibility toggles — unchecked checkboxes are absent from the request.
        foreach ([
            'footer_show_brand', 'footer_show_links', 'footer_show_contact',
            'footer_show_delivery', 'footer_show_social', 'footer_show_pages_bar',
            'footer_show_brand_name',
        ] as $key) {
            $this->settingModel->set($key, $request->input($key) ? '1' : '0');
        }

        $this->saveFooterQuickLinks($request);
        $this->saveFooterSocialLinks($request);

        $this->flash('success', trans('admin_saved'));
        $this->redirect('/mf-dashboard/footer');
    }

    public function header(): string
    {
        return $this->view('admin/wsit_header', [
            'pageTitle' => trans('admin_header') . ' | ' . trans('admin'),
        ], 'wsit_admin_material');
    }

    public function updateHeader(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/header');
        }

        // Appearance — colours accept only a hex value; sizes are clamped.
        $colorKeys = [
            'header_bg_color', 'header_text_color', 'header_text_hover_color',
            'nav_bg_color', 'nav_text_color', 'nav_accent_color',
            'ann_bg_color', 'ann_text_color',
        ];
        foreach ($colorKeys as $key) {
            $val = trim((string)$request->input($key, ''));
            if ($val !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                $this->settingModel->set($key, $val);
            }
        }

        $numRanges = [
            'nav_font_size'     => [11, 20],
            'header_label_size' => [9, 16],
            'header_padding'    => [6, 28],
            'nav_padding'       => [4, 24],
        ];
        foreach ($numRanges as $key => $range) {
            $val = (int)$request->input($key, 0);
            if ($val > 0) {
                $this->settingModel->set($key, (string)max($range[0], min($range[1], $val)));
            }
        }

        // Visibility toggles — unchecked checkboxes are absent from the request.
        foreach (['header_show_announcement', 'header_show_nav'] as $key) {
            $this->settingModel->set($key, $request->input($key) ? '1' : '0');
        }

        $this->flash('success', trans('admin_saved'));
        $this->redirect('/mf-dashboard/header');
    }

    private function saveFooterSocialLinks(wsit_Request $request): void
    {
        $raw     = $request->inputArray('social_links', []);
        $removes = $request->inputArray('social_links_remove', []);
        $allowed = ['facebook', 'instagram', 'youtube', 'twitter', 'x', 'whatsapp', 'tiktok', 'linkedin'];
        $links   = [];

        foreach ($raw as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!empty($removes[$key])) {
                continue;
            }
            $url = trim((string)($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $platform = trim((string)($item['platform'] ?? ''));
            if (!in_array($platform, $allowed, true)) {
                $platform = 'facebook';
            }
            $links[] = [
                'platform' => $platform,
                'url'      => $url,
                'enabled'  => empty($item['enabled']) ? 0 : 1,
            ];
        }

        $this->settingModel->set('footer_social_links', json_encode($links, JSON_UNESCAPED_UNICODE));
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

    public function updateNavMenu(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $raw     = $request->inputArray('nav_menu', []);
        $removes = $request->inputArray('nav_menu_remove', []);
        $items   = [];

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
            $icon = trim((string)($item['icon'] ?? ''));
            if ($icon !== '' && !nav_icon_valid($icon)) {
                $icon = '';
            }
            $items[] = [
                'label_en' => $labelEn,
                'label_bn' => $labelBn,
                'url'      => $url,
                'icon'     => $icon,
                'target'   => empty($item['target']) ? 0 : 1,
                'enabled'  => empty($item['enabled']) ? 0 : 1,
                'order'    => max(0, (int)($item['order'] ?? 0)),
            ];
        }

        // Stable sort by the admin-set order number.
        usort($items, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);

        $this->settingModel->set('nav_menu', json_encode($items, JSON_UNESCAPED_UNICODE));

        $this->flash('success', trans('admin_saved'));
        $this->redirect('/mf-dashboard/settings');
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
        ], 'wsit_admin_material');
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

    public function shippingMethods(): string
    {
        return $this->view('admin/wsit_shipping', [
            'pageTitle' => trans('admin_shipping_methods') . ' | ' . trans('admin'),
            'shipping'  => shipping_config(),
        ], 'wsit_admin_material');
    }

    public function updateShippingMethods(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/shipping');
        }

        $cleaned = [];
        foreach ((array)$request->inputArray('methods') as $row) {
            if (!is_array($row)) {
                continue;
            }
            $nameEn = trim((string)($row['name_en'] ?? ''));
            $nameBn = trim((string)($row['name_bn'] ?? ''));
            $base   = trim((string)($row['base'] ?? ''));
            $perKg  = trim((string)($row['per_kg'] ?? ''));
            $areas  = trim((string)($row['areas'] ?? ''));
            $exclude = trim((string)($row['exclude'] ?? ''));

            // Rows the admin left completely blank (the "add" template) are
            // not stored — same rule as the announcement-offer rows.
            if ($nameEn === '' && $nameBn === '' && $base === '' && $perKg === ''
                && $areas === '' && $exclude === '') {
                continue;
            }

            $cleaned[] = [
                'id'      => trim((string)($row['id'] ?? '')) !== '' ? trim((string)$row['id']) : ('m' . uniqid()),
                'name_en' => $nameEn,
                'name_bn' => $nameBn,
                'enabled' => !empty($row['enabled']),
                'base'    => max(0.0, (float)$base),
                'per_kg'  => max(0.0, (float)$perKg),
                'areas'   => $areas,
                'exclude' => $exclude,
            ];
        }

        $this->settingModel->set('shipping_config', json_encode([
            'enabled'   => $request->input('shipping_enabled') ? 1 : 0,
            'free_over' => max(0.0, (float)$request->input('free_over', 500)),
            'methods'   => $cleaned,
        ], JSON_UNESCAPED_UNICODE));

        $this->flash('success', trans('admin_shipping_saved'));
        $this->redirect('/mf-dashboard/shipping');
    }

    /**
     * Persist the Facebook Pixel configuration (pixel ID, master switch,
     * test mode and per-event toggles) using the dedicated model.
     */
    public function updateFacebookPixel(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/settings');
        }

        $pixelModel = new \App\Models\wsit_FacebookPixel();
        $pixelModel->save([
            'pixel_id'  => $request->input('facebook_pixel_id', ''),
            'enabled'   => $request->input('facebook_pixel_enabled', '0') === '1' ? '1' : '0',
            'test_mode' => $request->input('facebook_pixel_test_mode', '0') === '1' ? '1' : '0',
            'events'    => [
                'view_content'      => $request->input('facebook_pixel_event_view_content', '0') === '1' ? '1' : '0',
                'add_to_cart'       => $request->input('facebook_pixel_event_add_to_cart', '0') === '1' ? '1' : '0',
                'initiate_checkout' => $request->input('facebook_pixel_event_initiate_checkout', '0') === '1' ? '1' : '0',
                'purchase'          => $request->input('facebook_pixel_event_purchase', '0') === '1' ? '1' : '0',
            ],
        ]);

        $this->flash('success', trans('admin_facebook_pixel_saved'));
        $this->redirect('/mf-dashboard/settings');
    }
}
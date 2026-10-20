<?php
$pageTitle = $pageTitle ?? trans('admin');
$currentUser = current_user();
$locale = app_locale();
$adminPath = current_page_path();

// Pending-order badge — controllers already pass it on the dashboard; on every
// other admin page we fall back to a single cheap count query.
// Real "Live Status" numbers for the sidebar card are computed here too.
if (!isset($pendingCount) || !isset($todayOrders) || !isset($deliveredCount) || !isset($lowStockCount)) {
    try {
        $orderModel     = new \App\Models\wsit_Order();
        $statusCounts   = $orderModel->statusCounts();
        $pendingCount   = $pendingCount   ?? (int)($statusCounts['pending'] ?? 0);
        $todayOrders    = $todayOrders    ?? (int)$orderModel->countBetween(date('Y-m-d 00:00:00'));
        $deliveredCount = $deliveredCount ?? (int)($statusCounts['delivered'] ?? 0);
        $lowStockCount  = $lowStockCount  ?? (int)(new \App\Models\wsit_Product())->countLowStock(15);
    } catch (\Throwable $e) {
        $pendingCount   = $pendingCount   ?? 0;
        $todayOrders    = $todayOrders    ?? 0;
        $deliveredCount = $deliveredCount ?? 0;
        $lowStockCount  = $lowStockCount  ?? 0;
    }
}
$pendingCount   = max(0, (int)$pendingCount);
$todayOrders    = max(0, (int)$todayOrders);
$deliveredCount = max(0, (int)$deliveredCount);
$lowStockCount  = max(0, (int)$lowStockCount);
$notifTotal     = max(0, $pendingCount + $todayOrders + $lowStockCount);

// Unread contact-form messages — shown as a sidebar badge on the Messages link.
try {
    $unreadMessages = max(0, (int)(new \App\Models\wsit_ContactMessage())->unreadCount());
} catch (\Throwable $e) {
    $unreadMessages = 0;
}

$adminName = user_display_name($currentUser, $locale);
$newProductUrl = locale_url('/mf-dashboard/products/create');
$ordersUrl = locale_url('/mf-dashboard/orders');
$productsUrl = locale_url('/mf-dashboard/products');
$categoriesUrl = locale_url('/mf-dashboard/categories');
$revenueUrl = locale_url('/mf-dashboard/payment-methods');
$pagesUrl = locale_url('/mf-dashboard/pages');
$homepageUrl = locale_url('/mf-dashboard/homepage');
$footerUrl = locale_url('/mf-dashboard/footer');
$headerUrl = locale_url('/mf-dashboard/header');
$settingsUrl = locale_url('/mf-dashboard/settings');
$logoutUrl = locale_url('/logout');

// Active navigation item detector.
$isNavActive = function (string $path, bool $exact = false) use ($adminPath): bool {
    $cleanPath  = trim($path, '/');
    $cleanAdmin = trim($adminPath, '/');
    if ($exact) {
        return $cleanAdmin === $cleanPath;
    }
    return $cleanAdmin === $cleanPath || str_starts_with($cleanAdmin, $cleanPath . '/');
};

$isBn = $locale === 'bn';

// Pending-order badge: Bengali digits when the locale is Bengali, latin otherwise.
$pendingBadge = $isBn
    ? str_replace(
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
        (string)$pendingCount
    )
    : (string)$pendingCount;

// Count formatter for notification badges/lines (Bengali digits when bn).
$fmtCount = function (int $n) use ($isBn): string {
    return $isBn
        ? str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            (string)max(0, $n)
        )
        : (string)max(0, $n);
};

// Chrome UI strings — Bengali (mockup) vs English (the active site locale,
// which the admin controls from Settings > Language).
$t = $isBn ? [
    'brand'         => 'মেঠো ফ্রেশ',
    'outlet'        => 'মেঠো ফ্রেশ - প্রধান আউটলেট',
    'outletSub'     => 'ঢাকা সেন্ট্রাল হাব • মিরপুর',
    'catMain'       => 'মূল মেনু',
    'catCatalog'    => 'পণ্য ও স্টক',
    'catCustomers'  => 'গ্রাহক ও বার্তা',
    'catStorefront' => 'অনলাইন স্টোর (CMS)',
    'catSystem'     => 'পেমেন্ট ও সেটিংস',
    'dashboard'     => 'ড্যাশবোর্ড',
    'orders'        => 'অর্ডার ব্যবস্থাপনা',
    'products'      => 'পণ্য তালিকা',
    'categories'    => 'ক্যাটাগরি',
    'revenue'       => 'পেমেন্ট গেটওয়ে',
    'content'       => 'কনটেন্ট',
    'pages'         => 'পেজ সমূহ',
    'homepage'      => 'হোমপেজ ডিজাইন',
    'footer'        => 'ফুটার ও ইনফো',
    'header'        => 'হেডার ও মেনু',
    'messages'      => 'যোগাযোগ বার্তা',
    'subscribers'   => 'নিউজলেটার সাবস্ক্রাইবার',
    'seo'           => 'SEO সেটিংস',
    'analytics'     => 'বিশ্লেষণ',
    'customers'     => 'গ্রাহক বিশ্লেষণ',
    'delivery'      => 'ডেলিভারি ট্র্যাকিং',
    'shipping'      => 'শিপিং মেথড',
    'settings'      => 'স্টোর সেটিংস',
    'newBadge'      => ' নতুন',
    'liveStatus'    => 'লাইভ স্ট্যাটাস',
    'pending'       => 'পেন্ডিং',
    'ordersToday'   => 'আজকের অর্ডার',
    'delivered'     => 'ডেলিভারি সম্পন্ন',
    'help'          => 'সহায়তা ও সাপোর্ট',
    'viewSite'      => 'ওয়েবসাইট দেখুন',
    'logout'        => 'লগআউট',
    'searchPh'      => 'অর্ডার আইডি, ফোন বা গ্রাহকের নাম দিয়ে খুঁজুন...',
    'searching'     => 'খোঁজা হচ্ছে…',
    'noResults'     => 'কোনো অর্ডার পাওয়া যায়নি',
    'reportChip'    => 'আজকের রিপোর্ট (গত ৩০ দিন)',
    'pDaily'        => 'দৈনিক রিপোর্ট',
    'pWeekly'       => 'সাপ্তাহিক রিপোর্ট',
    'pMonthly'      => 'মাসিক রিপোর্ট',
    'pYearly'       => 'বার্ষিক রিপোর্ট',
    'addProduct'    => 'নতুন পণ্য যুক্ত করুন',
    'notifTitle'    => 'নোটিফিকেশন',
    'notifPending'  => '{n}টি পেন্ডিং অর্ডার',
    'notifLowStock' => '{n}টি পণ্যের স্টক কম',
    'notifToday'    => 'আজ {n}টি নতুন অর্ডার',
    'notifNone'     => 'কোনো নোটিফিকেশন নেই',
    'role'          => 'সুপার অ্যাডমিন',
    'tagline'       => 'জৈব পণ্যের বিশ্বস্ত দোকান',
    'live'          => 'লাইভ ↗',
] : [
    'brand'         => 'Metho Fresh',
    'outlet'        => 'Metho Fresh - Main outlet',
    'outletSub'     => 'Dhaka Central Hub • Mirpur',
    'catMain'       => 'Main Menu',
    'catCatalog'    => 'Catalog & Stock',
    'catCustomers'  => 'Customers & Growth',
    'catStorefront' => 'Storefront CMS',
    'catSystem'     => 'Finance & Settings',
    'dashboard'     => 'Dashboard',
    'orders'        => 'Order Management',
    'products'      => 'Products & Stock',
    'categories'    => 'Categories',
    'revenue'       => 'Payment Gateways',
    'content'       => 'Content',
    'pages'         => 'Custom Pages',
    'homepage'      => 'Homepage Design',
    'footer'        => 'Footer & Info',
    'header'        => 'Header & Menu',
    'messages'      => 'Contact Messages',
    'subscribers'   => 'Newsletter Subscribers',
    'seo'           => 'SEO Settings',
    'analytics'     => 'Analytics',
    'customers'     => 'Customer Analytics',
    'delivery'      => 'Delivery Tracking',
    'shipping'      => 'Shipping Methods',
    'settings'      => 'Store Settings',
    'newBadge'      => ' new',
    'liveStatus'    => 'Live Status',
    'pending'       => 'pending',
    'ordersToday'   => 'orders today',
    'delivered'     => 'delivered',
    'help'          => 'Help & Support',
    'viewSite'      => 'View website',
    'logout'        => 'Logout',
    'searchPh'      => 'Search by order ID, phone or customer name...',
    'searching'     => 'Searching…',
    'noResults'     => 'No orders found',
    'reportChip'    => "Today's report (last 30 days)",
    'pDaily'        => 'Daily report',
    'pWeekly'       => 'Weekly report',
    'pMonthly'      => 'Monthly report',
    'pYearly'       => 'Yearly report',
    'addProduct'    => 'Add new product',
    'notifTitle'    => 'Notifications',
    'notifPending'  => '{n} pending orders',
    'notifLowStock' => '{n} products low on stock',
    'notifToday'    => '{n} new orders today',
    'notifNone'     => 'No notifications',
    'role'          => 'Super Admin',
    'tagline'       => 'Organic Merchant',
    'live'          => 'Live ↗',
];

// Report-period state for the header chip. On the dashboard ?period= drives
// the Revenue & Orders chart; the chip mirrors it so the header control acts
// as a shortcut that actually switches the report period.
$reportPeriodActive = (string)($_GET['period'] ?? '');
if (!in_array($reportPeriodActive, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $reportPeriodActive = '';
}
$reportPeriodChip = $reportPeriodActive !== ''
    ? ($t['p' . ucfirst($reportPeriodActive)] ?? $t['reportChip'])
    : $t['reportChip'];
$reportPeriodOptions = [
    'daily'   => $t['pDaily'],
    'weekly'  => $t['pWeekly'],
    'monthly' => $t['pMonthly'],
    'yearly'  => $t['pYearly'],
];

// Outlet identity: the sub-line shows the REAL registered business address,
// editable from Settings > Website Footer. The label above it is a static UI
// descriptor — the DB has no dedicated "outlet name" field.
$outletSub = trim($isBn ? site_setting('footer_address_bn', '') : site_setting('footer_address_en', ''));
if ($outletSub === '') {
    $outletSub = $t['outletSub'];
}

// Category Section Header Component (Eye-catching banner with domain icon badge & counter)
$catHeader = function (string $title, string $icon, string $color = 'emerald', string $count = '', bool $first = false) use ($isBn): string {
    $countDisplay = $isBn
        ? str_replace(['0','1','2','3','4','5','6','7','8','9'], ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], $count)
        : $count;

    $themes = [
        'emerald' => [
            'card'  => 'from-emerald-500/12 via-emerald-500/6 to-transparent border-emerald-500/30',
            'icon'  => 'bg-emerald-600 text-white shadow-emerald-700/25',
            'title' => 'text-emerald-950',
            'badge' => 'text-emerald-800 border-emerald-300/70 bg-emerald-50/95',
        ],
        'amber'   => [
            'card'  => 'from-amber-500/12 via-amber-500/6 to-transparent border-amber-500/30',
            'icon'  => 'bg-amber-600 text-white shadow-amber-700/25',
            'title' => 'text-amber-950',
            'badge' => 'text-amber-800 border-amber-300/70 bg-amber-50/95',
        ],
        'blue'    => [
            'card'  => 'from-sky-500/12 via-sky-500/6 to-transparent border-sky-500/30',
            'icon'  => 'bg-sky-600 text-white shadow-sky-700/25',
            'title' => 'text-sky-950',
            'badge' => 'text-sky-800 border-sky-300/70 bg-sky-50/95',
        ],
        'purple'  => [
            'card'  => 'from-purple-500/12 via-purple-500/6 to-transparent border-purple-500/30',
            'icon'  => 'bg-purple-600 text-white shadow-purple-700/25',
            'title' => 'text-purple-950',
            'badge' => 'text-purple-800 border-purple-300/70 bg-purple-50/95',
        ],
        'rose'    => [
            'card'  => 'from-rose-500/12 via-rose-500/6 to-transparent border-rose-500/30',
            'icon'  => 'bg-rose-600 text-white shadow-rose-700/25',
            'title' => 'text-rose-950',
            'badge' => 'text-rose-800 border-rose-300/70 bg-rose-50/95',
        ],
    ];

    $th  = $themes[$color] ?? $themes['emerald'];
    $pad = $first ? 'pt-1.5 pb-1' : 'pt-3.5 pb-1';

    return '<div class="' . $pad . ' select-none">'
        . '<div class="flex items-center justify-between px-3 py-2 rounded-xl bg-gradient-to-r ' . $th['card'] . ' border shadow-[0_1px_3px_rgba(0,0,0,0.03)]">'
            . '<div class="flex items-center gap-2.5 min-w-0">'
                . '<span class="w-6 h-6 rounded-lg ' . $th['icon'] . ' shadow-xs flex items-center justify-center shrink-0">'
                    . '<span class="material-symbols-outlined text-[15px] leading-none">' . htmlspecialchars($icon) . '</span>'
                . '</span>'
                . '<span class="text-[14px] font-bold ' . $th['title'] . ' font-sans tracking-tight truncate leading-tight">'
                    . htmlspecialchars($title)
                . '</span>'
            . '</div>'
            . ($count !== '' ? '<span class="text-[11px] font-extrabold ' . $th['badge'] . ' px-2 py-0.5 rounded-md border shadow-2xs leading-none shrink-0">' . htmlspecialchars($countDisplay) . '</span>' : '')
        . '</div>'
        . '</div>';
};

// Nav Link Component
$navLink = function (
    string $url,
    string $icon,
    string $label,
    bool $isActive,
    bool $badge = false,
    string $badgeText = '',
    string $badgeColor = 'amber'
) use ($t): string {
    if ($isActive) {
        $linkClasses = 'group flex items-center justify-between px-3 py-2 rounded-xl bg-primary text-white font-semibold shadow-[0_2px_6px_rgba(0,40,25,0.2)] transition-all text-[13.5px]';
        $iconClasses = 'material-symbols-outlined text-[19px] text-primary-fixed shrink-0';
        $badgeClasses = 'bg-white/20 text-white ring-1 ring-inset ring-white/30';
    } else {
        $linkClasses = 'group flex items-center justify-between px-3 py-2 rounded-xl text-stone-600 hover:text-stone-900 hover:bg-stone-100/90 font-medium transition-all text-[13.5px]';
        $iconClasses = 'material-symbols-outlined text-[19px] text-stone-400 group-hover:text-primary transition-colors shrink-0';
        $badgeClasses = $badgeColor === 'emerald'
            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'
            : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20';
    }

    $badgeHtml = $badge
        ? '<span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold ' . $badgeClasses . '">'
            . htmlspecialchars($badgeText)
            . '<span class="font-normal opacity-75 ml-0.5">' . htmlspecialchars($t['newBadge']) . '</span>'
          . '</span>'
        : '';

    return '<a class="' . $linkClasses . '" href="' . htmlspecialchars($url) . '"' . ($isActive ? ' aria-current="page"' : '') . '>'
        . '<div class="flex items-center gap-2.5 min-w-0 overflow-hidden">'
            . '<span class="' . $iconClasses . '">' . htmlspecialchars($icon) . '</span>'
            . '<span class="truncate leading-tight">' . htmlspecialchars($label) . '</span>'
        . '</div>'
        . $badgeHtml
        . '</a>';
};
?>
<!DOCTYPE html>
<html lang="<?= e($locale === 'bn' ? 'bn' : 'en') ?>"><head><meta charset="UTF-8"><meta name="robots" content="noindex, nofollow"><meta content="width=device-width, initial-scale=1.0" name="viewport"/><meta content="web_dashboard" name="shell-type"/>
<title><?= e($pageTitle) ?></title>
<?php $favicon = site_favicon_url(); ?>
<?php if ($favicon !== ''): ?>
<link rel="icon" href="<?= e($favicon) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥦</text></svg>">
<?php endif; ?>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link rel="preload" as="font" href="https://fonts.gstatic.com/s/notosansbengali/v16/r5Yodf1kK-2LrIQaT5fC6Igp9Tprq5T6s1qng2kC.woff2" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet"/>
<style>
@font-face {
    font-family: 'Material Symbols Outlined';
    font-style: normal;
    font-weight: 100 700;
    font-display: swap;
    src: url('<?= e(public_url('assets/fonts/material-symbols-outlined.woff2')) ?>') format('woff2');
}
@layer base{html,body{margin:0;padding:0;font-family:<?= $locale === 'bn' ? "'Noto Sans Bengali',sans-serif" : "'Inter','Noto Sans Bengali',sans-serif" ?>;}body{overscroll-behavior:none;}main>:first-child{margin-top:0!important;}main>:last-child{margin-bottom:0!important;}}.shadow-xs{box-shadow:0 1px 2px 0 rgb(0 0 0/.05);}.material-symbols-outlined{font-family:'Material Symbols Outlined';font-weight:normal;font-style:normal;font-size:24px;line-height:1;letter-spacing:normal;text-transform:none;display:inline-block;white-space:nowrap;word-wrap:normal;direction:ltr;font-feature-settings:'liga';-webkit-font-feature-settings:'liga';-webkit-font-smoothing:antialiased;}
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(150, 150, 150, 0.25) transparent;
}
.sidebar-scroll::-webkit-scrollbar {
    width: 4px;
}
.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.12);
    border-radius: 9999px;
}
.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.24);
}
[lang="bn"], [lang="bn"] *, :lang(bn), .font-bengali {
    font-family: 'Noto Sans Bengali', sans-serif !important;
}
<?php if ($locale === 'bn'): ?>
html, body, button, input, select, textarea, [class*="font-"] {
    font-family: 'Noto Sans Bengali', sans-serif !important;
}
<?php endif; ?>
</style>
<script>
// Show icons only after Material Symbols font is loaded to prevent ligature text flash
document.fonts.load('1em Material Symbols Outlined').then(function(){
    document.documentElement.classList.add('fonts-loaded');
});
// Fallback: show after 3s even if font never loads
setTimeout(function(){ document.documentElement.classList.add('fonts-loaded'); }, 3000);
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script id="tailwind-config">tailwind.config={theme:{extend:{"colors":{"primary-fixed":"#c0edd4","surface-bright":"#f9f9ff","on-background":"#141b2b","on-secondary-container":"#663500","surface-variant":"#dce2f7","surface-container-high":"#e1e8fd","primary-fixed-dim":"#a5d0b9","on-surface-variant":"#414844","error":"#ba1a1a","on-primary-container":"#7fa993","error-container":"#ffdad6","background":"#f9f9ff","on-tertiary":"#ffffff","inverse-primary":"#a5d0b9","on-primary-fixed":"#002114","outline":"#717973","outline-variant":"#c1c8c2","on-surface":"#141b2b","on-primary":"#ffffff","secondary":"#904d00","on-error-container":"#93000a","on-tertiary-fixed-variant":"#005236","tertiary-fixed":"#6ffbbe","secondary-fixed-dim":"#ffb77d","on-error":"#ffffff","tertiary-container":"#00402a","surface-dim":"#d3daef","on-secondary-fixed-variant":"#6e3900","surface":"#f9f9ff","inverse-surface":"#293040","on-primary-fixed-variant":"#264e3c","on-tertiary-fixed":"#002113","tertiary":"#002819","surface-container-highest":"#dce2f7","surface-container":"#e9edff","primary":"#002819","on-secondary":"#ffffff","secondary-fixed":"#ffdcc3","surface-container-low":"#f1f3ff","secondary-container":"#fe932c","surface-container-lowest":"#ffffff","tertiary-fixed-dim":"#4edea3","surface-tint":"#3e6653","on-secondary-fixed":"#2f1500","on-tertiary-container":"#06b77f","inverse-on-surface":"#edf0ff","primary-container":"#153e2d","brand":{"50":"#f0f5ee","100":"#dce7d9","200":"#bad0b6","300":"#8fb18a","400":"#5d8b5c","500":"#3f6f42","600":"#2a552f","700":"#194224","800":"#123019","900":"#0b2010","950":"#06140a"},"honey":{"50":"#fef9ea","100":"#f8dc90","200":"#f5ce6c","300":"#f2a635","400":"#e08b1f","500":"#c6711a","600":"#a35715","700":"#7c4213","800":"#59300f","900":"#3b200a"},"cream":"#f7f6ea"},"borderRadius":{"DEFAULT":"0.125rem","md":"0.375rem","lg":"0.25rem","xl":"0.5rem","2xl":"1rem","full":"0.75rem"},"spacing":{"margin":"1rem","gutter":"1rem","space-xs":"0.25rem","space-lg":"1.5rem","space-2xl":"3rem","space-xl":"2rem","space-sm":"0.5rem","gutter-lg":"1.5rem","margin-md":"1.5rem","space-md":"1rem","margin-lg":"2rem"},"fontFamily":{"sans":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"bengali":["Noto Sans Bengali","sans-serif"],"body-lg":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"metric-stat-mobile":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"label-sm":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"body-sm":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"label-lg":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"display":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"headline-lg":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"display-mobile":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"headline-md":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"body-md":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"headline-sm":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"label-md":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"metric-stat":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>,"headline-lg-mobile":<?= $locale === 'bn' ? '["Noto Sans Bengali","sans-serif"]' : '["Inter","Noto Sans Bengali","sans-serif"]' ?>},"fontSize":{"body-lg":["16px",{"lineHeight":"24px","fontWeight":"400"}],"metric-stat-mobile":["24px",{"lineHeight":"30px","fontWeight":"700"}],"label-sm":["11px",{"lineHeight":"14px","letterSpacing":"0.04em","fontWeight":"600"}],"body-sm":["12px",{"lineHeight":"16px","fontWeight":"400"}],"label-lg":["14px",{"lineHeight":"20px","fontWeight":"500"}],"display":["36px",{"lineHeight":"44px","fontWeight":"700"}],"headline-lg":["28px",{"lineHeight":"36px","fontWeight":"600"}],"display-mobile":["28px",{"lineHeight":"36px","fontWeight":"700"}],"headline-md":["20px",{"lineHeight":"28px","fontWeight":"600"}],"body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],"headline-sm":["16px",{"lineHeight":"24px","fontWeight":"600"}],"label-md":["12px",{"lineHeight":"16px","fontWeight":"500"}],"metric-stat":["30px",{"lineHeight":"36px","fontWeight":"700"}],"headline-lg-mobile":["22px",{"lineHeight":"30px","fontWeight":"600"}]}}}}</script>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased" x-data="{ sidebarOpen: false }">
<!-- Mobile drawer backdrop (visible only while the drawer is open, below md) -->
<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-[45] bg-inverse-surface/45 backdrop-blur-sm md:hidden" style="display: none;"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : ''"
       class="fixed left-0 top-0 h-full w-72 bg-white border-r border-stone-200/80 shadow-[0_1px_8px_rgba(0,0,0,0.03)] z-50 flex flex-col justify-between select-none transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0">
    
    <!-- Top Identity Area (Pinned) -->
    <div class="shrink-0 flex flex-col">
        <div class="px-4 pt-4 pb-3 flex items-center gap-3 border-b border-stone-100">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shadow-sm text-primary-fixed shrink-0">
                <span class="material-symbols-outlined text-[22px]">eco</span>
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-base font-bold text-stone-900 leading-tight truncate"><?= e($t['brand']) ?></span>
                <span class="text-[10px] text-stone-600 uppercase tracking-widest font-semibold"><?= e($t['tagline']) ?></span>
            </div>
        </div>
        <div class="px-3 pt-2.5 pb-1">
            <div class="flex items-center justify-between p-2 rounded-xl bg-stone-50 border border-stone-200/60 transition-colors">
                <div class="flex items-center gap-2 min-w-0 overflow-hidden">
                    <span class="material-symbols-outlined text-stone-500 text-[18px] shrink-0">storefront</span>
                    <div class="flex flex-col truncate leading-tight">
                        <span class="text-xs font-semibold text-stone-800 truncate"><?= e($t['outlet']) ?></span>
                        <span class="text-[11px] text-stone-600 truncate"><?= e($outletSub) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorized Menus (Smooth Scrollable Area) -->
    <nav class="flex-1 min-h-0 overflow-y-auto sidebar-scroll px-3 py-2 space-y-3" @click="sidebarOpen = false">
        
        <!-- 1. Main Menu -->
        <div class="flex flex-col gap-0.5">
            <?= $catHeader($t['catMain'], 'widgets', 'emerald', '3', true) ?>
            <?= $navLink(locale_url('/mf-dashboard'), 'space_dashboard', $t['dashboard'], $isNavActive('mf-dashboard', true)) ?>
            <?= $navLink($ordersUrl, 'local_mall', $t['orders'], $isNavActive('mf-dashboard/orders'), $pendingCount > 0, $pendingBadge, 'amber') ?>
            <?= $navLink(locale_url('/mf-dashboard/delivery-tracking'), 'local_shipping', $t['delivery'], $isNavActive('mf-dashboard/delivery-tracking')) ?>
        </div>

        <!-- 2. Catalog & Stock -->
        <div class="flex flex-col gap-0.5">
            <?= $catHeader($t['catCatalog'], 'inventory_2', 'amber', '2') ?>
            <?= $navLink($productsUrl, 'inventory_2', $t['products'], $isNavActive('mf-dashboard/products')) ?>
            <?= $navLink($categoriesUrl, 'category', $t['categories'], $isNavActive('mf-dashboard/categories')) ?>
        </div>

        <!-- 3. Customers & Growth -->
        <div class="flex flex-col gap-0.5">
            <?= $catHeader($t['catCustomers'], 'groups', 'blue', '3') ?>
            <?= $navLink(locale_url('/mf-dashboard/customers'), 'group', $t['customers'], $isNavActive('mf-dashboard/customers')) ?>
            <?= $navLink(locale_url('/mf-dashboard/messages'), 'mark_email_unread', $t['messages'], $isNavActive('mf-dashboard/messages'), $unreadMessages > 0, $fmtCount($unreadMessages), 'emerald') ?>
            <?= $navLink(locale_url('/mf-dashboard/subscribers'), 'mail', $t['subscribers'], $isNavActive('mf-dashboard/subscribers')) ?>
        </div>

        <!-- 4. Storefront CMS -->
        <div class="flex flex-col gap-0.5">
            <?= $catHeader($t['catStorefront'], 'palette', 'purple', '4') ?>
            <?= $navLink($homepageUrl, 'storefront', $t['homepage'], $isNavActive('mf-dashboard/homepage')) ?>
            <?= $navLink($pagesUrl, 'article', $t['pages'], $isNavActive('mf-dashboard/pages')) ?>
            <?= $navLink($headerUrl, 'web_asset', $t['header'], $isNavActive('mf-dashboard/header')) ?>
            <?= $navLink($footerUrl, 'view_day', $t['footer'], $isNavActive('mf-dashboard/footer')) ?>
        </div>

        <!-- 5. Finance & Settings -->
        <div class="flex flex-col gap-0.5">
            <?= $catHeader($t['catSystem'], 'settings_suggest', 'rose', '4') ?>
            <?= $navLink($revenueUrl, 'payments', $t['revenue'], $isNavActive('mf-dashboard/payment-methods')) ?>
            <?= $navLink(locale_url('/mf-dashboard/shipping'), 'move_to_inbox', $t['shipping'], $isNavActive('mf-dashboard/shipping')) ?>
            <?= $navLink(locale_url('/mf-dashboard/seo'), 'search', $t['seo'], $isNavActive('mf-dashboard/seo')) ?>
            <?= $navLink($settingsUrl, 'tune', $t['settings'], $isNavActive('mf-dashboard/settings')) ?>
        </div>
    </nav>

    <!-- Bottom Actions & Live Status (Pinned) -->
    <div class="shrink-0 p-3 border-t border-stone-200/70 bg-stone-50/50 flex flex-col gap-1.5" @click="sidebarOpen = false">
        
        <!-- Live Status Card -->
        <div class="p-3 mb-1 rounded-xl bg-gradient-to-br from-stone-50 to-emerald-50/40 border border-stone-200/80 flex flex-col gap-2 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold text-stone-800 uppercase tracking-wider"><?= e($t['liveStatus']) ?></span>
                </div>
                <span class="text-[11px] font-semibold <?= $pendingCount > 0 ? 'text-amber-800 bg-amber-100/80 border border-amber-200' : 'text-stone-700 bg-stone-100 border border-stone-200' ?> px-1.5 py-0.5 rounded-md">
                    <?= e(format_number($pendingCount)) ?> <?= e($t['pending']) ?>
                </span>
            </div>
            <div class="flex items-center justify-between text-[11px] text-stone-600 font-medium pt-1 border-t border-stone-200/50">
                <span><?= e(format_number($todayOrders)) ?> <?= e($t['ordersToday']) ?></span>
                <span class="text-stone-300">•</span>
                <span><?= e(format_number($deliveredCount)) ?> <?= e($t['delivered']) ?></span>
            </div>
        </div>

        <?= $navLink(locale_url('/mf-dashboard/support'), 'support_agent', $t['help'], $isNavActive('mf-dashboard/support')) ?>
        
        <a class="group flex items-center justify-between px-3 py-2 rounded-xl text-stone-600 hover:text-stone-900 hover:bg-stone-100/90 font-medium transition-all text-[13px]" href="<?= e(locale_url('/')) ?>" target="_blank" rel="noopener">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-[19px] text-stone-400 group-hover:text-primary transition-colors shrink-0">open_in_new</span>
                <span><?= e($t['viewSite']) ?></span>
            </div>
            <span class="text-[10px] text-stone-600 uppercase font-semibold"><?= e($t['live']) ?></span>
        </a>

        <a class="group flex items-center gap-2.5 px-3 py-2 rounded-xl text-stone-600 hover:text-red-700 hover:bg-red-50/80 font-medium transition-all text-[13px]" href="<?= e($logoutUrl) ?>">
            <span class="material-symbols-outlined text-[19px] text-stone-400 group-hover:text-red-600 transition-colors shrink-0">logout</span>
            <span><?= e($t['logout']) ?></span>
        </a>
    </div>
</aside>
<div class="pl-0 md:pl-72">
    <header class="fixed top-0 left-0 md:left-72 right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 flex items-center justify-between gap-space-sm px-4 md:px-space-xl">
        <div class="flex items-center gap-space-md flex-1 min-w-0 max-w-xl">
            <!-- Mobile hamburger (below md) -->
            <button type="button" @click="sidebarOpen = !sidebarOpen" class="md:hidden w-10 h-10 shrink-0 rounded-xl bg-surface-container-low hover:bg-surface-container-high flex items-center justify-center transition-colors">
                <span class="material-symbols-outlined text-on-surface-variant text-[20px]">menu</span>
            </button>
            <!-- Header search → live search dropdown (orders) -->
            <div class="relative w-full min-w-0" x-data="headerSearch()" data-endpoint="<?= e(locale_url('/mf-dashboard/search')) ?>" data-order-base="<?= e(locale_url('/mf-dashboard/orders')) ?>" @keydown.escape.window="close()" @click.outside="close()" role="search">
                <form class="relative" action="<?= e($ordersUrl) ?>" method="get" @submit="submit($event)">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
                    <input name="q" type="text" x-model="q" @input="onInput()" autocomplete="off" class="w-full pl-10 pr-space-md py-2 bg-surface-container-low text-on-surface placeholder:text-on-surface-variant font-body-sm text-body-sm rounded-xl focus:outline-none focus:bg-surface-container-lowest focus:ring-1 focus:ring-primary transition-all" placeholder="<?= e($t['searchPh']) ?>"/>
                    <button type="submit" class="sr-only"><?= e($t['searchPh']) ?></button>
                </form>
                <div x-show="open" x-cloak x-transition.origin.top.left class="absolute left-0 right-0 top-full mt-2 z-50 rounded-xl bg-surface-container-lowest shadow-xl border border-surface-container overflow-hidden">
                    <div x-show="loading" class="px-4 py-3 font-body-sm text-body-sm text-on-surface-variant"><?= e($t['searching']) ?></div>
                    <template x-for="r in results" :key="r.id">
                        <a :href="orderUrl(r.id)" class="flex items-center gap-3 px-4 py-2.5 hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined text-on-surface-variant text-[18px] shrink-0">receipt_long</span>
                            <div class="flex flex-col min-w-0 flex-1">
                                <span class="font-label-md text-label-md text-on-surface font-semibold truncate" x-text="r.order_number"></span>
                                <span class="font-body-sm text-body-sm text-on-surface-variant truncate"><span x-text="r.shipping_name"></span><template x-if="r.shipping_phone"><span> · <span x-text="r.shipping_phone"></span></span></template></span>
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0">
                                <span class="font-label-sm text-label-sm text-on-surface font-bold" x-text="r.total_label"></span>
                                <span class="px-2 py-0.5 rounded-full font-label-sm text-label-sm font-semibold" :class="r.status_badge" x-text="r.status_label"></span>
                            </div>
                        </a>
                    </template>
                    <div x-show="!loading && results.length === 0" class="px-4 py-3 font-body-sm text-body-sm text-on-surface-variant"><?= e($t['noResults']) ?></div>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 md:gap-space-md shrink-0">
            <div class="relative hidden xl:block" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button type="button" @click="open = !open" class="flex items-center gap-space-xs px-space-md py-2 bg-surface-container-low rounded-xl cursor-pointer hover:bg-surface-container-high transition-colors" aria-haspopup="menu" :aria-expanded="open">
                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">calendar_today</span>
                    <span class="font-label-md text-label-md text-on-surface font-medium"><?= e($reportPeriodChip) ?></span>
                    <span class="material-symbols-outlined text-on-surface-variant text-[16px] transition-transform" :class="open ? 'rotate-180' : ''">keyboard_arrow_down</span>
                </button>
                <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 top-full mt-2 w-60 z-50 rounded-xl bg-surface-container-lowest shadow-xl border border-surface-container overflow-hidden py-1.5">
                    <?php foreach ($reportPeriodOptions as $rp => $rpLabel): ?>
                    <a href="<?= e(locale_url('/mf-dashboard') . '?period=' . $rp . '#report-chart') ?>"
                       class="flex items-center justify-between gap-2 px-4 py-2.5 font-label-md text-label-md transition-colors hover:bg-surface-container-low <?= $reportPeriodActive === $rp ? 'text-primary font-semibold' : 'text-on-surface-variant' ?>">
                        <span><?= e($rpLabel) ?></span>
                        <?php if ($reportPeriodActive === $rp): ?><span class="material-symbols-outlined text-[16px] text-primary">check</span><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a class="flex items-center gap-space-xs px-space-md py-2 bg-primary text-on-primary rounded-xl font-label-md text-label-md font-semibold hover:bg-primary-container transition-colors shadow-sm" href="<?= e($newProductUrl) ?>">
                <span class="material-symbols-outlined text-[18px]">add</span><span class="hidden sm:inline"><?= e($t['addProduct']) ?></span>
            </a>
            <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button type="button" @click="open = !open" class="relative w-10 h-10 rounded-xl bg-surface-container-low hover:bg-surface-container-high flex items-center justify-center transition-colors" aria-label="<?= e($t['notifTitle']) ?>" :aria-expanded="open">
                    <span class="material-symbols-outlined text-on-surface-variant text-[20px]">notifications</span>
                    <?php if ($notifTotal > 0): ?>
                    <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-secondary text-on-secondary font-label-sm text-label-sm flex items-center justify-center font-bold"><?= e($fmtCount($notifTotal)) ?></span>
                    <?php endif; ?>
                </button>
                <div x-show="open" x-cloak x-transition.origin.top.right class="absolute right-0 top-full mt-2 w-80 z-50 rounded-xl bg-surface-container-lowest shadow-xl border border-surface-container overflow-hidden">
                    <div class="px-4 py-3 border-b border-surface-container">
                        <span class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($t['notifTitle']) ?></span>
                    </div>
                    <div class="py-1.5 max-h-96 overflow-y-auto">
                        <?php if ($notifTotal > 0): ?>
                        <a href="<?= e($ordersUrl) ?>" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low transition-colors <?= $pendingCount < 1 ? 'hidden' : '' ?>">
                            <span class="material-symbols-outlined text-secondary text-[20px] shrink-0">local_mall</span>
                            <span class="flex-1 font-body-sm text-body-sm text-on-surface font-semibold"><?= e(str_replace('{n}', $fmtCount($pendingCount), $t['notifPending'])) ?></span>
                            <span class="material-symbols-outlined text-on-surface-variant text-[16px] shrink-0">chevron_right</span>
                        </a>
                        <a href="<?= e($productsUrl) ?>" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low transition-colors <?= $lowStockCount < 1 ? 'hidden' : '' ?>">
                            <span class="material-symbols-outlined text-secondary text-[20px] shrink-0">inventory_2</span>
                            <span class="flex-1 font-body-sm text-body-sm text-on-surface font-semibold"><?= e(str_replace('{n}', $fmtCount($lowStockCount), $t['notifLowStock'])) ?></span>
                            <span class="material-symbols-outlined text-on-surface-variant text-[16px] shrink-0">chevron_right</span>
                        </a>
                        <a href="<?= e($ordersUrl) ?>" class="flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low transition-colors <?= $todayOrders < 1 ? 'hidden' : '' ?>">
                            <span class="material-symbols-outlined text-secondary text-[20px] shrink-0">today</span>
                            <span class="flex-1 font-body-sm text-body-sm text-on-surface font-semibold"><?= e(str_replace('{n}', $fmtCount($todayOrders), $t['notifToday'])) ?></span>
                            <span class="material-symbols-outlined text-on-surface-variant text-[16px] shrink-0">chevron_right</span>
                        </a>
                        <?php else: ?>
                        <div class="px-4 py-8 flex flex-col items-center gap-1 text-center">
                            <span class="material-symbols-outlined text-[28px] text-on-surface-variant">notifications_off</span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($t['notifNone']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="hidden sm:block h-8 w-px bg-surface-container-high"></div>
            <div class="flex items-center gap-space-sm pl-space-xs">
                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center"><span class="material-symbols-outlined text-on-primary text-[18px]">person</span></div>
                <div class="flex flex-col text-left hidden sm:flex">
                    <span class="font-label-md text-label-md text-on-surface font-semibold leading-tight"><?= e($adminName) ?></span>
                    <span class="font-label-sm text-label-sm text-secondary font-medium"><?= e($t['role']) ?></span>
                </div>
            </div>
        </div>
    </header>
    <main class="relative pt-16 bg-surface min-h-screen w-full px-4 sm:px-6 md:px-space-xl py-space-xl">
        <?php include views_path('partials/wsit_flash.php'); ?>
        <?= $content ?>
    </main>
</div>
<style>[x-cloak]{display:none!important}</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('headerSearch', () => ({
        q: '',
        open: false,
        loading: false,
        results: [],
        _timer: null,
        onInput() {
            const q = (this.q || '').trim();
            clearTimeout(this._timer);
            if (q.length < 2) { this.results = []; this.loading = false; this.open = false; return; }
            this.open = true;
            this.loading = true;
            this._timer = setTimeout(() => this.fetchResults(q), 300);
        },
        async fetchResults(q) {
            try {
                const res   = await fetch(this.$root.dataset.endpoint + '?q=' + encodeURIComponent(q));
                const data  = await res.json();
                if (q === (this.q || '').trim()) { this.results = data.results || []; }
            } catch (e) {
                if (q === (this.q || '').trim()) { this.results = []; }
            } finally {
                if (q === (this.q || '').trim()) { this.loading = false; }
            }
        },
        close() { this.open = false; this.loading = false; },
        submit($event) {
            if ((this.q || '').trim() === '') { $event.preventDefault(); }
        },
        orderUrl(id) { return this.$root.dataset.orderBase + '/' + id; },
    }));
});
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?php include views_path('partials/wsit_icons.php'); ?>
<?php include views_path('admin/partials/wsit_translate_assist.php'); ?>

</body>
</html>
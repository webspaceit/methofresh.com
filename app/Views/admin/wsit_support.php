<?php
// Help & Support — Material admin (locale-aware via trans()).
$contactEmail = (string)($contactEmail ?? 'support@methofresh.com');
$phone        = (string)($phone ?? '');
$address      = (string)($address ?? '');
$currentLang  = app_locale() === 'bn' ? 'বাংলা' : 'English';

$quickLinks = [
    [locale_url('/mf-dashboard/orders'),        'local_mall',        trans('admin_orders')],
    [locale_url('/mf-dashboard/products'),      'inventory_2',       trans('admin_products')],
    [locale_url('/mf-dashboard/homepage'),      'home',              trans('admin_homepage')],
    [locale_url('/mf-dashboard/settings'),      'tune',              trans('admin_settings')],
    [locale_url('/mf-dashboard/payment-methods'), 'payments',        trans('payment_method')],
    [locale_url('/'),                           'language',          trans('admin_view_website')],
];

$faqs = [
    ['admin_faq1_q', 'admin_faq1_a', 'help_center'],
    ['admin_faq2_q', 'admin_faq2_a', 'update'],
    ['admin_faq3_q', 'admin_faq3_a', 'add_box'],
    ['admin_faq4_q', 'admin_faq4_a', 'translate'],
];

$contacts = [
    ['mail',   trans('admin_support_email_lbl'),   $contactEmail, 'mailto:' . e($contactEmail)],
    ['call',   trans('admin_support_phone_lbl'),   $phone,        $phone !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : '#'],
    ['map',    trans('admin_support_address_lbl'), $address,      ''],
];
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-fixed">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-primary-fixed/40 flex items-center justify-center text-primary shrink-0">
            <span class="material-symbols-outlined text-[26px]">support_agent</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_support')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_support_sub')) ?></p>
        </div>
    </div>
</section>

<!-- Contacts + quick links -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <div class="lg:col-span-7 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center gap-3 pb-space-sm border-b border-surface-container">
            <div class="w-10 h-10 rounded-xl bg-primary-fixed/40 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-[22px]">contact_support</span>
            </div>
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_support_contacts')) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_support_contacts_desc')) ?></p>
            </div>
        </div>
        <div class="flex flex-col divide-y divide-surface-container mt-space-sm">
            <?php foreach ($contacts as [$icon, $label, $value, $href]): ?>
            <div class="flex items-center gap-4 py-4">
                <div class="w-11 h-11 rounded-full bg-surface-container flex items-center justify-center text-primary shrink-0">
                    <span class="material-symbols-outlined text-[22px]"><?= e($icon) ?></span>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($label) ?></span>
                    <?php if ($href !== '' && $href !== '#'): ?>
                    <a href="<?= e($href) ?>" class="font-label-lg text-label-lg text-primary font-bold truncate hover:underline"><?= e($value) ?></a>
                    <?php else: ?>
                    <span class="font-label-lg text-label-lg text-on-surface font-semibold truncate"><?= e($value) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($href !== '' && $href !== '#'): ?>
                <span class="ml-auto material-symbols-outlined text-on-surface-variant text-[18px]">open_in_new</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-2 flex items-center gap-2 px-4 py-3 rounded-xl bg-surface-container font-body-sm text-body-sm text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px] text-primary">schedule</span>
            <span><?= e(trans('admin_support_hours')) ?></span>
        </div>
    </div>

    <div class="lg:col-span-5 flex flex-col gap-space-lg">
        <div class="flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
            <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_support_quick')) ?></h2>
                <span class="material-symbols-outlined text-primary text-[22px]">bolt</span>
            </div>
            <div class="flex flex-col mt-space-sm">
                <?php foreach ($quickLinks as [$url, $icon, $label]): ?>
                <a href="<?= e($url) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-surface-container-low transition-colors group">
                    <span class="w-9 h-9 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0 group-hover:bg-primary-fixed/40">
                        <span class="material-symbols-outlined text-[20px]"><?= e($icon) ?></span>
                    </span>
                    <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($label) ?></span>
                    <span class="ml-auto material-symbols-outlined text-[16px] text-on-surface-variant group-hover:text-primary">arrow_forward</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex items-center gap-3 p-space-md rounded-2xl bg-primary-fixed/20 border border-primary-fixed">
            <span class="material-symbols-outlined text-primary text-[24px]">language</span>
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_language_preference')) ?></span>
                <span class="font-label-lg text-label-lg text-on-surface font-bold"><?= e($currentLang) ?></span>
            </div>
            <a class="ml-auto inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-bold hover:bg-primary-fixed hover:text-primary transition-colors" href="<?= e(locale_url('/mf-dashboard/settings')) ?>">
                <span class="material-symbols-outlined text-[18px]">tune</span>
                <?= e(trans('admin_settings')) ?>
            </a>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="flex items-center gap-3 pb-space-sm border-b border-surface-container">
        <div class="w-10 h-10 rounded-xl bg-secondary-fixed/50 text-secondary flex items-center justify-center">
            <span class="material-symbols-outlined text-[22px]">quiz</span>
        </div>
        <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_support_faq')) ?></h2>
            <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_support_faq_desc')) ?></p>
        </div>
    </div>
    <div class="flex flex-col gap-3 mt-space-sm">
        <?php foreach ($faqs as [$qKey, $aKey, $icon]): ?>
        <details class="group rounded-xl bg-surface-container-low open:bg-surface-container-lowest border border-surface-container open:border-primary-container transition-colors">
            <summary class="flex items-center gap-3 cursor-pointer list-none px-5 py-4 select-none">
                <span class="w-8 h-8 rounded-lg bg-primary-fixed/40 text-primary flex items-center justify-center shrink-0 transition-transform group-open:rotate-90">
                    <span class="material-symbols-outlined text-[19px]"><?= e($icon) ?></span>
                </span>
                <span class="font-label-lg text-label-lg text-on-surface font-bold flex-1"><?= e(trans($qKey)) ?></span>
                <span class="material-symbols-outlined text-on-surface-variant text-[20px] transition-transform group-open:rotate-180">expand_more</span>
            </summary>
            <div class="px-5 pb-5 pt-1 pl-16 font-body-md text-body-md text-on-surface-variant">
                <?= e(trans($aKey)) ?>
            </div>
        </details>
        <?php endforeach; ?>
    </div>
</section>
</div>
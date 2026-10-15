<?php
$locale = app_locale();

// Only allow safe CSS colour tokens from the admin; otherwise fall back.
$ftColor = static function (string $val, string $fallback): string {
    $val = trim($val);
    if ($val !== '' && (
        preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)
        || preg_match('/^rgba?\([0-9.,%\s]+\)$/', $val)
        || preg_match('/^[a-zA-Z]{3,20}$/', $val)
    )) {
        return $val;
    }
    return $fallback;
};
$ftNum = static function (string $val, int $min, int $max, int $fallback): int {
    $val = trim($val);
    if ($val === '' || !is_numeric($val)) {
        return $fallback;
    }
    return max($min, min($max, (int) $val));
};

// --- Content -------------------------------------------------------------
$footerPages   = \App\Models\wsit_Page::activeMenu();
$brandName     = site_setting('footer_brand_' . $locale) ?: trans('site_name');
$footerAbout   = site_setting('footer_about_' . $locale) ?: site_setting('footer_about_en');
$footerEmail   = site_setting('footer_email', 'support@methofresh.com');
$footerPhone   = site_setting('footer_phone');
$footerAddress = site_setting('footer_address_' . $locale) ?: site_setting('footer_address_en');

$headingLinks    = site_setting('footer_heading_links_' . $locale)    ?: trans('footer_quick_links');
$headingContact  = site_setting('footer_heading_contact_' . $locale)  ?: trans('footer_contact');
$headingDelivery = site_setting('footer_heading_delivery_' . $locale) ?: trans('footer_delivery');

$deliveryText  = site_setting('footer_delivery_text_' . $locale) ?: trans('footer_delivery_text');
$paymentsLabel = site_setting('footer_payments_label_' . $locale) ?: trans('footer_payments');
$paymentsText  = site_setting('footer_payments_text_' . $locale)  ?: trans('footer_payments_text');

$copyright = site_setting('copyright_' . $locale) ?: site_setting('copyright_en');
$copyright = str_replace(
    ['{year}', '{site_name}', '{rights}'],
    [format_number(date('Y')), trans('site_name'), trans('footer_rights')],
    $copyright
);
if ($copyright === '') {
    $copyright = '© ' . format_number(date('Y')) . ' ' . trans('site_name') . '. ' . trans('footer_rights');
}

// --- Visibility ----------------------------------------------------------
$showBrand     = site_setting('footer_show_brand', '1') !== '0';
$showLinks     = site_setting('footer_show_links', '1') !== '0';
$showContact   = site_setting('footer_show_contact', '1') !== '0';
$showDelivery  = site_setting('footer_show_delivery', '1') !== '0';
$showSocial    = site_setting('footer_show_social', '1') !== '0';
$showPagesBar  = site_setting('footer_show_pages_bar', '1') !== '0';

// --- Appearance ----------------------------------------------------------
$ftVars = sprintf(
    '--ft-bg:%s;--ft-heading:%s;--ft-text:%s;--ft-link:%s;--ft-hover:%s;--ft-border:%s;--ft-fs:%dpx;--ft-hs:%dpx;--ft-py:%dpx;',
    $ftColor(site_setting('footer_bg_color'), '#ffffff'),
    $ftColor(site_setting('footer_heading_color'), '#1c1917'),
    $ftColor(site_setting('footer_text_color'), '#57534e'),
    $ftColor(site_setting('footer_link_color'), '#44403c'),
    $ftColor(site_setting('footer_link_hover_color'), '#b45309'),
    $ftColor(site_setting('footer_border_color'), '#e7e5e4'),
    $ftNum(site_setting('footer_font_size'), 11, 22, 14),
    $ftNum(site_setting('footer_heading_size'), 11, 26, 15),
    $ftNum(site_setting('footer_padding'), 12, 72, 28)
);

$socials = [];
if ($showSocial) {
    foreach (footer_social_links() as $social) {
        if (!empty($social['enabled']) && trim((string) ($social['url'] ?? '')) !== '') {
            $socials[] = $social;
        }
    }
}

$socialSvg = static function (string $platform): string {
    $paths = [
        'facebook'  => '<path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.4h-1.2c-1.2 0-1.6.8-1.6 1.5V12h2.7l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/>',
        'instagram' => '<path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.8s0 3.5-.1 4.8c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2-.1-1.3-.1-1.7-.1-4.8s0-3.5.1-4.8c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C9 2.2 9.4 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.2.7-.3.3-.6.7-.7 1.2-.2.4-.3 1-.4 2.1-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.4.9.7 1.2.3.3.7.6 1.2.7.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.2-.7.3-.3.6-.7.7-1.2.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.7-1.2-.3-.3-.7-.6-1.2-.7-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1zm0 3.1a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8zm0 8.1a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4zm6.3-8.3a1.15 1.15 0 1 1-2.3 0 1.15 1.15 0 0 1 2.3 0z"/>',
        'youtube'   => '<path d="M23 12s0-3.2-.4-4.7c-.2-.9-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.8-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.9.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.8 1.7-1.7.4-1.5.4-4.7.4-4.7zM9.8 15.3V8.7l6 3.3-6 3.3z"/>',
        'twitter'   => '<path d="M18.9 2H22l-7.3 8.3L23.3 22h-6.8l-5.3-6.9L5 22H1.9l7.8-8.9L1 2h7l4.8 6.3L18.9 2zm-1.2 18h1.9L6.1 3.9H4.1L17.7 20z"/>',
        'x'         => '<path d="M18.9 2H22l-7.3 8.3L23.3 22h-6.8l-5.3-6.9L5 22H1.9l7.8-8.9L1 2h7l4.8 6.3L18.9 2zm-1.2 18h1.9L6.1 3.9H4.1L17.7 20z"/>',
        'whatsapp'  => '<path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.1-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-1.7-.1-1.8-.6-3.6-2.3-4.4-4-.3-.6-.5-1.3-.5-1.9 0-.7.3-1.3.7-1.7.2-.2.4-.2.5-.2h.5c.2 0 .4 0 .6.4l.7 1.6c.1.2 0 .4-.1.5l-.4.5c-.1.1-.2.3-.1.5.4.7 1 1.3 1.7 1.7.2.1.4.1.5 0l.5-.5c.2-.2.3-.2.5-.1l1.6.8c.3.1.4.2.4.4 0 .2 0 .6-.2 1z"/>',
        'tiktok'    => '<path d="M16.5 2c.3 2.1 1.6 3.6 3.7 3.9v2.5c-1.3.1-2.5-.3-3.7-1v6.3c0 3.6-2.6 6.3-6 6.3-3.2 0-5.7-2.4-5.7-5.6 0-3.4 2.9-6 6.6-5.4v2.6c-.4-.1-.8-.2-1.2-.2-1.7 0-3 1.3-3 3s1.3 3 3 3 3.1-1.3 3.1-3V2h3.2z"/>',
        'linkedin'  => '<path d="M20.4 3H3.6C3 3 2.5 3.5 2.5 4.1v15.8c0 .6.5 1.1 1.1 1.1h16.8c.6 0 1.1-.5 1.1-1.1V4.1c0-.6-.5-1.1-1.1-1.1zM8.3 18.3H5.6V9.8h2.7v8.5zM6.9 8.6a1.6 1.6 0 1 1 0-3.2 1.6 1.6 0 0 1 0 3.2zm11.5 9.7h-2.7v-4.1c0-1 0-2.3-1.4-2.3s-1.6 1.1-1.6 2.2v4.2h-2.7V9.8h2.6v1.2h.1c.4-.7 1.2-1.4 2.5-1.4 2.7 0 3.2 1.8 3.2 4.1v4.6z"/>',
    ];
    return $paths[strtolower(trim($platform))] ?? '';
};

$ftIcon = static function (string $name): string {
    $paths = [
        'phone' => '<path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.33.57 3.57.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1C10.61 21 3 13.39 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1.02l-2.2 2.2z"/>',
        'mail'  => '<path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm9 8.2L4.2 7h15.6L12 13.2zM4 8.6V17h16V8.6l-7.4 5.1a1 1 0 0 1-1.2 0L4 8.6z"/>',
        'pin'   => '<path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>',
    ];
    return $paths[$name] ?? '';
};

$quickLinks = array_values(array_filter(footer_quick_links(), static fn($l) => !empty($l['enabled'])));
?>
<!-- BEGIN: MainFooter -->
<footer class="site-footer" data-purpose="site-footer" style="<?= e($ftVars) ?>">
    <style>
        .site-footer { background: var(--ft-bg); color: var(--ft-text); font-size: var(--ft-fs); border-top: 1px solid var(--ft-border); }
        .site-footer .ft-inner { padding-top: var(--ft-py); padding-bottom: var(--ft-py); }
        .site-footer .ft-heading { color: var(--ft-heading); font-size: var(--ft-hs); font-weight: 700; letter-spacing: .01em; }
        .site-footer a { color: var(--ft-link); text-decoration: none; transition: color .2s ease; }
        .site-footer a:hover { color: var(--ft-hover); }
        .site-footer .ft-muted { opacity: .78; }
        .site-footer .ft-grid { display: grid; gap: 2.25rem; grid-template-columns: 1fr; }
        @media (min-width: 768px) { .site-footer .ft-grid { grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); } }
        .site-footer .ft-list { list-style: none; margin: 0; padding: 0; }
        .site-footer .ft-list li + li { margin-top: .5rem; }
        .site-footer .ft-list .ft-link-row { transition: transform .2s ease; }
        .site-footer .ft-list .ft-link-row:hover { transform: translateX(3px); }
        .site-footer .ft-icon { width: 1rem; height: 1rem; flex: none; opacity: .85; }
        .site-footer .ft-social a { display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 9999px; border: 1px solid var(--ft-border); color: var(--ft-link); }
        .site-footer .ft-social a:hover { background: var(--ft-hover); border-color: var(--ft-hover); color: #ffffff; }
        .site-footer .ft-pay { border-top: 1px solid var(--ft-border); margin-top: .85rem; padding-top: .85rem; }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 ft-inner">
        <div class="ft-grid">

            <?php if ($showBrand): ?>
            <!-- Brand -->
            <div>
                <div class="flex items-center gap-2.5">
                    <?php $logo = site_logo_url(); ?>
                    <?php if ($logo !== ''): ?>
                        <img src="<?= e($logo) ?>" alt="<?= e($brandName) ?>" style="<?= e(site_logo_style()) ?>" class="object-contain max-w-[150px]">
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm" style="background:var(--ft-heading);color:var(--ft-bg)"><?= e(mb_substr($brandName, 0, 1)) ?></div>
                    <?php endif; ?>
                    <span class="text-lg font-bold" style="color:var(--ft-heading)"><?= e($brandName) ?></span>
                </div>
                <?php if ($footerAbout !== ''): ?>
                    <p class="ft-muted mt-3 leading-relaxed"><?= e($footerAbout) ?></p>
                <?php endif; ?>
                <?php if ($socials): ?>
                    <div class="ft-social mt-4 flex flex-wrap gap-2.5">
                        <?php foreach ($socials as $social): ?>
                            <?php $platform = (string) ($social['platform'] ?? ''); ?>
                            <?php $svg = $socialSvg($platform); ?>
                            <?php if ($svg === '') continue; ?>
                            <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e(ucfirst($platform)) ?>" title="<?= e(ucfirst($platform)) ?>">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $svg ?></svg>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($showLinks): ?>
            <!-- Important Links -->
            <div>
                <h4 class="ft-heading mb-3"><?= e($headingLinks) ?></h4>
                <ul class="ft-list">
                    <?php foreach ($quickLinks as $link): ?>
                        <?php $label = trim((string) ($link['label_' . $locale] ?? '')) ?: trim((string) ($link['label_en'] ?? '')); ?>
                        <?php $url = trim((string) ($link['url'] ?? '')); ?>
                        <?php if ($url === '' || $label === '') continue; ?>
                        <li class="ft-link-row"><a href="<?= e(locale_url($url)) ?>"><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($showContact): ?>
            <!-- Contact -->
            <div>
                <h4 class="ft-heading mb-3"><?= e($headingContact) ?></h4>
                <ul class="ft-list">
                    <?php if ($footerPhone !== ''): ?>
                        <li class="flex items-center gap-2">
                            <svg class="ft-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $ftIcon('phone') ?></svg>
                            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $footerPhone)) ?>"><?= e($footerPhone) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ($footerEmail !== ''): ?>
                        <li class="flex items-center gap-2">
                            <svg class="ft-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $ftIcon('mail') ?></svg>
                            <a href="mailto:<?= e($footerEmail) ?>"><?= e($footerEmail) ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if ($footerAddress !== ''): ?>
                        <li class="flex items-start gap-2">
                            <svg class="ft-icon mt-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?= $ftIcon('pin') ?></svg>
                            <span class="ft-muted"><?= nl2br(e($footerAddress)) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($showDelivery): ?>
            <!-- Delivery & Payment -->
            <div>
                <h4 class="ft-heading mb-3"><?= e($headingDelivery) ?></h4>
                <?php if ($deliveryText !== ''): ?>
                    <p class="ft-muted leading-relaxed"><?= nl2br(e($deliveryText)) ?></p>
                <?php endif; ?>
                <?php if ($paymentsLabel !== '' || $paymentsText !== ''): ?>
                    <div class="ft-pay">
                        <?php if ($paymentsLabel !== ''): ?>
                            <span class="block font-semibold mb-1"><?= e($paymentsLabel) ?></span>
                        <?php endif; ?>
                        <?php if ($paymentsText !== ''): ?>
                            <span class="ft-muted"><?= e($paymentsText) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($showPagesBar || $copyright !== ''): ?>
        <!-- Copyright -->
        <div class="mt-9 pt-5 flex flex-col sm:flex-row justify-between items-center gap-3" style="border-top:1px solid var(--ft-border)">
            <p class="ft-muted"><?= e($copyright) ?></p>
            <?php if ($showPagesBar && $footerPages): ?>
            <div class="flex flex-wrap gap-4">
                <?php foreach ($footerPages as $page): ?>
                    <a href="<?= e(locale_url('/pages/' . $page['slug'])) ?>"><?= e($page['title']) ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</footer>
<!-- END: MainFooter -->

<!-- Floating back-to-top (inline SVG, no webfont) -->
<button type="button"
        x-data="{ show: false }"
        x-init="show = window.scrollY > 400"
        @scroll.window="show = window.scrollY > 400"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        x-show="show"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        aria-label="<?= e(trans('back_to_top')) ?>"
        title="<?= e(trans('back_to_top')) ?>"
        class="fixed bottom-6 right-5 z-40 w-11 h-11 rounded-full bg-brand-700 text-amber-400 shadow-lg hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-amber-400/70 flex items-center justify-center">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
    </svg>
</button>

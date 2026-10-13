<?php
$locale = app_locale();
$footerPages = \App\Models\wsit_Page::activeMenu();
$footerAbout = site_setting('footer_about_' . $locale);
$footerEmail = site_setting('footer_email');
$footerPhone = site_setting('footer_phone');
$footerAddress = site_setting('footer_address_' . $locale);
$copyright = site_setting('copyright_' . $locale);
$copyright = str_replace(
    ['{year}', '{site_name}', '{rights}'],
    [format_number(date('Y')), trans('site_name'), trans('footer_rights')],
    $copyright
);
if ($copyright === '') {
    $copyright = '© ' . format_number(date('Y')) . ' ' . trans('site_name') . '. ' . trans('footer_rights');
}
?>
<!-- BEGIN: MainFooter -->
<footer class="bg-white border-t border-stone-200 text-stone-700 text-sm" data-purpose="site-footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-7">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

            <!-- Brand Info -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <?php $logo = site_logo_url(); ?>
                    <?php if ($logo !== ''): ?>
                        <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>"
                             style="<?= e(site_logo_style()) ?>"
                             class="object-contain max-w-[160px]">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-brand-700 flex items-center justify-center text-amber-400 font-bold text-sm"><?= e(mb_substr(trans('site_name'), 0, 1)) ?></div>
                    <?php endif; ?>
                    <span class="text-lg font-bold text-brand-800"><?= e(trans('site_name')) ?></span>
                </div>
                <?php if ($footerAbout !== ''): ?>
                    <p class="text-xs text-stone-500 leading-relaxed"><?= e($footerAbout) ?></p>
                <?php endif; ?>
            </div>

            <!-- Important Links -->
            <div>
                <h4 class="font-bold text-stone-900 mb-3 text-sm"><?= e(trans('footer_quick_links')) ?></h4>
                <ul class="space-y-2 text-xs text-stone-600">
                    <?php $quickLinks = array_values(array_filter(footer_quick_links(), static fn($l) => !empty($l['enabled']))); ?>
                    <?php foreach ($quickLinks as $link): ?>
                    <?php $label = trim((string)($link['label_' . $locale] ?? '')) ?: trim((string)($link['label_en'] ?? '')); ?>
                    <?php $url = trim((string)($link['url'] ?? '')); ?>
                    <?php if ($url === '' || $label === '') continue; ?>
                    <li><a href="<?= e(locale_url($url)) ?>" class="hover:text-brand-700 transition"><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="font-bold text-stone-900 mb-3 text-sm"><?= e(trans('footer_contact')) ?></h4>
                <ul class="space-y-2 text-xs text-stone-600">
                    <?php if ($footerPhone !== ''): ?>
                    <li class="flex items-center gap-2">
                        <span>📞</span>
                        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $footerPhone)) ?>" class="hover:text-brand-700 transition"><?= e($footerPhone) ?></a>
                    </li>
                    <?php endif; ?>
                    <?php if ($footerEmail !== ''): ?>
                    <li class="flex items-center gap-2">
                        <span>✉️</span>
                        <a href="mailto:<?= e($footerEmail) ?>" class="hover:text-brand-700 transition"><?= e($footerEmail) ?></a>
                    </li>
                    <?php endif; ?>
                    <?php if ($footerAddress !== ''): ?>
                    <li class="flex items-center gap-2">
                        <span>📍</span>
                        <span><?= e($footerAddress) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Delivery & Payment -->
            <div>
                <h4 class="font-bold text-stone-900 mb-3 text-sm"><?= e(trans('footer_delivery')) ?></h4>
                <p class="text-xs text-stone-500 leading-relaxed"><?= nl2br(e(trans('footer_delivery_text'))) ?></p>
                <div class="mt-3 pt-3 border-t border-stone-200">
                    <span class="text-[11px] font-semibold text-stone-600 block mb-1"><?= e(trans('footer_payments')) ?></span>
                    <span class="text-xs text-stone-500"><?= e(trans('footer_payments_text')) ?></span>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="mt-10 pt-2 border-t border-stone-100 flex flex-col sm:flex-row justify-between items-center text-xs text-stone-400 gap-2">
            <p><?= e($copyright) ?></p>
            <div class="flex gap-4">
                <?php foreach ($footerPages as $page): ?>
                <a href="<?= e(locale_url('/pages/' . $page['slug'])) ?>" class="hover:text-brand-700 transition"><?= e($page['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
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
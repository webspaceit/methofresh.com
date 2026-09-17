<?php
$locale = app_locale();
$categoriesForFooter = app()->get('footer_categories') ?? \App\Models\wsit_Category::allStatic();
$footerPages = \App\Models\wsit_Page::activeMenu();
$footerAbout = site_setting('footer_about_' . $locale);
$footerEmail = site_setting('footer_email');
$footerPhone = site_setting('footer_phone');
$footerAddress = site_setting('footer_address_' . $locale);
$copyright = site_setting('copyright_' . $locale);
$copyright = str_replace(
    ['{year}', '{site_name}', '{rights}'],
    [date('Y'), trans('site_name'), trans('footer_rights')],
    $copyright
);
if ($copyright === '') {
    $copyright = '© ' . date('Y') . ' ' . trans('site_name') . '. ' . trans('footer_rights');
}
?>
<footer class="bg-stone-900 text-stone-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 py-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

        <div>
            <div class="flex items-center gap-2 mb-4">
                <?php $logo = site_logo_url(); ?>
                <?php if ($logo !== ''): ?>
                    <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>" class="h-9 w-auto max-w-[150px] object-contain bg-white rounded-lg p-1">
                <?php else: ?>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white text-lg font-black"><?= e(mb_substr(trans('site_name'), 0, 1)) ?></span>
                <?php endif; ?>
                <span class="text-lg font-extrabold text-white"><?= e(trans('site_name')) ?></span>
            </div>
            <?php if ($footerAbout !== ''): ?>
                <p class="text-sm leading-relaxed mb-4"><?= e($footerAbout) ?></p>
            <?php endif; ?>
            <ul class="space-y-2 text-xs">
                <?php if ($footerEmail !== ''): ?>
                <li>✉️ <a href="mailto:<?= e($footerEmail) ?>" class="hover:text-white"><?= e($footerEmail) ?></a></li>
                <?php endif; ?>
                <?php if ($footerPhone !== ''): ?>
                <li>📞 <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $footerPhone)) ?>" class="hover:text-white"><?= e($footerPhone) ?></a></li>
                <?php endif; ?>
                <?php if ($footerAddress !== ''): ?>
                <li>📍 <?= e($footerAddress) ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div>
            <h3 class="text-white font-semibold mb-4"><?= e(trans('footer_quick_links')) ?></h3>
            <ul class="space-y-2 text-sm">
                <?php $quickLinks = array_values(array_filter(footer_quick_links(), static fn($l) => !empty($l['enabled']))); ?>
                <?php foreach ($quickLinks as $link): ?>
                <?php $label = trim((string)($link['label_' . $locale] ?? '')) ?: trim((string)($link['label_en'] ?? '')); ?>
                <?php $url = trim((string)($link['url'] ?? '')); ?>
                <?php if ($url === '' || $label === '') continue; ?>
                <li><a href="<?= e(locale_url($url)) ?>" class="hover:text-white"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h3 class="text-white font-semibold mb-4"><?= e(trans('footer_categories')) ?></h3>
            <ul class="space-y-2 text-sm">
                <?php foreach (array_slice($categoriesForFooter, 0, 5) as $cat): ?>
                <li><a href="<?= e(locale_url('/categories/' . $cat['slug'])) ?>" class="hover:text-white"><?= e($cat['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h3 class="text-white font-semibold mb-4"><?= e(trans('footer_newsletter')) ?></h3>
            <p class="text-sm mb-4"><?= e(trans('footer_newsletter_text')) ?></p>
            <form method="post" action="<?= e(locale_url('/')) ?>" class="flex gap-2">
                <?= csrf_field() ?>
                <input type="email" name="newsletter_email" required placeholder="<?= e(trans('newsletter_placeholder')) ?>"
                       class="flex-1 min-w-0 rounded-lg bg-stone-800 border border-stone-700 px-3 py-2 text-sm text-white placeholder-stone-500 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <button type="submit" class="rounded-lg bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 text-sm font-semibold"><?= e(trans('newsletter_btn')) ?></button>
            </form>
        </div>
    </div>

    <div class="border-t border-stone-800">
        <div class="max-w-7xl mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-stone-500">
            <p><?= e($copyright) ?></p>
            <div class="flex gap-4">
                <?php foreach ($footerPages as $page): ?>
                <a href="<?= e(locale_url('/pages/' . $page['slug'])) ?>" class="hover:text-stone-300"><?= e($page['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</footer>
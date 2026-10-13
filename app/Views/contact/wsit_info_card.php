<?php
// Contact info card — shared by /contact and the "contact" CMS page.
// Reads the real address/phone/email from Settings → Footer.
$locale = app_locale();
$infoEmail = site_setting('footer_email', 'support@methofresh.com');
$infoPhone = site_setting('footer_phone');
$infoAddress = site_setting('footer_address_' . $locale);
?>
<div class="bg-card rounded-2xl border border-honey-600/15 shadow-sm p-6 sm:p-8">
    <h2 class="text-lg font-bold text-brand-800 mb-3"><?= e(trans('contact_info')) ?></h2>
    <div class="space-y-3 text-sm text-stone-600">
        <?php if ($infoEmail !== ''): ?>
        <p class="flex items-start gap-2">
            <span aria-hidden="true">✉️</span>
            <a href="mailto:<?= e($infoEmail) ?>" class="text-brand-600 hover:underline break-all"><?= e($infoEmail) ?></a>
        </p>
        <?php endif; ?>
        <?php if ($infoPhone !== ''): ?>
        <p class="flex items-start gap-2">
            <span aria-hidden="true">📞</span>
            <span><?= e($infoPhone) ?></span>
        </p>
        <?php endif; ?>
        <?php if ($infoAddress !== ''): ?>
        <p class="flex items-start gap-2">
            <span aria-hidden="true">🏢</span>
            <span><?= e($infoAddress) ?></span>
        </p>
        <?php endif; ?>
        <?php if ($infoEmail === '' && $infoPhone === '' && $infoAddress === ''): ?>
        <p class="text-stone-500"><?= e(trans('contact_info_empty')) ?></p>
        <?php endif; ?>
    </div>
</div>
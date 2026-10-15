<?php
$footer = [
    'brand_en'   => site_setting('footer_brand_en'),
    'brand_bn'   => site_setting('footer_brand_bn'),
    'about_en'   => site_setting('footer_about_en'),
    'about_bn'   => site_setting('footer_about_bn'),
    'email'      => site_setting('footer_email'),
    'phone'      => site_setting('footer_phone'),
    'address_en' => site_setting('footer_address_en'),
    'address_bn' => site_setting('footer_address_bn'),
    'copyright_en' => site_setting('copyright_en'),
    'copyright_bn' => site_setting('copyright_bn'),
    'heading_links_en'    => site_setting('footer_heading_links_en'),
    'heading_links_bn'    => site_setting('footer_heading_links_bn'),
    'heading_contact_en'  => site_setting('footer_heading_contact_en'),
    'heading_contact_bn'  => site_setting('footer_heading_contact_bn'),
    'heading_delivery_en' => site_setting('footer_heading_delivery_en'),
    'heading_delivery_bn' => site_setting('footer_heading_delivery_bn'),
    'delivery_text_en'    => site_setting('footer_delivery_text_en'),
    'delivery_text_bn'    => site_setting('footer_delivery_text_bn'),
    'payments_label_en'   => site_setting('footer_payments_label_en'),
    'payments_label_bn'   => site_setting('footer_payments_label_bn'),
    'payments_text_en'    => site_setting('footer_payments_text_en'),
    'payments_text_bn'    => site_setting('footer_payments_text_bn'),
];

// Colour fields: only a 6-digit hex can be shown in <input type="color">.
$colorDefaults = [
    'footer_bg_color'           => '#ffffff',
    'footer_heading_color'      => '#1c1917',
    'footer_text_color'         => '#57534e',
    'footer_link_color'         => '#44403c',
    'footer_link_hover_color'   => '#b45309',
    'footer_border_color'       => '#e7e5e4',
];
$colors = [];
foreach ($colorDefaults as $ck => $cd) {
    $cv = site_setting($ck);
    $colors[$ck] = preg_match('/^#[0-9a-fA-F]{6}$/', $cv) ? strtolower($cv) : $cd;
}

$numDefaults = ['footer_font_size' => 14, 'footer_heading_size' => 15, 'footer_padding' => 28];
$nums = [];
foreach ($numDefaults as $nk => $nd) {
    $nv = (int) site_setting($nk, (string) $nd);
    $nums[$nk] = $nv > 0 ? $nv : $nd;
}

$toggles = [
    'footer_show_brand'      => site_setting('footer_show_brand', '1'),
    'footer_show_links'      => site_setting('footer_show_links', '1'),
    'footer_show_contact'    => site_setting('footer_show_contact', '1'),
    'footer_show_delivery'   => site_setting('footer_show_delivery', '1'),
    'footer_show_social'     => site_setting('footer_show_social', '1'),
    'footer_show_pages_bar'  => site_setting('footer_show_pages_bar', '1'),
    'footer_show_brand_name' => site_setting('footer_show_brand_name', site_logo_url() !== '' ? '0' : '1'),
];

$platforms = ['facebook', 'instagram', 'youtube', 'twitter', 'x', 'whatsapp', 'tiktok', 'linkedin'];
$platformLabel = static function (string $p): string {
    return $p === 'x' ? 'X (Twitter)' : ucfirst($p);
};

$inputCls = 'w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none';
?>
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_footer')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_footer_subtitle')) ?></p>
        </div>
        <a href="<?= e(locale_url('/')) ?>" target="_blank" class="text-sm font-semibold text-brand-700 hover:underline"><?= e(trans('admin_view_website')) ?> →</a>
    </div>

    <form method="post" action="<?= e(locale_url('/mf-dashboard/footer')) ?>" class="bg-card rounded-2xl border border-honey-600/15 p-6 space-y-8">
        <?= csrf_field() ?>

        <!-- ============ Brand & About ============ -->
        <section>
            <h2 class="text-sm font-bold text-stone-800 mb-1"><?= e(trans('admin_footer_brand')) ?></h2>
            <p class="text-xs text-stone-400 mb-3"><?= e(trans('admin_footer_brand_hint')) ?></p>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_en')) ?></label>
                    <input type="text" name="footer_brand_en" value="<?= e($footer['brand_en']) ?>" placeholder="<?= e(trans('site_name')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                        <?php $source = '[name=footer_brand_en]'; $target = '[name=footer_brand_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="footer_brand_bn" value="<?= e($footer['brand_bn']) ?>" placeholder="<?= e(trans('site_name')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_about')) ?> (<?= e(trans('admin_lang_en')) ?>)</label>
                    <textarea name="footer_about_en" rows="3" class="<?= $inputCls ?> mt-1"><?= e($footer['about_en']) ?></textarea>
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_about')) ?> (<?= e(trans('admin_lang_bn')) ?>)</label>
                        <?php $source = '[name=footer_about_en]'; $target = '[name=footer_about_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <textarea name="footer_about_bn" rows="3" class="<?= $inputCls ?> mt-1"><?= e($footer['about_bn']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- ============ Column headings ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-3 mt-4"><?= e(trans('admin_footer_headings')) ?></h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_heading_links')) ?> (EN)</label>
                    <input type="text" name="footer_heading_links_en" value="<?= e($footer['heading_links_en']) ?>" placeholder="<?= e(trans('footer_quick_links')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_heading_links')) ?> (BN)</label>
                        <?php $source = '[name=footer_heading_links_en]'; $target = '[name=footer_heading_links_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="footer_heading_links_bn" value="<?= e($footer['heading_links_bn']) ?>" class="<?= $inputCls ?> mt-1">
                </div>

                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_heading_contact')) ?> (EN)</label>
                    <input type="text" name="footer_heading_contact_en" value="<?= e($footer['heading_contact_en']) ?>" placeholder="<?= e(trans('footer_contact')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_heading_contact')) ?> (BN)</label>
                        <?php $source = '[name=footer_heading_contact_en]'; $target = '[name=footer_heading_contact_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="footer_heading_contact_bn" value="<?= e($footer['heading_contact_bn']) ?>" class="<?= $inputCls ?> mt-1">
                </div>

                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_heading_delivery')) ?> (EN)</label>
                    <input type="text" name="footer_heading_delivery_en" value="<?= e($footer['heading_delivery_en']) ?>" placeholder="<?= e(trans('footer_delivery')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_heading_delivery')) ?> (BN)</label>
                        <?php $source = '[name=footer_heading_delivery_en]'; $target = '[name=footer_heading_delivery_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="footer_heading_delivery_bn" value="<?= e($footer['heading_delivery_bn']) ?>" class="<?= $inputCls ?> mt-1">
                </div>
            </div>
        </section>

        <!-- ============ Delivery & payment text ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-3 mt-4"><?= e(trans('admin_footer_delivery_section')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_delivery_text')) ?> (EN)</label>
                    <textarea name="footer_delivery_text_en" rows="3" class="<?= $inputCls ?> mt-1"><?= e($footer['delivery_text_en']) ?></textarea>
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_delivery_text')) ?> (BN)</label>
                        <?php $source = '[name=footer_delivery_text_en]'; $target = '[name=footer_delivery_text_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <textarea name="footer_delivery_text_bn" rows="3" class="<?= $inputCls ?> mt-1"><?= e($footer['delivery_text_bn']) ?></textarea>
                </div>

                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_payments_label')) ?> (EN)</label>
                    <input type="text" name="footer_payments_label_en" value="<?= e($footer['payments_label_en']) ?>" placeholder="<?= e(trans('footer_payments')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_payments_label')) ?> (BN)</label>
                        <?php $source = '[name=footer_payments_label_en]'; $target = '[name=footer_payments_label_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="footer_payments_label_bn" value="<?= e($footer['payments_label_bn']) ?>" class="<?= $inputCls ?> mt-1">
                </div>

                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_payments_text')) ?> (EN)</label>
                    <input type="text" name="footer_payments_text_en" value="<?= e($footer['payments_text_en']) ?>" placeholder="<?= e(trans('footer_payments_text')) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_payments_text')) ?> (BN)</label>
                        <?php $source = '[name=footer_payments_text_en]'; $target = '[name=footer_payments_text_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="footer_payments_text_bn" value="<?= e($footer['payments_text_bn']) ?>" class="<?= $inputCls ?> mt-1">
                </div>
            </div>
        </section>

        <!-- ============ Contact details ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-3 mt-4"><?= e(trans('admin_footer_contact')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_email')) ?></label>
                    <input type="text" name="footer_email" value="<?= e($footer['email']) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_phone')) ?></label>
                    <input type="text" name="footer_phone" value="<?= e($footer['phone']) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_en')) ?></label>
                    <textarea name="footer_address_en" rows="2" class="<?= $inputCls ?> mt-1"><?= e($footer['address_en']) ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                        <?php $source = '[name=footer_address_en]'; $target = '[name=footer_address_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <textarea name="footer_address_bn" rows="2" class="<?= $inputCls ?> mt-1"><?= e($footer['address_bn']) ?></textarea>
                </div>
            </div>
        </section>

        <!-- ============ Social links ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-1 mt-4"><?= e(trans('admin_footer_social')) ?></h2>
            <p class="text-xs text-stone-400 mb-3"><?= e(trans('admin_footer_social_desc')) ?></p>
            <div class="space-y-4" id="social-links">
                <?php foreach (footer_social_links() as $sk => $social): $sNum = (int) $sk + 1; ?>
                <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
                        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_ql_link')) ?> <?= $sNum ?></span>
                        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                            <input type="checkbox" name="social_links[<?= $sk ?>][enabled]" value="1" class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= !empty($social['enabled']) ? 'checked' : '' ?>>
                            <?= e(trans('admin_social_show')) ?>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
                            <input type="checkbox" name="social_links_remove[<?= $sk ?>]" value="1" class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
                            <?= e(trans('admin_social_remove')) ?>
                        </label>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_social_platform')) ?></label>
                            <select name="social_links[<?= $sk ?>][platform]" class="<?= $inputCls ?>">
                                <?php foreach ($platforms as $p): ?>
                                    <option value="<?= e($p) ?>" <?= (string) ($social['platform'] ?? '') === $p ? 'selected' : '' ?>><?= e($platformLabel($p)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_social_url')) ?></label>
                            <input type="text" name="social_links[<?= $sk ?>][url]" value="<?= e($social['url'] ?? '') ?>" placeholder="https://facebook.com/yourpage" class="<?= $inputCls ?>">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-social-link"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-500 hover:border-brand-500 hover:text-brand-600 transition">
                <span class="text-lg leading-none">+</span> <?= e(trans('admin_footer_add_social')) ?>
            </button>
        </section>

        <!-- ============ Quick links ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-1 mt-4"><?= e(trans('admin_footer_quick_links')) ?></h2>
            <p class="text-xs text-stone-400 mb-3"><?= e(trans('admin_footer_quick_links_desc')) ?></p>
            <div class="space-y-4" id="quick-links">
                <?php $existingQuickLinks = footer_quick_links(); ?>
                <?php foreach ($existingQuickLinks as $qlKey => $ql): $qlNum = (int) $qlKey + 1; ?>
                <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
                        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_ql_link')) ?> <?= $qlNum ?></span>
                        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                            <input type="checkbox" name="quick_links[<?= $qlKey ?>][enabled]" value="1"
                                   class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= !empty($ql['enabled']) ? 'checked' : '' ?>>
                            <?= e(trans('admin_ql_enabled')) ?>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
                            <input type="checkbox" name="quick_links_remove[<?= $qlKey ?>]" value="1"
                                   class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
                            <?= e(trans('admin_ql_remove')) ?>
                        </label>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (EN)</label>
                            <input type="text" name="quick_links[<?= $qlKey ?>][label_en]" value="<?= e($ql['label_en'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_ql_label_en_placeholder')) ?>" class="<?= $inputCls ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (BN)</label>
                            <input type="text" name="quick_links[<?= $qlKey ?>][label_bn]" value="<?= e($ql['label_bn'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_ql_label_bn_placeholder')) ?>" class="<?= $inputCls ?>">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_url')) ?></label>
                            <input type="text" name="quick_links[<?= $qlKey ?>][url]" value="<?= e($ql['url'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_ql_url_hint')) ?>" class="<?= $inputCls ?>">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-quick-link"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-500 hover:border-brand-500 hover:text-brand-600 transition">
                <span class="text-lg leading-none">+</span> <?= e(trans('admin_footer_add_link')) ?>
            </button>
        </section>

        <!-- ============ Copyright ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-3 mt-4"><?= e(trans('admin_copyright')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_en')) ?></label>
                    <input type="text" name="copyright_en" value="<?= e($footer['copyright_en']) ?>" class="<?= $inputCls ?> mt-1">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                    <input type="text" name="copyright_bn" value="<?= e($footer['copyright_bn']) ?>" class="<?= $inputCls ?> mt-1">
                </div>
            </div>
            <p class="text-xs text-stone-400 mt-2"><?= e(trans('admin_copyright_hint')) ?></p>
        </section>

        <!-- ============ Appearance ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h2 class="text-sm font-bold text-stone-800 mb-1 mt-4"><?= e(trans('admin_footer_appearance')) ?></h2>
            <p class="text-xs text-stone-400 mb-3"><?= e(trans('admin_footer_appearance_desc')) ?></p>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <?php
                $colorFields = [
                    'footer_bg_color'         => 'admin_footer_bg_color',
                    'footer_heading_color'    => 'admin_footer_heading_color',
                    'footer_text_color'       => 'admin_footer_text_color',
                    'footer_link_color'       => 'admin_footer_link_color',
                    'footer_link_hover_color' => 'admin_footer_link_hover_color',
                    'footer_border_color'     => 'admin_footer_border_color',
                ];
                foreach ($colorFields as $fieldName => $labelKey): ?>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans($labelKey)) ?></label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="<?= e($fieldName) ?>" value="<?= e($colors[$fieldName]) ?>"
                               class="h-10 w-12 rounded-lg border border-stone-200 bg-white p-1 cursor-pointer">
                        <input type="text" value="<?= e($colors[$fieldName]) ?>" readonly
                               class="w-full rounded-xl border border-stone-200 px-2.5 py-2 text-xs text-stone-500 bg-stone-50">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_footer_font_size')) ?></label>
                    <input type="number" name="footer_font_size" min="11" max="22" value="<?= e((string) $nums['footer_font_size']) ?>" class="<?= $inputCls ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_footer_heading_size')) ?></label>
                    <input type="number" name="footer_heading_size" min="11" max="26" value="<?= e((string) $nums['footer_heading_size']) ?>" class="<?= $inputCls ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_footer_padding')) ?></label>
                    <input type="number" name="footer_padding" min="12" max="72" value="<?= e((string) $nums['footer_padding']) ?>" class="<?= $inputCls ?>">
                </div>
            </div>

            <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mt-6 mb-3"><?= e(trans('admin_footer_visibility')) ?></h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <?php
                $visibility = [
                    'footer_show_brand'     => 'admin_footer_show_brand',
                    'footer_show_links'     => 'admin_footer_show_links',
                    'footer_show_contact'   => 'admin_footer_show_contact',
                    'footer_show_delivery'  => 'admin_footer_show_delivery',
                    'footer_show_social'    => 'admin_footer_show_social',
                    'footer_show_pages_bar' => 'admin_footer_show_pages_bar',
                    'footer_show_brand_name' => 'admin_footer_show_brand_name',
                ];
                foreach ($visibility as $fieldName => $labelKey): ?>
                <label class="flex items-center gap-2.5 rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm text-stone-700 cursor-pointer">
                    <input type="checkbox" name="<?= e($fieldName) ?>" value="1"
                           class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= $toggles[$fieldName] !== '0' ? 'checked' : '' ?>>
                    <?= e(trans($labelKey)) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
        </div>
    </form>
</div>

<?php ob_start(); ?>
<div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_ql_link')) ?> {{KEYNUM}}</span>
        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
            <input type="checkbox" name="quick_links[{{KEY}}][enabled]" value="1" checked class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            <?= e(trans('admin_ql_enabled')) ?>
        </label>
        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
            <input type="checkbox" name="quick_links_remove[{{KEY}}]" value="1" class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
            <?= e(trans('admin_ql_remove')) ?>
        </label>
    </div>
    <div class="grid sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (EN)</label>
            <input type="text" name="quick_links[{{KEY}}][label_en]" value="" placeholder="<?= e(trans('admin_ql_label_en_placeholder')) ?>" class="<?= $inputCls ?>">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (BN)</label>
            <input type="text" name="quick_links[{{KEY}}][label_bn]" value="" placeholder="<?= e(trans('admin_ql_label_bn_placeholder')) ?>" class="<?= $inputCls ?>">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_url')) ?></label>
            <input type="text" name="quick_links[{{KEY}}][url]" value="" placeholder="<?= e(trans('admin_ql_url_hint')) ?>" class="<?= $inputCls ?>">
        </div>
    </div>
</div>
<?php $quickLinkTemplate = ob_get_clean(); ?>

<?php ob_start(); ?>
<div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_ql_link')) ?> {{KEYNUM}}</span>
        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
            <input type="checkbox" name="social_links[{{KEY}}][enabled]" value="1" checked class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            <?= e(trans('admin_social_show')) ?>
        </label>
        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
            <input type="checkbox" name="social_links_remove[{{KEY}}]" value="1" class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
            <?= e(trans('admin_social_remove')) ?>
        </label>
    </div>
    <div class="grid sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_social_platform')) ?></label>
            <select name="social_links[{{KEY}}][platform]" class="<?= $inputCls ?>">
                <?php foreach ($platforms as $p): ?>
                    <option value="<?= e($p) ?>"><?= e($platformLabel($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_social_url')) ?></label>
            <input type="text" name="social_links[{{KEY}}][url]" value="" placeholder="https://facebook.com/yourpage" class="<?= $inputCls ?>">
        </div>
    </div>
</div>
<?php $socialLinkTemplate = ob_get_clean(); ?>

<script>
(function () {
    const qlContainer = document.getElementById('quick-links');
    const qlBtn = document.getElementById('add-quick-link');
    const qlTpl = <?= json_encode($quickLinkTemplate) ?>;

    if (qlBtn && qlContainer && qlTpl) {
        qlBtn.addEventListener('click', function () {
            const key = qlContainer.children.length;
            qlContainer.insertAdjacentHTML('beforeend', qlTpl.replaceAll('{{KEY}}', String(key)).replaceAll('{{KEYNUM}}', String(key + 1)));
        });
    }

    const slContainer = document.getElementById('social-links');
    const slBtn = document.getElementById('add-social-link');
    const slTpl = <?= json_encode($socialLinkTemplate) ?>;

    if (slBtn && slContainer && slTpl) {
        slBtn.addEventListener('click', function () {
            const key = slContainer.children.length;
            slContainer.insertAdjacentHTML('beforeend', slTpl.replaceAll('{{KEY}}', String(key)).replaceAll('{{KEYNUM}}', String(key + 1)));
        });
    }
})();
</script>

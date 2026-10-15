<?php
// Colour fields: only a 6-digit hex can be shown in <input type="color">.
$colorDefaults = [
    'header_bg_color'         => '#142e1c',
    'header_text_color'       => '#d6d3d1',
    'header_text_hover_color' => '#ffffff',
    'nav_bg_color'            => '#142e1c',
    'nav_text_color'          => '#e7e5e4',
    'nav_accent_color'        => '#fbbf24',
    'ann_bg_color'            => '#143626',
    'ann_text_color'          => '#ffffff',
];
$colors = [];
foreach ($colorDefaults as $ck => $cd) {
    $cv = site_setting($ck);
    $colors[$ck] = preg_match('/^#[0-9a-fA-F]{6}$/', $cv) ? strtolower($cv) : $cd;
}

$numDefaults = [
    'nav_font_size'     => 14,
    'header_label_size' => 11,
    'header_padding'    => 14,
    'nav_padding'       => 10,
];
$nums = [];
foreach ($numDefaults as $nk => $nd) {
    $nv = (int) site_setting($nk, (string) $nd);
    $nums[$nk] = $nv > 0 ? $nv : $nd;
}

$toggles = [
    'header_show_announcement' => site_setting('header_show_announcement', '1'),
    'header_show_nav'          => site_setting('header_show_nav', '1'),
];

$inputCls = 'w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none';
?>
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_header')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_header_subtitle')) ?></p>
        </div>
        <a href="<?= e(locale_url('/')) ?>" target="_blank" class="text-sm font-semibold text-brand-700 hover:underline"><?= e(trans('admin_view_website')) ?> →</a>
    </div>

    <!-- Live preview -->
    <div class="mb-6">
        <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mb-2"><?= e(trans('admin_header_preview')) ?></h3>
        <div id="hd-preview" class="rounded-2xl overflow-hidden border border-stone-200 shadow-sm">
            <div id="hd-prev-ann" class="text-center text-xs py-1.5 px-3"
                 style="background: var(--hd-ann-bg); color: var(--hd-ann-text)">
                <?= e(trans('ann_cod')) ?> • <?= e(trans('ann_help')) ?>: <?= e(site_setting('footer_phone', '01700-000000')) ?>
            </div>
            <div class="flex items-center justify-between gap-3" style="background: var(--hd-bg); padding: var(--hd-py) 16px">
                <span class="text-lg font-extrabold" style="color: #ffffff"><?= e(trans('site_name')) ?></span>
                <span class="flex items-center gap-3" style="color: var(--hd-text); font-size: var(--hd-label-fs)">
                    <span>EN</span><span><?= e(trans('nav_account')) ?></span><span><?= e(trans('nav_cart')) ?></span>
                </span>
            </div>
            <div id="hd-prev-nav" class="flex items-center gap-5 overflow-hidden" style="background: var(--hd-nav-bg); padding: var(--hd-nav-py) 16px">
                <span class="font-semibold whitespace-nowrap" style="color: var(--hd-accent); font-size: var(--hd-nav-fs); border-bottom: 2px solid var(--hd-accent); padding-bottom: 2px"><?= e(trans('nav_home')) ?></span>
                <span class="whitespace-nowrap" style="color: var(--hd-nav-text); font-size: var(--hd-nav-fs)"><?= e(trans('nav_all_categories')) ?></span>
                <span class="whitespace-nowrap" style="color: var(--hd-nav-text); font-size: var(--hd-nav-fs)"><?= e(trans('nav_products')) ?></span>
            </div>
        </div>
    </div>

    <form id="header-form" method="post" action="<?= e(locale_url('/mf-dashboard/header')) ?>" class="bg-card rounded-2xl border border-honey-600/15 p-6 space-y-8">
        <?= csrf_field() ?>

        <!-- ============ Appearance ============ -->
        <section>
            <h2 class="text-sm font-bold text-stone-800 mb-1"><?= e(trans('admin_header_appearance')) ?></h2>
            <p class="text-xs text-stone-400 mb-3"><?= e(trans('admin_header_appearance_desc')) ?></p>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <?php
                $colorFields = [
                    'header_bg_color'         => 'admin_header_bg_color',
                    'header_text_color'       => 'admin_header_text_color',
                    'header_text_hover_color' => 'admin_header_text_hover_color',
                    'nav_bg_color'            => 'admin_header_nav_bg_color',
                    'nav_text_color'          => 'admin_header_nav_text_color',
                    'nav_accent_color'        => 'admin_header_nav_accent_color',
                    'ann_bg_color'            => 'admin_header_ann_bg_color',
                    'ann_text_color'          => 'admin_header_ann_text_color',
                ];
                foreach ($colorFields as $fieldName => $labelKey): ?>
                <div data-hd-color="<?= e($fieldName) ?>">
                    <label class="block text-xs font-semibold text-stone-500 mb-1" for="<?= e($fieldName) ?>"><?= e(trans($labelKey)) ?></label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="<?= e($fieldName) ?>" name="<?= e($fieldName) ?>" value="<?= e($colors[$fieldName]) ?>"
                               class="h-10 w-12 shrink-0 rounded-lg border border-stone-200 bg-white p-1 cursor-pointer">
                        <input type="text" value="<?= e($colors[$fieldName]) ?>" readonly
                               class="w-full rounded-xl border border-stone-200 px-2.5 py-2 text-xs text-stone-500 bg-stone-50">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5">
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1" for="nav_font_size"><?= e(trans('admin_header_nav_font_size')) ?></label>
                    <input type="number" id="nav_font_size" name="nav_font_size" min="11" max="20" value="<?= e((string) $nums['nav_font_size']) ?>" class="<?= $inputCls ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1" for="header_label_size"><?= e(trans('admin_header_label_size')) ?></label>
                    <input type="number" id="header_label_size" name="header_label_size" min="9" max="16" value="<?= e((string) $nums['header_label_size']) ?>" class="<?= $inputCls ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1" for="header_padding"><?= e(trans('admin_header_padding')) ?></label>
                    <input type="number" id="header_padding" name="header_padding" min="6" max="28" value="<?= e((string) $nums['header_padding']) ?>" class="<?= $inputCls ?>">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1" for="nav_padding"><?= e(trans('admin_header_nav_padding')) ?></label>
                    <input type="number" id="nav_padding" name="nav_padding" min="4" max="24" value="<?= e((string) $nums['nav_padding']) ?>" class="<?= $inputCls ?>">
                </div>
            </div>
        </section>

        <!-- ============ Visibility ============ -->
        <section class="pt-2 border-t border-stone-100">
            <h3 class="text-xs font-bold text-stone-700 uppercase tracking-wide mt-4 mb-3"><?= e(trans('admin_header_visibility')) ?></h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <?php
                $visibility = [
                    'header_show_announcement' => 'admin_header_show_announcement',
                    'header_show_nav'          => 'admin_header_show_nav',
                ];
                foreach ($visibility as $fieldName => $labelKey): ?>
                <label class="flex items-center gap-2.5 rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm text-stone-700 cursor-pointer">
                    <input type="checkbox" id="<?= e($fieldName) ?>" name="<?= e($fieldName) ?>" value="1"
                           class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= $toggles[$fieldName] !== '0' ? 'checked' : '' ?>>
                    <?= e(trans($labelKey)) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="text-xs text-stone-400 mt-3">
                <?= e(trans('admin_header_nav_note')) ?>
                <a href="<?= e(locale_url('/mf-dashboard/settings')) ?>#nav-menu" class="font-semibold text-brand-700 hover:underline"><?= e(trans('admin_nav_menu')) ?></a>.
            </p>
        </section>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
        </div>
    </form>
</div>

<script>
(function () {
    var form = document.getElementById('header-form');
    var preview = document.getElementById('hd-preview');
    if (!form || !preview) return;

    // setting name → CSS custom property (with optional unit)
    var map = [
        ['header_bg_color', '--hd-bg', ''],
        ['header_text_color', '--hd-text', ''],
        ['header_text_hover_color', '--hd-hover', ''],
        ['nav_bg_color', '--hd-nav-bg', ''],
        ['nav_text_color', '--hd-nav-text', ''],
        ['nav_accent_color', '--hd-accent', ''],
        ['ann_bg_color', '--hd-ann-bg', ''],
        ['ann_text_color', '--hd-ann-text', ''],
        ['nav_font_size', '--hd-nav-fs', 'px'],
        ['header_label_size', '--hd-label-fs', 'px'],
        ['header_padding', '--hd-py', 'px'],
        ['nav_padding', '--hd-nav-py', 'px']
    ];

    var apply = function () {
        map.forEach(function (entry) {
            var el = form.elements[entry[0]];
            if (!el) return;
            preview.style.setProperty(entry[1], el.value + entry[2]);
        });

        // Mirror the visibility toggles into the preview.
        var annToggle = form.elements['header_show_announcement'];
        var navToggle = form.elements['header_show_nav'];
        var annPrev = document.getElementById('hd-prev-ann');
        var navPrev = document.getElementById('hd-prev-nav');
        if (annToggle && annPrev) annPrev.style.display = annToggle.checked ? '' : 'none';
        if (navToggle && navPrev) navPrev.style.display = navToggle.checked ? '' : 'none';

        // Keep the read-only hex text in sync with its colour picker.
        form.querySelectorAll('input[type="color"]').forEach(function (picker) {
            var text = picker.parentElement.querySelector('input[type="text"]');
            if (text) text.value = picker.value;
        });
    };

    form.addEventListener('input', apply);
    form.addEventListener('change', apply);
    apply();
})();
</script>

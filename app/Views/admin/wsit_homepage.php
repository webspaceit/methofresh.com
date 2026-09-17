<?php
// $blocks is the associative homepage block config from homepage_blocks().
$blockOrder = array_values($blocks ?? []);
usort($blockOrder, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
$blockNames = [
    'categories' => trans('admin_home_block_categories'),
    'featured'   => trans('admin_home_block_featured'),
    'new'        => trans('admin_home_block_new'),
];

// $heroSlides is the rotating banner config from hero_slides().
$heroRows = array_values($heroSlides ?? hero_slides());
$heroCount = count($heroRows);
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_homepage')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_homepage_subtitle')) ?></p>
    </div>
</div>

<form method="post" action="<?= e(locale_url('/mf-dashboard/homepage')) ?>" enctype="multipart/form-data" class="max-w-4xl space-y-6">
    <?= csrf_field() ?>

    <!-- Tagline -->
    <div class="bg-white rounded-2xl border border-stone-200 p-5 space-y-3">
        <div>
            <h2 class="font-bold text-stone-900"><?= e(trans('admin_tagline')) ?></h2>
            <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_tagline_hint')) ?></p>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_lang_en')) ?></label>
                <input type="text" name="tagline_en" value="<?= e(site_setting('tagline_en')) ?>"
                       placeholder="<?= e(trans('tagline')) ?>"
                       class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                    <?php $source = '[name=tagline_en]'; $target = '[name=tagline_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <input type="text" name="tagline_bn" value="<?= e(site_setting('tagline_bn')) ?>"
                       placeholder="<?= e('তাজা সবজি ও ফল, হাতের নাগালে') ?>"
                       class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>
    </div>

    <!-- Hero slides -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_hero')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_hero_subtitle')) ?></p>
            </div>
            <button type="button" id="add-hero-slide"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 text-xs font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <?= e(trans('admin_hero_add')) ?>
            </button>
        </div>
        <div class="px-5 py-4 grid sm:grid-cols-2 gap-4 border-b border-stone-100">
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_speed')) ?></label>
                <input type="number" name="hero_speed" min="1000" max="20000" step="100" value="<?= (int)site_setting('hero_speed', '5500') ?>"
                       class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_hero_speed_hint')) ?></p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_height')) ?></label>
                <input type="number" name="hero_height" min="300" max="900" step="10" value="<?= (int)site_setting('hero_height', '540') ?>"
                       class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_hero_height_hint')) ?></p>
            </div>
        </div>
        <div id="hero-slides" class="p-5 space-y-5">
            <?php foreach ($heroRows as $i => $slide): ?>
            <?php $key = $i; include views_path('admin/partials/wsit_hero_slide.php'); ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Homepage blocks -->
    <?php foreach ($blockOrder as $block): ?>
    <?php $bid = $block['id']; ?>
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-3">
            <h2 class="font-bold text-stone-900"><?= e($blockNames[$bid] ?? $bid) ?></h2>
            <label class="flex items-center gap-2 text-xs font-semibold text-stone-600 cursor-pointer">
                <input type="checkbox" name="<?= e($bid) ?>_visible" value="1" <?= $block['visible'] ? 'checked' : '' ?> class="w-4 h-4 text-brand-600 focus:ring-brand-500 rounded">
                <?= e(trans('admin_home_show')) ?>
            </label>
        </div>
        <div class="p-5 grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_home_order')) ?></label>
                <input type="number" name="<?= e($bid) ?>_order" min="1" max="3" value="<?= (int)$block['order'] ?>"
                       class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_home_title_en')) ?></label>
                <input type="text" name="<?= e($bid) ?>_title_en" value="<?= e($block['title_en']) ?>"
                       placeholder="<?= e(trans('admin_home_title_placeholder')) ?>"
                       class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_home_title_bn')) ?></label>
                <input type="text" name="<?= e($bid) ?>_title_bn" value="<?= e($block['title_bn']) ?>"
                       placeholder="<?= e(trans('admin_home_title_placeholder')) ?>"
                       class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>
        <?php if ($bid === 'new'): ?>
        <div class="px-5 pb-5">
            <p class="flex gap-2 text-xs text-stone-500">
                <svg class="mt-0.5 w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M12 12v4"/>
                </svg>
                <span><?= e(trans('admin_home_block_new_hint')) ?></span>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-4">
        <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
            <?= e(trans('admin_save')) ?>
        </button>
        <a href="<?= e(locale_url('/')) ?>" class="text-sm font-semibold text-stone-500 hover:text-brand-700"><?= e(trans('admin_view_website')) ?> →</a>
    </div>
</form>

<template id="hero-slide-template">
    <?php $key = '{{KEY}}'; $slide = []; include views_path('admin/partials/wsit_hero_slide.php'); ?>
</template>

<script>
(function () {
    var addBtn = document.getElementById('add-hero-slide');
    var container = document.getElementById('hero-slides');
    var tpl = document.getElementById('hero-slide-template');
    if (!addBtn || !container || !tpl) return;

    function addSlide() {
        var box = tpl.content.cloneNode(true);
        var existing = container.querySelectorAll('[data-slide-key]');
        var max = -1;
        existing.forEach(function (el) {
            var v = parseInt(el.getAttribute('data-slide-key'), 10);
            if (!isNaN(v) && v > max) max = v;
        });
        var key = max + 1;
        walk(box, function (node) {
            if (node.nodeType === 1 && node.attributes) {
                for (var i = 0; i < node.attributes.length; i++) {
                    node.attributes[i].value = node.attributes[i].value.split('{{KEY}}').join(key);
                }
            }
        });
        box.querySelectorAll('[data-slide-badge]').forEach(function (b) {
            b.textContent = parseInt(key, 10) + 1;
        });
        container.appendChild(box);
    }
    function walk(node, fn) {
        fn(node);
        Array.prototype.slice.call(node.childNodes || []).forEach(function (c) { walk(c, fn); });
    }
    addBtn.addEventListener('click', addSlide);
})();

function heroSlidePreview(input) {
    var target = document.querySelector(input ? input.getAttribute('data-preview-target') : null);
    if (!target || !input.files || !input.files[0]) return;
    target.src = URL.createObjectURL(input.files[0]);
    target.classList.remove('hidden');
}
</script>
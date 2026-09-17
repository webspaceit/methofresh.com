<?php
// $key  — form array index for this slide (used inside hero_slides[<key>][...])
// $slide — slide array from hero_slides()
if (!isset($key) || !is_array($slide ?? null)) {
    $slide = [];
}
$pfx    = 'hero_slides[' . $key . ']';
$image  = trim((string)($slide['image'] ?? ''));
$enabled = !empty($slide['enabled']);
?>
<div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4" data-slide-key="<?= e($key) ?>">
    <input type="hidden" name="<?= e($pfx) ?>[id]" value="<?= (int)($slide['id'] ?? 0) ?>">

    <div class="flex flex-wrap items-center gap-3 mb-4">
        <span class="inline-flex items-center justify-center rounded-lg bg-brand-600 text-white text-xs font-bold px-2.5 py-1.5">
            <?= e(trans('admin_hero_slide')) ?> <span data-slide-badge><?= (int)($slide['order'] ?? $key) ?></span>
        </span>
        <label class="flex items-center gap-2 text-xs font-semibold text-stone-600 cursor-pointer">
            <input type="checkbox" name="<?= e($pfx) ?>[enabled]" value="1" <?= $enabled ? 'checked' : '' ?> class="w-4 h-4 text-brand-600 focus:ring-brand-500 rounded">
            <?= e(trans('admin_hero_enabled')) ?>
        </label>
        <label class="flex items-center gap-2 text-xs font-semibold text-stone-600 cursor-pointer ml-auto">
            <?= e(trans('admin_hero_remove')) ?>
            <input type="checkbox" name="<?= e($pfx) ?>[delete]" value="1" class="w-4 h-4 text-red-600 focus:ring-red-500 rounded">
        </label>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_order')) ?></label>
            <input type="number" name="<?= e($pfx) ?>[order]" min="1" value="<?= (int)($slide['order'] ?? 1) ?>"
                   class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_emoji')) ?></label>
            <input type="text" name="<?= e($pfx) ?>[emoji]" value="<?= e($slide['emoji'] ?? '') ?>" maxlength="4"
                   placeholder="🥬"
                   class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_image')) ?></label>
            <div class="flex items-start gap-3">
                <div class="flex-1">
                    <input type="file" name="<?= e($pfx) ?>[image_file]" accept="image/png,image/jpeg,image/webp,image/gif"
                           data-preview-target="#hero-preview-<?= e($key) ?>" onchange="heroSlidePreview(this)"
                           class="w-full text-sm text-stone-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <input type="hidden" name="<?= e($pfx) ?>[image]" value="<?= e($image) ?>">
                    <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_hero_image_hint')) ?></p>
                    <?php if ($image !== ''): ?>
                    <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-red-600 cursor-pointer">
                        <input type="checkbox" name="<?= e($pfx) ?>[remove_image]" value="1" class="w-4 h-4 text-red-600 focus:ring-red-500 rounded">
                        <?= e(trans('admin_hero_remove_image')) ?>
                    </label>
                    <?php endif; ?>
                </div>
                <div class="shrink-0">
                    <img id="hero-preview-<?= e($key) ?>" src="<?= $image !== '' ? e(image_url($image, 'Hero', 640)) : '' ?>"
                         alt="" class="h-24 w-36 rounded-lg border border-stone-200 object-cover <?= $image !== '' ? '' : 'hidden' ?>">
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_title')) ?> (EN)</label>
                <div class="flex items-center mb-1">
                    <label class="text-xs font-semibold text-stone-600"><?= e(trans('admin_hero_title')) ?> (BN)</label>
                    <?php $source = '[name="' . $pfx . '[title_en]"]'; $target = '[name="' . $pfx . '[title_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
            </div>
            <input type="text" name="<?= e($pfx) ?>[title_en]" value="<?= e($slide['title_en'] ?? '') ?>" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none mb-2" placeholder="Fresh & Healthy, Delivered Fast">
            <input type="text" name="<?= e($pfx) ?>[title_bn]" value="<?= e($slide['title_bn'] ?? '') ?>" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="তাজা ও স্বাস্থ্যকর, দ্রুত ডেলিভারি">
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label class="text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_subtitle_lbl')) ?> (EN)</label>
                <div class="flex items-center mb-1">
                    <label class="text-xs font-semibold text-stone-600"><?= e(trans('admin_hero_subtitle_lbl')) ?> (BN)</label>
                    <?php $source = '[name="' . $pfx . '[subtitle_en]"]'; $target = '[name="' . $pfx . '[subtitle_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
            </div>
            <textarea name="<?= e($pfx) ?>[subtitle_en]" rows="3" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none mb-2" placeholder="Shop premium groceries, fruits and vegetables at great prices."><?= e($slide['subtitle_en'] ?? '') ?></textarea>
            <textarea name="<?= e($pfx) ?>[subtitle_bn]" rows="3" class="w-full rounded-xl border border-stone-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="সেরা মানের সবজি, ফল ও প্রতিদিনের পণ্য সাশ্রয়ী দামে কিনুন।"><?= e($slide['subtitle_bn'] ?? '') ?></textarea>
        </div>

        <div class="sm:col-span-2 grid sm:grid-cols-2 gap-4 border-t border-stone-200 pt-4">
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_btn1')) ?></label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="<?= e($pfx) ?>[btn1_text_en]" value="<?= e($slide['btn1_text_en'] ?? '') ?>" placeholder="Shop Now" class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <input type="text" name="<?= e($pfx) ?>[btn1_text_bn]" value="<?= e($slide['btn1_text_bn'] ?? '') ?>" placeholder="এখনই কিনুন" class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <label class="block text-xs font-semibold text-stone-400 mt-2 mb-1"><?= e(trans('admin_hero_btn_link')) ?> <span class="text-stone-300">(<?= e(trans('admin_hero_btn_hint')) ?>)</span></label>
                <input type="text" name="<?= e($pfx) ?>[btn1_link]" value="<?= e($slide['btn1_link'] ?? '/products') ?>" placeholder="/products"
                       class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_hero_btn2')) ?></label>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="<?= e($pfx) ?>[btn2_text_en]" value="<?= e($slide['btn2_text_en'] ?? '') ?>" placeholder="Categories" class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <input type="text" name="<?= e($pfx) ?>[btn2_text_bn]" value="<?= e($slide['btn2_text_bn'] ?? '') ?>" placeholder="ক্যাটাগরি" class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <label class="block text-xs font-semibold text-stone-400 mt-2 mb-1"><?= e(trans('admin_hero_btn_link')) ?> <span class="text-stone-300">(<?= e(trans('admin_hero_btn_hint')) ?>)</span></label>
                <input type="text" name="<?= e($pfx) ?>[btn2_link]" value="<?= e($slide['btn2_link'] ?? '/categories') ?>" placeholder="/categories"
                       class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>
    </div>
</div>
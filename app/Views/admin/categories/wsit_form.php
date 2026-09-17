<?php
$isEdit = $category !== null;
$c = $category ?? [];
$formAction = $isEdit
    ? locale_url('/mf-dashboard/categories/' . $c['id'] . '/update')
    : locale_url('/mf-dashboard/categories');
?>
<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-extrabold text-stone-900"><?= $isEdit ? e(trans('admin_edit_category')) : e(trans('admin_add_category')) ?></h1>
        <a href="<?= e(locale_url('/mf-dashboard/categories')) ?>" class="text-sm font-semibold text-brand-700 hover:underline">← <?= e(trans('admin_back')) ?></a>
    </div>

    <form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="bg-white rounded-2xl border border-stone-200 p-6 space-y-4">
        <?= csrf_field() ?>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_name_en')) ?> *</label>
                </div>
                <input type="text" name="name_en" required value="<?= e($c['name_en'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_name_bn')) ?> *</label>
                    <?php $source = '[name=name_en]'; $target = '[name=name_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <input type="text" name="name_bn" required value="<?= e($c['name_bn'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_slug')) ?></label>
            <input type="text" name="slug" value="<?= e($c['slug'] ?? '') ?>" placeholder="auto" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_desc_en')) ?></label>
                </div>
                <textarea name="description_en" rows="3" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($c['description_en'] ?? '') ?></textarea>
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_desc_bn')) ?></label>
                    <?php $source = '[name=description_en]'; $target = '[name=description_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <textarea name="description_bn" rows="3" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($c['description_bn'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="active" value="1" class="accent-brand-600" <?= $isEdit && (int)$c['active'] === 0 ? '' : 'checked' ?>>
                <?= e(trans('admin_active')) ?>
            </label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
            <a href="<?= e(locale_url('/mf-dashboard/categories')) ?>" class="rounded-xl border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-600 hover:border-stone-400"><?= e(trans('admin_cancel')) ?></a>
        </div>
    </form>
</div>

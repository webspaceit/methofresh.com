<?php
$isEdit = $product !== null;
$p = $product ?? [];
$formAction = $isEdit
    ? locale_url('/mf-dashboard/products/' . $p['id'] . '/update')
    : locale_url('/mf-dashboard/products');
?>
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-900"><?= $isEdit ? e(trans('admin_edit_product')) : e(trans('admin_add_product')) ?></h1>
        </div>
        <a href="<?= e(locale_url('/mf-dashboard/products')) ?>" class="text-sm font-semibold text-brand-700 hover:underline">← <?= e(trans('admin_back')) ?></a>
    </div>

    <form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="bg-white rounded-2xl border border-stone-200 p-6 space-y-5">
        <?= csrf_field() ?>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_name_en')) ?> *</label>
                <input type="text" name="name_en" required value="<?= e($p['name_en'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_name_bn')) ?> *</label>
                    <?php $source = '[name=name_en]'; $target = '[name=name_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <input type="text" name="name_bn" required value="<?= e($p['name_bn'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_category')) ?> *</label>
                <select name="category_id" required class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                    <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= isset($p['category_id']) && (int)$p['category_id'] === (int)$category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name_en']) ?> / <?= e($category['name_bn']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_sku')) ?> *</label>
                <input type="text" name="sku" required value="<?= e($p['sku'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_slug')) ?></label>
                <input type="text" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto from EN name" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('category')) ?></label>
                <input type="text" disabled value="<?= e($p['category_name_en'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2.5 text-sm">
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_price')) ?> *</label>
                <input type="number" step="0.01" min="0" name="price" required value="<?= e($p['price'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_sale_price')) ?></label>
                <input type="number" step="0.01" min="0" name="sale_price" value="<?= e($p['sale_price'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_stock')) ?></label>
                <input type="number" min="0" name="stock" value="<?= e($p['stock'] ?? 0) ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_desc_en')) ?></label>
                <textarea name="description_en" rows="4" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($p['description_en'] ?? '') ?></textarea>
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_desc_bn')) ?></label>
                    <?php $source = '[name=description_en]'; $target = '[name=description_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <textarea name="description_bn" rows="4" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($p['description_bn'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-4 items-center">
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_import_file')) ?></label>
                <input type="file" name="image" accept="image/*" class="mt-1 block w-full text-sm text-stone-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                <?php if (!empty($p['image'])): ?>
                <p class="mt-1 text-[11px] text-green-600">✓ <?= basename($p['image']) ?></p>
                <?php endif; ?>
            </div>
            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="active" value="1" class="accent-brand-600" <?= $isEdit && (int)$p['active'] === 0 ? '' : 'checked' ?>>
                <?= e(trans('admin_active')) ?>
            </label>
            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="featured" value="1" class="accent-brand-600" <?= $isEdit && (int)$p['featured'] === 1 ? 'checked' : '' ?>>
                <?= e(trans('admin_featured')) ?>
            </label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
            <a href="<?= e(locale_url('/mf-dashboard/products')) ?>" class="rounded-xl border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-600 hover:border-stone-400"><?= e(trans('admin_cancel')) ?></a>
        </div>
    </form>
</div>

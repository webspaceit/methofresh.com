<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_categories')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(format_number(count($categories))) ?> <?= e(trans('admin_categories')) ?></p>
    </div>
    <a href="<?= e(locale_url('/mf-dashboard/categories/create')) ?>" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-bold">+ <?= e(trans('admin_add_category')) ?></a>
</div>

<div class="bg-white rounded-2xl border border-stone-200 overflow-x-auto">
    <?php if (empty($categories)): ?>
    <p class="text-center text-sm text-stone-400 py-12"><?= e(trans('admin_no_items')) ?></p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-stone-500 border-b border-stone-100 uppercase">
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_thumb')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_name')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_slug')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_products')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_active')) ?></th>
                <th class="px-4 py-3 font-semibold text-right"><?= e(trans('action')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
            <tr class="border-b border-stone-50 last:border-0 hover:bg-stone-50">
                <td class="px-4 py-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-lg">🥦</span>
                </td>
                <td class="px-4 py-3">
                    <p class="font-semibold text-stone-800"><?= e($category['name_en']) ?></p>
                    <p class="text-[11px] text-stone-400"><?= e($category['name_bn']) ?></p>
                </td>
                <td class="px-4 py-3 text-stone-600 font-mono text-xs"><?= e($category['slug']) ?></td>
                <td class="px-4 py-3"><span class="inline-block rounded-full bg-brand-50 text-brand-700 px-2.5 py-1 text-[11px] font-bold"><?= e(format_number((int)$category['product_count'])) ?></span></td>
                <td class="px-4 py-3">
                    <form method="post" action="<?= e(locale_url('/mf-dashboard/categories/' . $category['id'] . '/toggle')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="text-sm font-bold <?= (int)$category['active'] === 1 ? 'text-green-600' : 'text-red-500' ?>"><?= (int)$category['active'] === 1 ? e(trans('admin_yes')) : e(trans('admin_no')) ?></button>
                    </form>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="<?= e(locale_url('/mf-dashboard/categories/' . $category['id'] . '/edit')) ?>" class="inline-block rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold hover:border-brand-600 hover:text-brand-700"><?= e(trans('admin_edit')) ?></a>
                    <form method="post" action="<?= e(locale_url('/mf-dashboard/categories/' . $category['id'] . '/delete')) ?>" class="inline" onsubmit="return confirm('<?= e(trans('admin_delete_confirm')) ?>')">
                        <?= csrf_field() ?>
                        <button type="submit" class="rounded-lg border border-red-200 text-red-600 px-3 py-1.5 text-xs font-semibold hover:bg-red-50"><?= e(trans('admin_delete')) ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
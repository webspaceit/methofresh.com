<?php foreach ($products as $product): ?>
<tr class="border-b border-stone-50 last:border-0 hover:bg-stone-50">
    <td class="px-4 py-3">
        <img src="<?= e(image_url($product['image'] ?? '', $product['name_en'] ?? 'P', 48)) ?>"
             class="w-10 h-10 rounded-lg object-cover bg-stone-100" alt="">
    </td>
    <td class="px-4 py-3">
        <p class="font-semibold text-stone-800"><?= e($product['name_en']) ?></p>
        <p class="text-[11px] text-stone-400"><?= app_locale() === 'bn' ? e($product['name_bn'] ?? '') : e('#' . $product['id']) ?></p>
    </td>
    <td class="px-4 py-3 text-stone-600 font-mono text-xs"><?= e($product['sku']) ?></td>
    <td class="px-4 py-3 text-stone-600"><?= e($product['category_name_en'] ?? '—') ?></td>
    <td class="px-4 py-3">
        <span class="font-semibold"><?= e(format_price((float)$product['price'])) ?></span>
        <?php if ($product['sale_price']): ?>
        <span class="block text-[11px] text-red-600"><?= e(format_price((float)$product['sale_price'])) ?></span>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3">
        <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-bold <?= (int)$product['stock'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= e(format_number((int)$product['stock'])) ?>
        </span>
    </td>
    <td class="px-4 py-3">
        <form method="post" action="<?= e(locale_url('/mf-dashboard/products/' . $product['id'] . '/featured')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="text-lg" title="<?= e(trans('admin_featured')) ?>"><?= (int)$product['featured'] === 1 ? '⭐' : '☆' ?></button>
        </form>
    </td>
    <td class="px-4 py-3">
        <form method="post" action="<?= e(locale_url('/mf-dashboard/products/' . $product['id'] . '/new-arrival')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="text-[11px] font-bold rounded-md px-2 py-1 <?= (int)$product['new_arrival'] === 1 ? 'bg-amber-100 text-amber-700' : 'bg-stone-100 text-stone-400 hover:bg-amber-50' ?>"
                    title="<?= e(trans('admin_new_arrival')) ?>">NEW</button>
        </form>
    </td>
    <td class="px-4 py-3">
        <form method="post" action="<?= e(locale_url('/mf-dashboard/products/' . $product['id'] . '/toggle')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="text-sm font-bold <?= (int)$product['active'] === 1 ? 'text-green-600' : 'text-red-500' ?>"><?= (int)$product['active'] === 1 ? e(trans('admin_yes')) : e(trans('admin_no')) ?></button>
        </form>
    </td>
    <td class="px-4 py-3 text-right whitespace-nowrap">
        <a href="<?= e(locale_url('/mf-dashboard/products/' . $product['id'] . '/edit')) ?>" class="inline-block rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold hover:border-brand-600 hover:text-brand-700"><?= e(trans('admin_edit')) ?></a>
        <form method="post" action="<?= e(locale_url('/mf-dashboard/products/' . $product['id'] . '/delete')) ?>" class="inline" onsubmit="return confirm('<?= e(trans('admin_delete_confirm')) ?>')">
            <?= csrf_field() ?>
            <button type="submit" class="rounded-lg border border-red-200 text-red-600 px-3 py-1.5 text-xs font-semibold hover:bg-red-50"><?= e(trans('admin_delete')) ?></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
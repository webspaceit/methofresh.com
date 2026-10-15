<?php
$priceInfo = price_display($product);
$name = $product['name'] ?? ($product['name_en'] ?? '');
$image = $product['image'] ?? '';
$imgUrl = image_url($image, $name);
?>
<div class="group flex flex-col bg-white rounded-2xl overflow-hidden border border-stone-200 hover:border-amber-400 hover:shadow-xl transition">
    <a href="<?= e(locale_url('/products/' . $product['slug'])) ?>" class="relative overflow-hidden bg-stone-100 h-52 sm:h-56 flex items-center justify-center p-4">
        <img src="<?= e($imgUrl) ?>" alt="<?= e($name) ?>" loading="lazy"
             class="max-h-full max-w-full object-contain group-hover:scale-105 transition duration-500">
        <?php if ($priceInfo['discount'] > 0): ?>
        <span class="absolute top-3 left-3 bg-amber-600 text-white text-[11px] font-semibold px-2.5 py-0.5 rounded-full z-10">-<?= e(format_number((int)$priceInfo['discount'])) ?>%</span>
        <?php endif; ?>
        <?php if ((int)$product['stock'] <= 0): ?>
        <span class="absolute inset-0 bg-black/50 flex items-center justify-center text-white font-semibold text-sm uppercase tracking-wide"><?= e(trans('out_of_stock')) ?></span>
        <?php endif; ?>
    </a>
    <div class="p-5 flex-1 flex flex-col justify-between">
        <div>
            <a href="<?= e(locale_url('/categories/' . ($product['category_slug'] ?? ''))) ?>" class="text-[11px] font-medium text-amber-600 uppercase tracking-wide">
                <?= e($product['category_name'] ?? '') ?>
            </a>
            <a href="<?= e(locale_url('/products/' . $product['slug'])) ?>" class="mt-1 block text-base font-bold text-stone-900 leading-snug line-clamp-2 group-hover:text-brand-700 transition">
                <?= e($name) ?>
            </a>
            <div class="flex items-center gap-2 mt-3">
                <span class="text-lg font-bold text-stone-900"><?= e(format_price($priceInfo['current'])) ?></span>
                <?php if ($priceInfo['sale'] !== null): ?>
                    <span class="text-sm text-stone-400 line-through"><?= e(format_price($priceInfo['price'])) ?></span>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded"><?= e(trans('discount_pill')) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ((int)$product['stock'] > 0): ?>
        <button @click="flyToCart($event, '<?= e($imgUrl) ?>'); addToCart(<?= (int)$product['id'] ?>, 1); trackAddToCart(<?= (int)$product['id'] ?>, 1, '<?= e($name) ?>', <?= (float)$priceInfo['current'] ?>, '<?= e(app_locale() === 'bn' ? 'BDT' : 'USD') ?>')"
                class="mt-5 w-full py-2.5 px-4 rounded-lg bg-brand-700 hover:bg-brand-800 text-white text-sm font-semibold transition flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span><?= e(trans('land_order_now')) ?></span>
        </button>
        <?php else: ?>
        <span class="mt-5 block w-full py-2.5 px-4 rounded-lg bg-stone-100 text-stone-400 text-sm font-semibold text-center"><?= e(trans('out_of_stock')) ?></span>
        <?php endif; ?>
    </div>
</div>
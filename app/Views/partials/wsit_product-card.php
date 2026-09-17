<?php
$priceInfo = price_display($product);
$name = $product['name'] ?? ($product['name_en'] ?? '');
$image = $product['image'] ?? '';
$imgUrl = image_url($image, $name);
?>
<div class="group bg-white rounded-2xl border border-stone-200 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition shadow-sm">
    <a href="<?= e(locale_url('/products/' . $product['slug'])) ?>" class="relative block aspect-square overflow-hidden bg-stone-100">
        <img src="<?= e($imgUrl) ?>" alt="<?= e($name) ?>" loading="lazy"
             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php if ($priceInfo['discount'] > 0): ?>
        <span class="absolute top-2 left-2 bg-red-600 text-white text-[11px] font-bold px-2 py-1 rounded-lg">-<?= e(format_number((int)$priceInfo['discount'])) ?>%</span>
        <?php endif; ?>
        <?php if ((int)$product['stock'] <= 0): ?>
        <span class="absolute inset-0 bg-black/50 flex items-center justify-center text-white font-semibold text-sm uppercase tracking-wide"><?= e(trans('out_of_stock')) ?></span>
        <?php endif; ?>
    </a>
    <div class="p-4">
        <a href="<?= e(locale_url('/categories/' . ($product['category_slug'] ?? ''))) ?>" class="text-[11px] font-medium text-brand-700 uppercase tracking-wide">
            <?= e($product['category_name'] ?? '') ?>
        </a>
        <a href="<?= e(locale_url('/products/' . $product['slug'])) ?>" class="mt-1 block text-sm font-semibold text-stone-800 leading-snug line-clamp-2 hover:text-brand-700">
            <?= e($name) ?>
        </a>
        <div class="mt-2 flex items-baseline justify-between gap-2">
            <div>
                <span class="text-base font-extrabold text-brand-700"><?= e(format_price($priceInfo['current'])) ?></span>
                <?php if ($priceInfo['sale'] !== null): ?>
                <span class="text-xs text-stone-400 line-through ml-1"><?= e(format_price($priceInfo['price'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if ((int)$product['stock'] > 0): ?>
            <button @click="addToCart(<?= (int)$product['id'] ?>, 1)"
                    class="inline-flex items-center gap-1 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold px-3 py-2 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <?= e(trans('add_to_cart_short')) ?>
            </button>
            <?php else: ?>
            <span class="text-xs text-red-500 font-medium"><?= e(trans('out_of_stock')) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
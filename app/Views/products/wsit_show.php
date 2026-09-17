<?php
$priceInfo = price_display($product);
$image = $product['image'] ?? '';
$imgUrl = image_url($image, $product['name'] ?? '', 600);
$inStock = (int)$product['stock'] > 0;
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <a href="<?= e(locale_url('/products')) ?>" class="hover:text-brand-700"><?= e(trans('nav_products')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e($product['name']) ?></span>
    </nav>

    <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
        <!-- Image -->
        <div class="relative rounded-3xl overflow-hidden bg-stone-100 border border-stone-200 aspect-square lg:sticky lg:top-24">
            <img src="<?= e($imgUrl) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover">
            <?php if ($priceInfo['discount'] > 0): ?>
            <span class="absolute top-4 left-4 bg-red-600 text-white text-sm font-bold px-3 py-1.5 rounded-xl">-<?= e(format_number((int)$priceInfo['discount'])) ?>%</span>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div>
            <a href="<?= e(locale_url('/categories/' . $product['category_slug'])) ?>" class="inline-block text-xs font-bold text-brand-700 uppercase tracking-wider bg-brand-50 px-3 py-1 rounded-lg">
                <?= e($product['category_name']) ?>
            </a>
            <h1 class="mt-3 text-2xl sm:text-3xl font-extrabold text-stone-900"><?= e($product['name']) ?></h1>

            <div class="mt-3 flex items-center gap-2 text-sm text-stone-500">
                <span class="inline-flex items-center gap-1 rounded-full bg-green-50 text-green-700 px-3 py-1 text-xs font-semibold">
                    <?= $inStock ? e(trans('in_stock')) . ' (' . e(format_number((int)$product['stock'])) . ')' : e(trans('out_of_stock')) ?>
                </span>
                <?php if ((int)$product['stock'] > 0 && (int)$product['stock'] <= 5): ?>
                <span class="text-amber-600 font-medium"><?= e(str_replace(':count', format_number((int)$product['stock']), trans('low_stock'))) ?></span>
                <?php endif; ?>
                <?php if ((int)$product['sold'] > 0): ?>
                <span>• <?= e(trans('product_sold')) ?>: <?= e(format_number((int)$product['sold'])) ?></span>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <div class="mt-5 flex items-baseline gap-3">
                <span class="text-3xl font-extrabold text-brand-700"><?= e(format_price($priceInfo['current'])) ?></span>
                <?php if ($priceInfo['sale'] !== null): ?>
                <span class="text-lg text-stone-400 line-through"><?= e(format_price($priceInfo['price'])) ?></span>
                <span class="text-sm font-bold text-red-600"><?= e(trans('save')) ?> <?= e(format_price($priceInfo['price'] - $priceInfo['current'])) ?></span>
                <?php endif; ?>
            </div>

            <!-- Quantity + add to cart -->
            <div class="mt-6 flex flex-wrap items-center gap-3" x-data="{ qty: 1 }">
                <div class="flex items-center rounded-xl border border-stone-200 bg-white">
                    <button @click="qty = Math.max(1, qty - 1)" class="px-4 py-3 text-lg font-bold text-stone-500 hover:text-brand-700">−</button>
                    <input type="number" x-model="qty" min="1" max="<?= (int)$product['stock'] ?>" readonly
                           class="w-14 text-center text-sm font-bold focus:outline-none bg-transparent">
                    <button @click="qty = Math.min(<?= (int)$product['stock'] ?>, qty + 1)" class="px-4 py-3 text-lg font-bold text-stone-500 hover:text-brand-700">+</button>
                </div>

                <?php if ($inStock): ?>
                <button @click="addToCart(<?= (int)$product['id'] ?>, qty)"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 text-sm transition shadow-lg shadow-brand-600/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <?= e(trans('add_to_cart')) ?>
                </button>
                <?php else: ?>
                <button disabled class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-stone-300 text-stone-500 font-bold px-8 py-3.5 text-sm cursor-not-allowed">
                    <?= e(trans('out_of_stock')) ?>
                </button>
                <?php endif; ?>
            </div>

            <p class="mt-3 text-xs text-stone-400"><?= e(trans('promo_hint')) ?></p>

            <!-- Description -->
            <div class="mt-8 border-t border-stone-200 pt-6">
                <h2 class="text-lg font-bold text-stone-900 mb-2"><?= e(trans('description')) ?></h2>
                <p class="text-sm leading-relaxed text-stone-600 whitespace-pre-line"><?= e($product['description']) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($related)): ?>
    <section class="mt-14">
        <div class="flex items-end justify-between mb-5">
            <h2 class="text-xl sm:text-2xl font-extrabold text-stone-900"><?= e(trans('related_products')) ?></h2>
            <a href="<?= e(locale_url('/categories/' . $product['category_slug'])) ?>" class="text-sm font-semibold text-brand-700 hover:underline"><?= e(trans('view_all')) ?> →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($related as $product) include views_path('partials/wsit_product-card.php'); ?>
        </div>
    </section>
    <?php endif; ?>
</section>
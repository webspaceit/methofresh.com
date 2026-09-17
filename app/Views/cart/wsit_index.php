<?php $cartItemCount = count($cart); ?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e(trans('nav_cart')) ?></span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 mb-6"><?= e(trans('cart')) ?> <span class="text-base font-semibold text-stone-400">(<?= e(format_number($cartItemCount)) ?> <?= $cartItemCount === 1 ? e(trans('item')) : e(trans('items')) ?>)</span></h1>

    <?php if (empty($cart)): ?>
    <div class="text-center py-20 bg-white rounded-3xl border border-stone-200">
        <p class="text-7xl mb-4">🛒</p>
        <h2 class="text-xl font-bold text-stone-800"><?= e(trans('cart_empty')) ?></h2>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('cart_empty_hint')) ?></p>
        <a href="<?= e(locale_url('/products')) ?>" class="inline-block mt-6 rounded-xl bg-brand-600 text-white px-8 py-3 text-sm font-bold hover:bg-brand-700"><?= e(trans('start_shopping')) ?></a>
    </div>
    <?php else: ?>

    <div class="lg:grid lg:grid-cols-[1fr,360px] gap-8" x-data="cartSummary()">
        <!-- Items -->
        <form method="post" action="<?= e(locale_url('/cart/update')) ?>" class="space-y-3">
            <?= csrf_field() ?>
            <?php foreach ($cart as $item): ?>
            <?php $itemTotal = (float)$item['current_price'] * (int)$item['quantity']; ?>
            <div class="bg-white rounded-2xl border border-stone-200 p-3 sm:p-4 flex gap-4 items-center" x-data="{ qty: <?= (int)$item['quantity'] ?>, price: <?= (float)$item['current_price'] ?>, stock: <?= (int)$item['stock'] ?> }"
                 x-init="watchItem">
                <a href="<?= e(locale_url('/products/' . $item['slug'])) ?>" class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-stone-100 shrink-0">
                    <img src="<?= e(image_url($item['image'] ?? '', $item['name'] ?? '', 200)) ?>" alt="<?= e($item['name']) ?>" class="w-full h-full object-cover">
                </a>

                <div class="flex-1 min-w-0">
                    <a href="<?= e(locale_url('/products/' . $item['slug'])) ?>" class="font-semibold text-stone-800 text-sm sm:text-base line-clamp-1 hover:text-brand-700"><?= e($item['name']) ?></a>
                    <p class="text-xs text-stone-500 mt-0.5"><?= e(format_price((float)$item['current_price'])) ?> <?= e(trans('unit_price')) ?></p>
                    <?php if ($item['sale_price'] !== null): ?>
                    <p class="text-xs text-stone-400 line-through"><?= e(format_price((float)$item['unit_price'])) ?></p>
                    <?php endif; ?>

                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        <div class="flex items-center rounded-lg border border-stone-200">
                            <button type="button" @click="qty = Math.max(1, qty - 1); $el.closest('form').watchLines()" class="px-3 py-1.5 font-bold text-stone-500 hover:text-brand-700">−</button>
                            <input type="number" name="quantity[<?= (int)$item['product_id'] ?>]" :value="qty" min="1" :max="stock"
                                   class="w-12 text-center text-sm font-bold focus:outline-none bg-transparent" readonly>
                            <button type="button" @click="qty = Math.min(stock, qty + 1); $el.closest('form').watchLines()" class="px-3 py-1.5 font-bold text-stone-500 hover:text-brand-700">+</button>
                        </div>

                        <div class="ml-auto flex items-center gap-3">
                            <span class="font-extrabold text-brand-700" x-text="'<?= app_locale() === 'bn' ? '৳' : '$' ?>' + (price * qty).toFixed(2)">
                                <?= e(format_price($itemTotal)) ?>
                            </span>
                            <button type="submit" formaction="<?= e(locale_url('/cart/remove')) ?>" name="product_id" value="<?= (int)$item['product_id'] ?>"
                                    class="inline-flex items-center gap-1 text-stone-400 hover:text-red-600 text-xs font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <?= e(trans('remove')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <button type="submit" class="rounded-xl border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-600 hover:border-brand-600 hover:text-brand-700"><?= e(trans('update_cart')) ?></button>
                <a href="<?= e(locale_url('/products')) ?>" class="text-sm font-semibold text-brand-700 hover:underline"><?= e(trans('continue_shopping')) ?> →</a>
            </div>
        </form>

        <!-- Summary -->
        <aside class="bg-white rounded-2xl border border-stone-200 p-5 h-fit lg:sticky lg:top-24">
            <h2 class="font-bold text-lg mb-4"><?= e(trans('order_summary')) ?></h2>

            <form method="post" action="<?= e(locale_url('/cart/coupon')) ?>" class="mb-4">
                <?= csrf_field() ?>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('coupon')) ?></label>
                <div class="flex gap-2 mt-1">
                    <input type="text" name="code" placeholder="WELCOME10"
                           class="flex-1 min-w-0 rounded-xl border border-stone-200 px-3 py-2 text-sm uppercase focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <button type="submit" class="rounded-xl bg-stone-800 hover:bg-stone-900 text-white px-4 py-2 text-sm font-semibold"><?= e(trans('apply')) ?></button>
                </div>
                <p class="text-[11px] text-stone-400 mt-1"><?= e(trans('promo_hint')) ?></p>
            </form>

            <div class="space-y-2 text-sm border-t border-stone-100 pt-4">
                <div class="flex justify-between">
                    <span class="text-stone-500"><?= e(trans('subtotal')) ?></span>
                    <span class="font-semibold" x-text="'<?= app_locale() === 'bn' ? '৳' : '$' ?>' + subtotal.toFixed(2)"><?= e(format_price($subtotal)) ?></span>
                </div>
                <div class="flex justify-between" x-show="discount > 0">
                    <span class="text-green-600"><?= e(trans('discount')) ?></span>
                    <span class="font-semibold text-green-600" x-text="'-' + '<?= app_locale() === 'bn' ? '৳' : '$' ?>' + discount.toFixed(2)"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500"><?= e(trans('shipping')) ?></span>
                    <span class="font-semibold" x-show="shipping > 0" x-text="'<?= app_locale() === 'bn' ? '৳' : '$' ?>' + shipping.toFixed(2)"><?= e(format_price($shipping)) ?></span>
                    <span class="font-semibold text-green-600" x-show="shipping === 0"><?= e(trans('shipping_free')) ?></span>
                </div>
                <div class="flex justify-between border-t border-stone-100 pt-3 text-base">
                    <span class="font-bold text-stone-900"><?= e(trans('grand_total')) ?></span>
                    <span class="font-extrabold text-brand-700" x-text="'<?= app_locale() === 'bn' ? '৳' : '$' ?>' + grandTotal.toFixed(2)"><?= e(format_price($total)) ?></span>
                </div>
            </div>

            <a href="<?= e(locale_url('/checkout')) ?>" class="mt-5 block w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-center font-bold px-6 py-3.5 text-sm transition shadow-lg shadow-brand-600/20">
                <?= e(trans('proceed_checkout')) ?> →
            </a>
        </aside>
    </div>
    <?php endif; ?>
</section>

<script>
    function cartSummary() {
        return {
            subtotal: <?= (float)$subtotal ?>,
            discount: <?= (float)$discount ?>,
            shipping: <?= (float)$shipping ?>,
            get grandTotal() {
                return this.subtotal - this.discount + this.shipping;
            },
            watchLines() {
                // Re-compute subtotal from the visible quantity inputs.
                const form = document.querySelector('form[action*="cart/update"]');
                if (!form) return;
                let total = 0;
                form.querySelectorAll('[name^="quantity["]').forEach(input => {
                    const lineBlock = input.closest('[x-data]');
                    const price = parseFloat(lineBlock.querySelector('input[name]').value) >= 0
                        ? parseFloat(lineBlock.getAttribute('x-data').match(/price: ([\d.]+)/)[1]) : 0;
                    total += price * parseInt(input.value || '0', 10);
                });
                this.subtotal = total;
            },
            init() {
                this.watchLines();
            }
        };
    }
</script>
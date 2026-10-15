<!-- BEGIN: CartDrawer -->
<div x-cloak @keydown.escape.window="closeCart()">
    <!-- Backdrop -->
    <div x-show="cartOpen" x-transition.opacity.duration.200ms
         @click="closeCart()"
         class="fixed inset-0 z-[70] bg-black/40"></div>

    <!-- Slide-in panel -->
    <aside x-show="cartOpen"
           x-effect="document.body.style.overflow = cartOpen ? 'hidden' : ''"
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full"
           role="dialog" aria-modal="true" aria-label="<?= e(trans('cart_bag')) ?>"
           class="fixed top-0 right-0 z-[80] flex h-full w-full max-w-sm flex-col bg-stone-50 shadow-2xl">

        <!-- Header -->
        <div class="flex items-center justify-between gap-3 bg-[#142e1c] px-5 py-4 text-white">
            <div class="min-w-0">
                <p class="text-[11px] uppercase tracking-[0.2em] text-white/60"><?= e(trans('cart_bag')) ?></p>
                <p class="text-lg font-semibold leading-tight" x-text="itemsLabel.replace(':count', cartCount)"></p>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
                <button type="button" x-show="cartItems.length > 0" @click="clearCart()"
                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white/70 transition hover:bg-white/10 hover:text-white">
                    <?= e(trans('cart_clear_all')) ?>
                </button>
                <button type="button" @click="closeCart()" aria-label="<?= e(trans('close')) ?>"
                        class="rounded-lg p-1.5 text-white/80 transition hover:bg-white/10 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Items -->
        <div class="flex-1 overflow-y-auto px-4 py-4">
            <template x-if="cartItems.length === 0">
                <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <span class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-brand-700">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                    <p class="text-sm font-semibold text-stone-600"><?= e(trans('cart_empty')) ?></p>
                    <p class="mt-1 text-xs text-stone-400"><?= e(trans('cart_empty_hint')) ?></p>
                    <a href="<?= e(locale_url('/products')) ?>" @click="closeCart()"
                       class="mt-5 inline-flex items-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                        <?= e(trans('continue_shopping')) ?>
                    </a>
                </div>
            </template>

            <div x-show="cartItems.length > 0" class="space-y-3">
                <template x-for="item in cartItems" :key="item.product_id">
                    <div class="rounded-2xl border border-stone-200 bg-white p-3 shadow-sm">
                        <div class="flex gap-3">
                            <a :href="item.url" @click="closeCart()" class="shrink-0">
                                <img :src="item.image" :alt="item.name" loading="lazy"
                                     class="h-16 w-16 rounded-xl bg-stone-100 object-cover">
                            </a>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <a :href="item.url" @click="closeCart()"
                                       class="line-clamp-2 text-sm font-semibold leading-snug text-stone-900 hover:text-brand-700"
                                       x-text="item.name"></a>
                                    <button type="button" @click="removeItem(item.product_id)"
                                            aria-label="<?= e(trans('remove')) ?>"
                                            class="shrink-0 rounded-md p-1 text-stone-400 transition hover:bg-red-50 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-stone-500" x-text="item.price_display"></p>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <div class="flex items-center overflow-hidden rounded-lg border border-stone-200 bg-stone-50">
                                        <button type="button" @click="setQty(item.product_id, item.quantity - 1)"
                                                aria-label="-"
                                                class="px-2.5 py-1 text-base font-semibold leading-none text-stone-600 transition hover:bg-stone-200">−</button>
                                        <span class="min-w-[1.75rem] px-1 text-center text-sm font-medium" x-text="item.quantity"></span>
                                        <button type="button" @click="setQty(item.product_id, item.quantity + 1)"
                                                :disabled="item.quantity >= item.stock"
                                                :class="item.quantity >= item.stock ? 'cursor-not-allowed opacity-40' : 'hover:bg-stone-200'"
                                                aria-label="+"
                                                class="px-2.5 py-1 text-base font-semibold leading-none text-stone-600 transition">+</button>
                                    </div>
                                    <span class="text-sm font-bold text-stone-900" x-text="item.line_display"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Footer -->
        <div x-show="cartItems.length > 0" class="border-t border-stone-200 bg-white px-5 py-4">
            <div class="mb-3 flex items-center justify-between text-sm">
                <span class="text-stone-500"><?= e(trans('subtotal')) ?></span>
                <span class="text-base font-bold text-stone-900" x-text="cartSubtotal || '—'"></span>
            </div>
            <a href="<?= e(locale_url('/checkout')) ?>"
               class="block w-full rounded-xl bg-brand-700 px-4 py-3 text-center text-sm font-bold text-white transition hover:bg-brand-800">
                <?= e(trans('proceed_checkout')) ?>
            </a>
            <a href="<?= e(locale_url('/cart')) ?>"
               class="mt-2 block w-full text-center text-sm font-semibold text-brand-700 hover:underline">
                <?= e(trans('cart_view')) ?>
            </a>
        </div>
    </aside>
</div>
<!-- END: CartDrawer -->

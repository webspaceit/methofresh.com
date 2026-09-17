<?php
$grandTotal = $subtotal - $discount + $shipping;
$defaultName = $user ? user_display_name($user) : '';
$defaultEmail = $user['email'] ?? '';
$defaultPhone = $user['phone'] ?? '';
$defaultAddress = $user['address'] ?? '';
$defaultCity = $user['city'] ?? '';
$defaultPostal = $user['postal_code'] ?? '';
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <a href="<?= e(locale_url('/cart')) ?>" class="hover:text-brand-700"><?= e(trans('nav_cart')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e(trans('checkout')) ?></span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 mb-6"><?= e(trans('checkout')) ?></h1>

    <div class="grid lg:grid-cols-[1fr,380px] gap-8 items-start">

        <!-- Billing form -->
        <form method="post" action="<?= e(locale_url('/checkout')) ?>" x-data="{ loading: false }" @submit="loading = true" class="bg-white rounded-2xl border border-stone-200 p-5 sm:p-6">
            <?= csrf_field() ?>

            <h2 class="font-bold text-lg mb-4"><?= e(trans('billing_details')) ?></h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('full_name')) ?> *</label>
                    <input type="text" name="name" value="<?= e($defaultName) ?>" required
                           class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('email')) ?> *</label>
                    <input type="email" name="email" value="<?= e($defaultEmail) ?>" required
                           class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('phone')) ?> *</label>
                    <input type="tel" name="phone" value="<?= e($defaultPhone) ?>" required placeholder="+8801XXXXXXXXX"
                           class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('address')) ?> *</label>
                    <input type="text" name="address" value="<?= e($defaultAddress) ?>" required
                           class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('city')) ?> *</label>
                    <input type="text" name="city" value="<?= e($defaultCity) ?>" required
                           class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('postal_code')) ?></label>
                    <input type="text" name="postal_code" value="<?= e($defaultPostal) ?>"
                           class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('order_notes')) ?></label>
                    <textarea name="notes" rows="3" placeholder="<?= e(trans('notes_placeholder')) ?>"
                              class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
                </div>
<?php
$paymentMethods = $paymentMethods ?? active_payment_methods();
if (empty($paymentMethods)) {
    $paymentMethods = [
        'cod' => [
            'key' => 'cod',
            'enabled' => true,
            'icon' => '💵',
            'title' => trans('payment_cod'),
            'desc' => '',
            'require_trx' => false,
        ]
    ];
}
$firstMethodKey = array_key_first($paymentMethods);
?>
                <div class="sm:col-span-2" x-data="{ 
                    selectedMethod: '<?= e($firstMethodKey) ?>',
                    copied: false,
                    copyAccount(text) {
                        navigator.clipboard.writeText(text);
                        this.copied = true;
                        setTimeout(() => { this.copied = false }, 2000);
                    }
                }">
                    <label class="text-xs font-semibold text-stone-500 mb-2 block"><?= e(trans('payment_method')) ?> *</label>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <?php foreach ($paymentMethods as $key => $method): ?>
                        <label @click="selectedMethod = '<?= e($key) ?>'"
                               class="flex items-center gap-3 rounded-xl border p-3.5 cursor-pointer transition relative"
                               :class="selectedMethod === '<?= e($key) ?>' ? 'border-brand-600 bg-brand-50/60 ring-2 ring-brand-500/20 shadow-xs' : 'border-stone-200 bg-white hover:border-stone-300'">
                            <input type="radio" name="payment_method" value="<?= e($key) ?>"
                                   x-model="selectedMethod"
                                   class="accent-brand-600 shrink-0">
                            <span class="text-2xl shrink-0"><?= e($method['icon'] ?? '💳') ?></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-stone-900 leading-tight"><?= e($method['title']) ?></p>
                                <?php if (!empty($method['account_type']) && in_array($key, ['bkash', 'nagad', 'rocket'])): ?>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-semibold uppercase rounded-md bg-stone-100 text-stone-600">
                                        <?= e(trans('type_' . $method['account_type']) ?? $method['account_type']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Dynamic Payment Instructions & TrxID Input -->
                    <?php foreach ($paymentMethods as $key => $method): ?>
                    <div x-show="selectedMethod === '<?= e($key) ?>'" x-cloak class="mt-4 rounded-xl border border-stone-200 bg-stone-50/70 p-4 space-y-3 transition">
                        <?php if ($key === 'cod'): ?>
                            <div class="flex items-start gap-2.5 text-xs text-stone-600">
                                <span class="text-base">💵</span>
                                <div>
                                    <p class="font-semibold text-stone-800"><?= e($method['title']) ?></p>
                                    <p class="text-stone-500 mt-0.5"><?= e(!empty($method['desc']) ? $method['desc'] : 'Pay with cash upon delivery of your order.') ?></p>
                                </div>
                            </div>
                        <?php elseif (in_array($key, ['bkash', 'nagad', 'rocket'])): ?>
                            <div class="space-y-2.5 text-xs text-stone-600">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-stone-800"><?= e($method['title']) ?></span>
                                        <?php if (!empty($method['account_type'])): ?>
                                        <span class="ml-1.5 px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-stone-200 text-stone-700">
                                            <?= e(trans('type_' . $method['account_type']) ?? $method['account_type']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($method['account_no'])): ?>
                                    <div class="flex items-center gap-1.5 bg-white border border-stone-200 px-2.5 py-1 rounded-lg">
                                        <span class="font-mono font-bold text-sm text-stone-900 select-all"><?= e($method['account_no']) ?></span>
                                        <button type="button" @click="copyAccount('<?= e($method['account_no']) ?>')" class="text-brand-600 hover:text-brand-800 font-semibold text-[11px] ml-1">
                                            <span x-show="!copied">Copy</span>
                                            <span x-show="copied" class="text-green-600 font-bold" x-cloak>Copied!</span>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($method['desc'])): ?>
                                <p class="text-xs text-stone-600 bg-white/60 p-2.5 rounded-lg border border-stone-200/60 leading-relaxed whitespace-pre-line"><?= e($method['desc']) ?></p>
                                <?php endif; ?>

                                <div class="pt-1">
                                    <label class="block text-xs font-semibold text-stone-700 mb-1">
                                        <?= e(trans('payment_trx_id')) ?> <?= !empty($method['require_trx']) ? '<span class="text-red-500">*</span>' : '<span class="text-stone-400 font-normal">(' . e(trans('optional') ?? 'Optional') . ')</span>' ?>
                                    </label>
                                    <input type="text" name="trx_id"
                                           :disabled="selectedMethod !== '<?= e($key) ?>'"
                                           placeholder="<?= e(trans('payment_trx_id_placeholder')) ?>"
                                           class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                    <p class="text-[11px] text-stone-400 mt-1"><?= e(trans('payment_trx_id_help')) ?></p>
                                </div>
                            </div>
                        <?php elseif ($key === 'bank'): ?>
                            <div class="space-y-3 text-xs text-stone-600">
                                <p class="font-bold text-stone-800 text-sm"><?= e($method['title']) ?></p>
                                <div class="grid sm:grid-cols-2 gap-2 bg-white p-3 rounded-xl border border-stone-200 text-xs">
                                    <?php if (!empty($method['bank_name'])): ?>
                                    <div><span class="text-stone-400 block"><?= e(trans('payment_bank_name')) ?>:</span> <span class="font-bold text-stone-800"><?= e($method['bank_name']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($method['account_name'])): ?>
                                    <div><span class="text-stone-400 block"><?= e(trans('payment_account_name')) ?>:</span> <span class="font-bold text-stone-800"><?= e($method['account_name']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($method['account_no'])): ?>
                                    <div><span class="text-stone-400 block"><?= e(trans('payment_account_number')) ?>:</span> <span class="font-mono font-bold text-stone-900"><?= e($method['account_no']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($method['branch_name'])): ?>
                                    <div><span class="text-stone-400 block"><?= e(trans('payment_branch_name')) ?>:</span> <span class="font-bold text-stone-800"><?= e($method['branch_name']) ?></span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($method['routing_no'])): ?>
                                    <div><span class="text-stone-400 block"><?= e(trans('payment_routing_number')) ?>:</span> <span class="font-mono font-bold text-stone-900"><?= e($method['routing_no']) ?></span></div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($method['desc'])): ?>
                                <p class="text-xs text-stone-600 bg-white/60 p-2.5 rounded-lg border border-stone-200/60 leading-relaxed whitespace-pre-line"><?= e($method['desc']) ?></p>
                                <?php endif; ?>

                                <div class="pt-1">
                                    <label class="block text-xs font-semibold text-stone-700 mb-1">
                                        <?= e(trans('payment_trx_id')) ?> <?= !empty($method['require_trx']) ? '<span class="text-red-500">*</span>' : '<span class="text-stone-400 font-normal">(' . e(trans('optional') ?? 'Optional') . ')</span>' ?>
                                    </label>
                                    <input type="text" name="trx_id"
                                           :disabled="selectedMethod !== '<?= e($key) ?>'"
                                           placeholder="<?= e(trans('payment_trx_id_placeholder')) ?>"
                                           class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                    <p class="text-[11px] text-stone-400 mt-1"><?= e(trans('payment_trx_id_help')) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <p class="mt-4 text-[11px] text-stone-400"><?= e(trans('terms_note')) ?></p>

            <button type="submit"
                    :disabled="loading"
                    class="mt-4 w-full rounded-xl bg-brand-600 hover:bg-brand-700 disabled:bg-stone-300 text-white font-bold px-6 py-3.5 text-sm transition shadow-lg shadow-brand-600/20 cursor-pointer disabled:cursor-not-allowed">
                <span x-show="!loading"><?= e(trans('place_order')) ?> →</span>
                <span x-show="loading" x-cloak><?= e(trans('place_order_loading')) ?>...</span>
            </button>
        </form>

        <!-- Summary -->
        <aside class="bg-white rounded-2xl border border-stone-200 p-5 lg:sticky lg:top-24">
            <h2 class="font-bold text-lg mb-4"><?= e(trans('order_summary')) ?></h2>
            <div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1">
                <?php foreach ($cart as $item): ?>
                <div class="flex items-center gap-3">
                    <div class="relative w-14 h-14 rounded-xl overflow-hidden bg-stone-100 shrink-0">
                        <img src="<?= e(image_url($item['image'] ?? '', $item['name'] ?? '', 100)) ?>" alt="" class="w-full h-full object-cover">
                        <span class="absolute -top-0 -right-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-stone-800 text-white text-[9px] font-bold"><?= e(format_number((int)$item['quantity'])) ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-stone-800 line-clamp-1"><?= e($item['name']) ?></p>
                        <p class="text-xs text-stone-500"><?= e(format_price((float)$item['current_price'])) ?></p>
                    </div>
                    <span class="text-sm font-bold"><?= e(format_price((float)$item['current_price'] * (int)$item['quantity'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="space-y-2 text-sm border-t border-stone-100 mt-4 pt-4">
                <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('subtotal')) ?></span><span class="font-semibold"><?= e(format_price($subtotal)) ?></span></div>
                <?php if ($discount > 0): ?>
                <div class="flex justify-between"><span class="text-green-600"><?= e(trans('discount')) ?></span><span class="font-semibold text-green-600">-<?= e(format_price($discount)) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <span class="text-stone-500"><?= e(trans('shipping')) ?></span>
                    <?php if ($shipping > 0): ?>
                    <span class="font-semibold"><?= e(format_price($shipping)) ?></span>
                    <?php else: ?>
                    <span class="font-semibold text-green-600"><?= e(trans('shipping_free')) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between border-t border-stone-100 pt-3 text-base">
                    <span class="font-bold"><?= e(trans('grand_total')) ?></span>
                    <span class="font-extrabold text-brand-700"><?= e(format_price($grandTotal)) ?></span>
                </div>
            </div>
        </aside>
    </div>
</section>
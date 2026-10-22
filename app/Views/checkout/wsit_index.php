<?php
$grandTotal = $subtotal - $discount + $shipping;

// Details the visitor typed before being asked to sign in win over the saved
// profile, so the form is not thrown away. The e-mail always comes from the
// account once signed in — it is the customer's identity.
$pending = $pending ?? [];
$pendingVal = static function (string $key) use ($pending): string {
    $v = trim((string)($pending[$key] ?? ''));
    return $v;
};
$defaultName = $pendingVal('name') !== ''
    ? $pendingVal('name')
    : ($user ? user_display_name($user) : '');
$defaultEmail = $user['email'] ?? '';
$defaultPhone = $pendingVal('phone') !== '' ? $pendingVal('phone') : (string)($user['phone'] ?? '');
$defaultAddress = $pendingVal('address') !== '' ? $pendingVal('address') : (string)($user['address'] ?? '');
$defaultCity = $pendingVal('city') !== '' ? $pendingVal('city') : (string)($user['city'] ?? '');
$defaultPostal = $pendingVal('postal_code') !== '' ? $pendingVal('postal_code') : (string)($user['postal_code'] ?? '');
$defaultNotes = $pendingVal('notes');
$cities = checkout_cities(); // [] (cleared in dashboard) = text-input fallback

// ---- Delivery methods (conditional by locality + cart weight) -------------
// The payload below drives a client-side preview (availability per city,
// charge = base + per_kg × weight); the POST handler recomputes everything
// server-side and is authoritative.
$shippingCfg = $shippingCfg ?? shipping_config();
$shippingWeight = $shippingWeight ?? cart_total_weight();
$shipJsMethods = [];
$shipInitialId = '';
if (!empty($shippingCfg['enabled'])) {
    foreach ((array)$shippingCfg['methods'] as $sm) {
        if (empty($sm['enabled'])) {
            continue;
        }
        if ($shipInitialId === '') {
            $shipInitialId = $sm['id'];
        }
        $shipJsMethods[] = [
            'id'      => $sm['id'],
            'name'    => shipping_method_name($sm),
            'base'    => (float)$sm['base'],
            'perKg'   => (float)$sm['per_kg'],
            'areas'   => (string)$sm['areas'],
            'exclude' => (string)$sm['exclude'],
        ];
    }
}
$shipPayload = [
    'enabled'     => $shipJsMethods !== [],
    'methods'     => $shipJsMethods,
    'freeOver'    => (float)$shippingCfg['free_over'],
    'subtotal'    => (float)$subtotal,
    'discount'    => (float)$discount,
    'weight'      => (float)$shippingWeight,
    'initialId'   => $shipInitialId,
    'initialCity' => (string)$defaultCity,
    'cityAlts'    => checkout_city_alts(),
    'isBn'        => app_locale() === 'bn',
    'freeText'    => trans('shipping_free'),
];
$shipJson = json_encode($shipPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$shipSection = $shipJsMethods !== [];
$pixelCurrency = app_locale() === 'bn' ? 'BDT' : 'USD';
?>
<?php if (facebook_pixel_enabled() && facebook_pixel_event_enabled('initiate_checkout')): ?>
<script>
(function () {
    if (typeof fbq !== 'function') return;
    const items = [];
    <?php foreach ($cart as $item): ?>
    items.push({
        id: '<?= e($item['product_id']) ?>',
        name: '<?= e($item['name']) ?>',
        quantity: <?= (int)$item['quantity'] ?>,
        price: <?= (float)$item['current_price'] ?>,
    });
    <?php endforeach; ?>
    fbq('track', 'InitiateCheckout', {
        currency: '<?= e($pixelCurrency) ?>',
        value: <?= (float)($subtotal - $discount + $shipping) ?>,
        content_type: 'product',
        contents: items.map(i => ({ id: String(i.id), quantity: i.price > 0 ? i.quantity : 1 })),
    });
})();
</script>
<?php endif; ?>
<script>
// Checkout delivery-method state: city-aware availability + charge preview,
// mirroring shipping_city_matches()/shipping_method_charge() on the server.
function checkoutShip(p) {
    const bnDigits = '০১২৩৪৫৬৭৮৯';
    return {
        loading: false,
        shipCity: p.initialCity || '',
        shipSel: p.initialId || '',
        shipNone: false,
        init() {
            // A city change re-picks the best method for the new locality;
            // manual radio clicks keep their choice until the city changes.
            this.$watch('shipCity', () => this.shipRecalc(true));
            this.$watch('shipSel', () => this.shipRecalc());
            this.shipRecalc(true);
        },
        _kw(s) {
            return String(s || '').split(',').map(x => x.trim().toLowerCase()).filter(Boolean);
        },
        _mById(id) {
            return p.methods.find(m => m.id === id) || null;
        },
        shipAvail(id) {
            const m = this._mById(id);
            if (!m || !p.enabled) return false;
            // Keywords match the selected city AND its counterpart spelling
            // from the dropdown list — a "ঢাকা" area keyword hits city "Dhaka".
            const alts = (p.cityAlts && p.cityAlts[this.shipCity]) || [];
            const hay = [String(this.shipCity || '').trim()].concat(alts).join(' ').toLowerCase();
            const areas = this._kw(m.areas);
            const excl = this._kw(m.exclude);
            if (areas.length && !areas.some(k => hay.indexOf(k) !== -1)) return false;
            if (excl.length && excl.some(k => hay.indexOf(k) !== -1)) return false;
            return true;
        },
        shipCharge(m) {
            if (!m) return 0;
            if (p.freeOver > 0 && p.subtotal >= p.freeOver) return 0;
            const v = Math.round((Number(m.base) + Number(m.perKg) * Number(p.weight)) * 100) / 100;
            return v < 0 ? 0 : v;
        },
        shipFmt(v) {
            const s = Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (p.isBn) return s.replace(/[0-9]/g, (d) => bnDigits[Number(d)]) + ' টাকা';
            return '৳' + s;
        },
        shipLabel(id) {
            const c = this.shipCharge(this._mById(id));
            return c > 0 ? this.shipFmt(c) : p.freeText;
        },
        // Most specific available method for the current city: methods
        // positively targeted at a locality (areas match) win, then methods
        // filtered with exclude, then "everywhere" methods; ties keep config
        // order. With no city chosen yet, an "everywhere" method wins.
        _bestMethod(avail) {
            const known = String(this.shipCity || '').trim() !== '';
            const rank = (m) => {
                const targeted = this._kw(m.areas).length > 0;
                const filtered = this._kw(m.exclude).length > 0;
                if (!known) return (!targeted && !filtered) ? 1 : 0;
                if (targeted) return 2;
                if (filtered) return 1;
                return 0;
            };
            let best = avail[0];
            let bestRank = -1;
            for (const m of avail) {
                const r = rank(m);
                if (r > bestRank) {
                    best = m;
                    bestRank = r;
                }
            }
            return best;
        },
        shipRecalc(reselect) {
            const avail = p.methods.filter(m => this.shipAvail(m.id));
            this.shipNone = p.enabled && p.methods.length > 0 && avail.length === 0;
            if (reselect || !avail.some(m => m.id === this.shipSel)) {
                this.shipSel = avail.length ? this._bestMethod(avail).id : '';
            }
            const charge = avail.length ? this.shipCharge(this._mById(this.shipSel)) : 0;

            const shipEl = document.getElementById('sum-ship');
            if (shipEl) {
                shipEl.textContent = charge > 0 ? this.shipFmt(charge) : p.freeText;
                shipEl.className = charge > 0 ? 'font-semibold' : 'font-semibold text-green-600';
            }
            const totalEl = document.getElementById('sum-total');
            if (totalEl) {
                totalEl.textContent = this.shipFmt(p.subtotal - p.discount + charge);
            }
        }
    };
}
</script>
<section class="max-w-7xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <a href="<?= e(locale_url('/cart')) ?>" class="hover:text-brand-700"><?= e(trans('nav_cart')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e(trans('checkout')) ?></span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-800 mb-6"><?= e(trans('checkout')) ?></h1>

    <div class="grid lg:grid-cols-[1fr,380px] gap-8 items-start">

        <!-- Billing form -->
        <form method="post" action="<?= e(locale_url('/checkout')) ?>" x-data='checkoutShip(<?= $shipJson ?>)' @submit="loading = true" class="bg-card rounded-2xl border border-honey-600/15 p-5 sm:p-6">
            <?= csrf_field() ?>

            <h2 class="font-bold text-lg mb-4"><?= e(trans('billing_details')) ?></h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('full_name')) ?> *</label>
                    <input type="text" name="name" value="<?= e($defaultName) ?>" required
                           class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('email')) ?> *</label>
                    <?php if ($user): ?>
                    <input type="email" name="email" value="<?= e($defaultEmail) ?>" required readonly
                           class="mt-1 w-full rounded-xl border border-honey-600/15 bg-stone-50 px-3.5 py-2.5 text-sm text-stone-600 cursor-not-allowed">
                    <p class="mt-1 text-xs text-stone-500"><?= e(trans('checkout_email_from_account')) ?></p>
                    <?php else: ?>
                    <input type="email" name="email" value="<?= e($defaultEmail) ?>" required
                           class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <p class="mt-1 text-xs text-stone-500">
                        <?= e(trans('checkout_have_account')) ?>
                        <a href="<?= e(locale_url('/login?redirect=' . urlencode('/checkout'))) ?>"
                           class="font-semibold text-brand-700 hover:underline"><?= e(trans('sign_in')) ?></a>
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('phone')) ?> *</label>
                    <input type="tel" name="phone" value="<?= e($defaultPhone) ?>" required placeholder="+8801XXXXXXXXX"
                           class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('address')) ?> *</label>
                    <input type="text" name="address" value="<?= e($defaultAddress) ?>" required
                           class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('city')) ?> *</label>
                    <?php if ($cities !== []): ?>
                    <?php
                    // Option values follow the current locale; a stored profile
                    // city outside the list is appended so its saved value
                    // still shows as selected.
                    $cityOptions = [];
                    foreach ($cities as $c) {
                        $v = app_locale() === 'bn' ? (string)$c['name_bn'] : (string)$c['name_en'];
                        if ($v === '') {
                            $v = (string)$c['name_bn'] !== '' ? (string)$c['name_bn'] : (string)$c['name_en'];
                        }
                        if ($v !== '') {
                            $cityOptions[] = $v;
                        }
                    }
                    $cityExtra = ($defaultCity !== '' && !in_array($defaultCity, $cityOptions, true)) ? $defaultCity : null;
                    ?>
                    <select name="city" required x-model="shipCity"
                            class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="" disabled <?= $defaultCity === '' ? 'selected' : '' ?>><?= e(trans('city_placeholder')) ?></option>
                        <?php foreach ($cityOptions as $cv): ?>
                        <option value="<?= e($cv) ?>" <?= $defaultCity === $cv ? 'selected' : '' ?>><?= e($cv) ?></option>
                        <?php endforeach; ?>
                        <?php if ($cityExtra !== null): ?>
                        <option value="<?= e($cityExtra) ?>" selected><?= e($cityExtra) ?></option>
                        <?php endif; ?>
                    </select>
                    <?php else: ?>
                    <!-- Dashboard list cleared = free-text fallback -->
                    <input type="text" name="city" value="<?= e($defaultCity) ?>" required x-model="shipCity"
                           class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <?php endif; ?>
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('postal_code')) ?></label>
                    <input type="text" name="postal_code" value="<?= e($defaultPostal) ?>"
                           class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('order_notes')) ?></label>
                    <textarea name="notes" rows="3" placeholder="<?= e(trans('notes_placeholder')) ?>"
                              class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($defaultNotes) ?></textarea>
                </div>
<?php if ($shipSection): ?>
                <!-- Delivery method: availability + charge depend on the city
                     above and the cart weight; recomputed live, re-validated
                     server-side on submit. -->
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-stone-500 mb-2 block"><?= e(trans('delivery_method')) ?></label>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <?php foreach ($shipJsMethods as $sm):
                            $smAlways = trim($sm['areas']) === '' && trim($sm['exclude']) === '';
                            $smFreeOver = (float)$shippingCfg['free_over'];
                            $smCharge = ($smFreeOver > 0 && (float)$subtotal >= $smFreeOver)
                                ? 0.0
                                : round(max(0.0, (float)$sm['base'] + (float)$sm['perKg'] * (float)$shippingWeight), 2);
                        ?>
                        <label @click="shipSel = '<?= e($sm['id']) ?>'"
                               <?php if (!$smAlways): ?>x-show="shipAvail('<?= e($sm['id']) ?>')" <?php endif; ?>
                               class="flex items-center gap-3 rounded-xl border p-3.5 cursor-pointer transition relative"
                               :class="shipSel === '<?= e($sm['id']) ?>' ? 'border-brand-600 bg-brand-50/60 ring-2 ring-brand-500/20 shadow-xs' : 'border-honey-600/15 bg-card hover:border-brand-400'">
                            <input type="radio" name="shipping_method" value="<?= e($sm['id']) ?>"
                                   x-model="shipSel" <?= $sm['id'] === $shipInitialId ? 'checked' : '' ?>
                                   class="accent-brand-600 shrink-0">
                            <div class="min-w-0 flex-1 flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-brand-800 leading-tight"><?= e($sm['name']) ?></p>
                                <span class="text-sm font-bold text-stone-700 whitespace-nowrap"
                                      x-text="shipLabel('<?= e($sm['id']) ?>')"><?= e($smCharge > 0 ? format_price($smCharge) : trans('shipping_free')) ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p x-show="shipNone" x-cloak class="text-xs font-semibold text-red-600 mt-2"><?= e(trans('delivery_method_none')) ?></p>
                </div>
<?php endif; ?>
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
                               :class="selectedMethod === '<?= e($key) ?>' ? 'border-brand-600 bg-brand-50/60 ring-2 ring-brand-500/20 shadow-xs' : 'border-honey-600/15 bg-card hover:border-brand-400'">
                            <input type="radio" name="payment_method" value="<?= e($key) ?>"
                                   x-model="selectedMethod"
                                   class="accent-brand-600 shrink-0">
                            <?php $pmIcon = payment_method_icon($method); ?>
                            <?php if ($pmIcon['type'] === 'img'): ?>
                            <img src="<?= e($pmIcon['src']) ?>" alt="<?= e($method['title'] ?? '') ?>" class="w-8 h-8 object-contain shrink-0">
                            <?php else: ?>
                            <span class="text-2xl shrink-0" aria-hidden="true"><?= e($pmIcon['src']) ?></span>
                            <?php endif; ?>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-brand-800 leading-tight"><?= e($method['title']) ?></p>
                                <?php if (!empty($method['account_type']) && in_array($key, ['bkash', 'nagad', 'rocket'])): ?>
                                    <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-semibold uppercase rounded-md bg-honey-100/40 text-stone-600">
                                        <?= e(trans('type_' . $method['account_type']) ?? $method['account_type']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Dynamic Payment Instructions & TrxID Input -->
                    <?php foreach ($paymentMethods as $key => $method): ?>
                    <div x-show="selectedMethod === '<?= e($key) ?>'" x-cloak class="mt-4 rounded-xl border border-honey-600/15 bg-cream/70 p-4 space-y-3 transition">
                        <?php if ($key === 'cod'): ?>
                            <!-- The name is already on the card above, so the
                                 panel only carries the explanation. -->
                            <p class="text-xs text-stone-600 leading-relaxed"><?= e(!empty($method['desc']) ? $method['desc'] : 'Pay with cash upon delivery of your order.') ?></p>
                        <?php elseif (in_array($key, ['bkash', 'nagad', 'rocket'])): ?>
                            <div class="space-y-2.5 text-xs text-stone-600">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-stone-800"><?= e(trans('payment_send_money_to')) ?></span>
                                        <?php if (!empty($method['account_type'])): ?>
                                        <span class="ml-1.5 px-2 py-0.5 text-[10px] font-semibold uppercase rounded bg-stone-200 text-stone-700">
                                            <?= e(trans('type_' . $method['account_type']) ?? $method['account_type']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($method['account_no'])): ?>
                                    <div class="flex items-center gap-1.5 bg-card border border-honey-600/15 px-2.5 py-1 rounded-lg">
                                        <span class="font-mono font-bold text-sm text-stone-900 select-all"><?= e($method['account_no']) ?></span>
                                        <button type="button" @click="copyAccount('<?= e($method['account_no']) ?>')" class="text-brand-600 hover:text-brand-800 font-semibold text-[11px] ml-1">
                                            <span x-show="!copied">Copy</span>
                                            <span x-show="copied" class="text-green-600 font-bold" x-cloak>Copied!</span>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($method['desc'])): ?>
                                <p class="text-xs text-stone-600 bg-card/60 p-2.5 rounded-lg border border-honey-600/15/60 leading-relaxed whitespace-pre-line"><?= e($method['desc']) ?></p>
                                <?php endif; ?>

                                <div class="pt-1">
                                    <label class="block text-xs font-semibold text-stone-700 mb-1">
                                        <?= e(trans('payment_trx_id')) ?> <?= !empty($method['require_trx']) ? '<span class="text-red-500">*</span>' : '<span class="text-stone-400 font-normal">(' . e(trans('optional') ?? 'Optional') . ')</span>' ?>
                                    </label>
                                    <input type="text" name="trx_id"
                                           :disabled="selectedMethod !== '<?= e($key) ?>'"
                                           placeholder="<?= e(trans('payment_trx_id_placeholder')) ?>"
                                           class="w-full rounded-xl border border-honey-600/15 bg-card px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                    <p class="text-[11px] text-stone-400 mt-1"><?= e(trans('payment_trx_id_help')) ?></p>
                                </div>
                            </div>
                        <?php elseif ($key === 'bank'): ?>
                            <div class="space-y-3 text-xs text-stone-600">
                                <div class="grid sm:grid-cols-2 gap-2 bg-card p-3 rounded-xl border border-honey-600/15 text-xs">
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
                                <p class="text-xs text-stone-600 bg-card/60 p-2.5 rounded-lg border border-honey-600/15/60 leading-relaxed whitespace-pre-line"><?= e($method['desc']) ?></p>
                                <?php endif; ?>

                                <div class="pt-1">
                                    <label class="block text-xs font-semibold text-stone-700 mb-1">
                                        <?= e(trans('payment_trx_id')) ?> <?= !empty($method['require_trx']) ? '<span class="text-red-500">*</span>' : '<span class="text-stone-400 font-normal">(' . e(trans('optional') ?? 'Optional') . ')</span>' ?>
                                    </label>
                                    <input type="text" name="trx_id"
                                           :disabled="selectedMethod !== '<?= e($key) ?>'"
                                           placeholder="<?= e(trans('payment_trx_id_placeholder')) ?>"
                                           class="w-full rounded-xl border border-honey-600/15 bg-card px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
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
                    :disabled="loading || shipNone"
                    class="mt-4 w-full rounded-xl bg-brand-600 hover:bg-brand-700 disabled:bg-stone-300 text-white font-bold px-6 py-3.5 text-sm transition shadow-lg shadow-brand-600/20 cursor-pointer disabled:cursor-not-allowed">
                <span x-show="!loading"><?= e(trans('place_order')) ?> →</span>
                <span x-show="loading" x-cloak><?= e(trans('place_order_loading')) ?>...</span>
            </button>
        </form>

        <!-- Summary -->
        <aside class="bg-card rounded-2xl border border-honey-600/15 p-5 lg:sticky lg:top-24">
            <h2 class="font-bold text-lg mb-4"><?= e(trans('order_summary')) ?></h2>
            <div class="space-y-3 max-h-[40vh] overflow-y-auto pr-1">
                <?php foreach ($cart as $item): ?>
                <div class="flex items-center gap-3">
                    <div class="relative w-14 h-14 rounded-xl overflow-hidden bg-honey-100/40 shrink-0">
                        <img src="<?= e(image_url($item['image'] ?? '', $item['name'] ?? '', 100)) ?>" alt="" class="w-full h-full object-cover">
                        <span class="absolute -top-0 -right-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand-700 text-white text-[9px] font-bold"><?= e(format_number((int)$item['quantity'])) ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-stone-800 line-clamp-1"><?= e($item['name']) ?></p>
                        <p class="text-xs text-stone-500"><?= e(format_price((float)$item['current_price'])) ?></p>
                    </div>
                    <span class="text-sm font-bold"><?= e(format_price((float)$item['current_price'] * (int)$item['quantity'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="space-y-2 text-sm border-t border-honey-600/15 mt-4 pt-4">
                <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('subtotal')) ?></span><span class="font-semibold"><?= e(format_price($subtotal)) ?></span></div>
                <?php if ($discount > 0): ?>
                <div class="flex justify-between"><span class="text-green-600"><?= e(trans('discount')) ?></span><span class="font-semibold text-green-600">-<?= e(format_price($discount)) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <span class="text-stone-500"><?= e(trans('shipping')) ?></span>
                    <?php if ($shipping > 0): ?>
                    <span class="font-semibold" id="sum-ship"><?= e(format_price($shipping)) ?></span>
                    <?php else: ?>
                    <span class="font-semibold text-green-600" id="sum-ship"><?= e(trans('shipping_free')) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between border-t border-honey-600/15 pt-3 text-base">
                    <span class="font-bold"><?= e(trans('grand_total')) ?></span>
                    <span class="font-extrabold text-brand-700" id="sum-total"><?= e(format_price($grandTotal)) ?></span>
                </div>
            </div>
        </aside>
    </div>
</section>
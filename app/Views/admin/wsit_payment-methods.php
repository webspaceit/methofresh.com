<?php
// Payment Methods Configuration Admin View
// Expects: $methods (array of all payment methods config)
?>
<?php
/**
 * Mobile wallets are never offered at checkout while their merchant number
 * is empty - there would be nowhere for the customer to send the money. The
 * dashboard says so out loud, otherwise a switched-on method looks broken.
 */
$needsNumber = static function (array $methods, string $key): bool {
    return !empty($methods[$key]['enabled'])
        && trim((string)($methods[$key]['account_no'] ?? '')) === '';
};
$walletsNeedingNumber = [];
foreach (['bkash', 'nagad', 'rocket'] as $walletKey) {
    if ($needsNumber($methods, $walletKey)) {
        $walletsNeedingNumber[$walletKey] = trans('payment_method_' . $walletKey);
    }
}
?>
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_payment_methods')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_payment_methods_desc')) ?></p>
    </div>
</div>

<?php if ($walletsNeedingNumber): ?>
<!-- These are switched on but stay hidden at checkout until a merchant
     number is filled in. -->
<div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    <span class="material-symbols-outlined text-[20px] shrink-0 text-amber-700">info</span>
    <div>
        <p class="font-bold"><?= e(trans('payment_hidden_title')) ?></p>
        <p class="mt-0.5 leading-relaxed"><?= e(trans('payment_hidden_body', ['methods' => implode(', ', $walletsNeedingNumber)])) ?></p>
    </div>
</div>
<?php endif; ?>

<form method="post" action="<?= e(locale_url('/mf-dashboard/payment-methods')) ?>" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
    <?= csrf_field() ?>

    <!-- ==========================================
         1. CASH ON DELIVERY (COD)
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-4 bg-stone-50/60">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-2xl">💵</span>
                <div>
                    <h2 class="font-bold text-stone-900 text-base"><?= e(trans('payment_method_cod')) ?></h2>
                    <p class="text-xs text-stone-500">Pay cash upon delivery</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="methods[cod][enabled]" value="1" class="sr-only peer"
                       <?= !empty($methods['cod']['enabled']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-stone-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
            </label>
        </div>

        <div class="p-5 space-y-4">
            <!-- Icon Upload -->
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-2"><?= e(trans('admin_payment_icon')) ?></label>
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl border-2 border-stone-200 flex items-center justify-center overflow-hidden bg-white">
                        <?php $codIcon = $methods['cod']['custom_icon'] ?? ''; ?>
                        <?php $codPreview = payment_method_icon($methods['cod']); ?>
                        <?php if ($codPreview['type'] === 'img'): ?>
                            <img src="<?= e($codPreview['src']) ?>" alt="COD Icon" class="w-full h-full object-contain">
                        <?php else: ?>
                            <span class="text-3xl"><?= e($codPreview['src']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="icons[cod]" accept="image/*" class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-stone-400 mt-1">SVG, PNG or JPG (max 500x500px)</p>
                        <?php if ($codIcon): ?>
                            <label class="inline-flex items-center gap-2 mt-2 text-xs text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_icon[cod]" value="1" class="rounded border-red-300">
                                <span>Remove custom icon</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_name_en')) ?></label>
                    <input type="text" name="methods[cod][title_en]" id="cod_title_en"
                           value="<?= e($methods['cod']['title_en'] ?? 'Cash on Delivery') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('admin_name_bn')) ?></label>
                        <button type="button" onclick="bnTranslate(this, '#cod_title_en', '#cod_title_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <input type="text" name="methods[cod][title_bn]" id="cod_title_bn"
                           value="<?= e($methods['cod']['title_bn'] ?? 'ক্যাশ অন ডেলিভারি') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_instructions')) ?> (EN)</label>
                    <textarea name="methods[cod][desc_en]" id="cod_desc_en" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['cod']['desc_en'] ?? '') ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('payment_instructions')) ?> (BN)</label>
                        <button type="button" onclick="bnTranslate(this, '#cod_desc_en', '#cod_desc_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <textarea name="methods[cod][desc_bn]" id="cod_desc_bn" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['cod']['desc_bn'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>


    <!-- ==========================================
         2. BKASH
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-4 bg-pink-50/50">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-pink-100 text-2xl">📱</span>
                <div>
                    <h2 class="font-bold text-stone-900 text-base"><?= e(trans('payment_method_bkash')) ?></h2>
                    <p class="text-xs text-stone-500">bKash Send Money / Payment</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="methods[bkash][enabled]" value="1" class="sr-only peer"
                       <?= !empty($methods['bkash']['enabled']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-stone-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
            </label>
        </div>

        <div class="p-5 space-y-4">
            <!-- Icon Upload -->
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-2"><?= e(trans('admin_payment_icon')) ?></label>
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl border-2 border-stone-200 flex items-center justify-center overflow-hidden bg-white">
                        <?php $bkashIcon = $methods['bkash']['custom_icon'] ?? ''; ?>
                        <?php $bkashPreview = payment_method_icon($methods['bkash']); ?>
                        <?php if ($bkashPreview['type'] === 'img'): ?>
                            <img src="<?= e($bkashPreview['src']) ?>" alt="bKash Icon" class="w-full h-full object-contain">
                        <?php else: ?>
                            <span class="text-3xl"><?= e($bkashPreview['src']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="icons[bkash]" accept="image/*" class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-stone-400 mt-1">SVG, PNG or JPG (max 500x500px)</p>
                        <?php if ($bkashIcon): ?>
                            <label class="inline-flex items-center gap-2 mt-2 text-xs text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_icon[bkash]" value="1" class="rounded border-red-300">
                                <span>Remove custom icon</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_number')) ?></label>
                    <input type="text" name="methods[bkash][account_no]" placeholder="017XXXXXXXX"
                           value="<?= e($methods['bkash']['account_no'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <?php if ($needsNumber($methods, 'bkash')): ?>
                    <p class="mt-1.5 flex items-start gap-1.5 text-xs text-amber-700">
                        <span class="material-symbols-outlined text-[14px] shrink-0">warning</span>
                        <span><?= e(trans('payment_number_required_hint')) ?></span>
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_type')) ?></label>
                    <select name="methods[bkash][account_type]"
                            class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="personal" <?= ($methods['bkash']['account_type'] ?? 'personal') === 'personal' ? 'selected' : '' ?>><?= e(trans('type_personal')) ?></option>
                        <option value="merchant" <?= ($methods['bkash']['account_type'] ?? '') === 'merchant' ? 'selected' : '' ?>><?= e(trans('type_merchant')) ?></option>
                        <option value="agent" <?= ($methods['bkash']['account_type'] ?? '') === 'agent' ? 'selected' : '' ?>><?= e(trans('type_agent')) ?></option>
                    </select>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_name_en')) ?></label>
                    <input type="text" name="methods[bkash][title_en]" id="bkash_title_en"
                           value="<?= e($methods['bkash']['title_en'] ?? 'bKash') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('admin_name_bn')) ?></label>
                        <button type="button" onclick="bnTranslate(this, '#bkash_title_en', '#bkash_title_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <input type="text" name="methods[bkash][title_bn]" id="bkash_title_bn"
                           value="<?= e($methods['bkash']['title_bn'] ?? 'বিকাশ') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_instructions')) ?> (EN)</label>
                    <textarea name="methods[bkash][desc_en]" id="bkash_desc_en" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['bkash']['desc_en'] ?? '') ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('payment_instructions')) ?> (BN)</label>
                        <button type="button" onclick="bnTranslate(this, '#bkash_desc_en', '#bkash_desc_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <textarea name="methods[bkash][desc_bn]" id="bkash_desc_bn" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['bkash']['desc_bn'] ?? '') ?></textarea>
                </div>
            </div>

            <label class="flex items-center gap-2.5 pt-2 text-xs font-medium text-stone-700 cursor-pointer">
                <input type="checkbox" name="methods[bkash][require_trx]" value="1"
                       <?= !empty($methods['bkash']['require_trx']) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-stone-300">
                <span><?= e(trans('payment_require_trx')) ?></span>
            </label>
        </div>
    </div>


    <!-- ==========================================
         3. NAGAD
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-honey-600/15 flex items-center justify-between gap-4 bg-honey-100/40">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-honey-100 text-2xl">💳</span>
                <div>
                    <h2 class="font-bold text-stone-900 text-base"><?= e(trans('payment_method_nagad')) ?></h2>
                    <p class="text-xs text-stone-500">Nagad Mobile Banking</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="methods[nagad][enabled]" value="1" class="sr-only peer"
                       <?= !empty($methods['nagad']['enabled']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-stone-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
            </label>
        </div>

        <div class="p-5 space-y-4">
            <!-- Icon Upload -->
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-2"><?= e(trans('admin_payment_icon')) ?></label>
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl border-2 border-stone-200 flex items-center justify-center overflow-hidden bg-white">
                        <?php $nagadIcon = $methods['nagad']['custom_icon'] ?? ''; ?>
                        <?php $nagadPreview = payment_method_icon($methods['nagad']); ?>
                        <?php if ($nagadPreview['type'] === 'img'): ?>
                            <img src="<?= e($nagadPreview['src']) ?>" alt="Nagad Icon" class="w-full h-full object-contain">
                        <?php else: ?>
                            <span class="text-3xl"><?= e($nagadPreview['src']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="icons[nagad]" accept="image/*" class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-stone-400 mt-1">SVG, PNG or JPG (max 500x500px)</p>
                        <?php if ($nagadIcon): ?>
                            <label class="inline-flex items-center gap-2 mt-2 text-xs text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_icon[nagad]" value="1" class="rounded border-red-300">
                                <span>Remove custom icon</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_number')) ?></label>
                    <input type="text" name="methods[nagad][account_no]" placeholder="018XXXXXXXX"
                           value="<?= e($methods['nagad']['account_no'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <?php if ($needsNumber($methods, 'nagad')): ?>
                    <p class="mt-1.5 flex items-start gap-1.5 text-xs text-amber-700">
                        <span class="material-symbols-outlined text-[14px] shrink-0">warning</span>
                        <span><?= e(trans('payment_number_required_hint')) ?></span>
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_type')) ?></label>
                    <select name="methods[nagad][account_type]"
                            class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="personal" <?= ($methods['nagad']['account_type'] ?? 'personal') === 'personal' ? 'selected' : '' ?>><?= e(trans('type_personal')) ?></option>
                        <option value="merchant" <?= ($methods['nagad']['account_type'] ?? '') === 'merchant' ? 'selected' : '' ?>><?= e(trans('type_merchant')) ?></option>
                    </select>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_name_en')) ?></label>
                    <input type="text" name="methods[nagad][title_en]" id="nagad_title_en"
                           value="<?= e($methods['nagad']['title_en'] ?? 'Nagad') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('admin_name_bn')) ?></label>
                        <button type="button" onclick="bnTranslate(this, '#nagad_title_en', '#nagad_title_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <input type="text" name="methods[nagad][title_bn]" id="nagad_title_bn"
                           value="<?= e($methods['nagad']['title_bn'] ?? 'নগদ') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_instructions')) ?> (EN)</label>
                    <textarea name="methods[nagad][desc_en]" id="nagad_desc_en" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['nagad']['desc_en'] ?? '') ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('payment_instructions')) ?> (BN)</label>
                        <button type="button" onclick="bnTranslate(this, '#nagad_desc_en', '#nagad_desc_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <textarea name="methods[nagad][desc_bn]" id="nagad_desc_bn" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['nagad']['desc_bn'] ?? '') ?></textarea>
                </div>
            </div>

            <label class="flex items-center gap-2.5 pt-2 text-xs font-medium text-stone-700 cursor-pointer">
                <input type="checkbox" name="methods[nagad][require_trx]" value="1"
                       <?= !empty($methods['nagad']['require_trx']) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-stone-300">
                <span><?= e(trans('payment_require_trx')) ?></span>
            </label>
        </div>
    </div>


    <!-- ==========================================
         4. ROCKET (DBBL)
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-4 bg-purple-50/50">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-2xl">🚀</span>
                <div>
                    <h2 class="font-bold text-stone-900 text-base"><?= e(trans('payment_method_rocket')) ?></h2>
                    <p class="text-xs text-stone-500">Dutch-Bangla Bank Rocket</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="methods[rocket][enabled]" value="1" class="sr-only peer"
                       <?= !empty($methods['rocket']['enabled']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-stone-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
            </label>
        </div>

        <div class="p-5 space-y-4">
            <!-- Icon Upload -->
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-2"><?= e(trans('admin_payment_icon')) ?></label>
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl border-2 border-stone-200 flex items-center justify-center overflow-hidden bg-white">
                        <?php $rocketIcon = $methods['rocket']['custom_icon'] ?? ''; ?>
                        <?php $rocketPreview = payment_method_icon($methods['rocket']); ?>
                        <?php if ($rocketPreview['type'] === 'img'): ?>
                            <img src="<?= e($rocketPreview['src']) ?>" alt="Rocket Icon" class="w-full h-full object-contain">
                        <?php else: ?>
                            <span class="text-3xl"><?= e($rocketPreview['src']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="icons[rocket]" accept="image/*" class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-stone-400 mt-1">SVG, PNG or JPG (max 500x500px)</p>
                        <?php if ($rocketIcon): ?>
                            <label class="inline-flex items-center gap-2 mt-2 text-xs text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_icon[rocket]" value="1" class="rounded border-red-300">
                                <span>Remove custom icon</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_number')) ?></label>
                    <input type="text" name="methods[rocket][account_no]" placeholder="019XXXXXXXXX"
                           value="<?= e($methods['rocket']['account_no'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <?php if ($needsNumber($methods, 'rocket')): ?>
                    <p class="mt-1.5 flex items-start gap-1.5 text-xs text-amber-700">
                        <span class="material-symbols-outlined text-[14px] shrink-0">warning</span>
                        <span><?= e(trans('payment_number_required_hint')) ?></span>
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_type')) ?></label>
                    <select name="methods[rocket][account_type]"
                            class="w-full rounded-xl border border-stone-200 bg-white px-3.5 py-2.5 text-sm font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="personal" <?= ($methods['rocket']['account_type'] ?? 'personal') === 'personal' ? 'selected' : '' ?>><?= e(trans('type_personal')) ?></option>
                        <option value="merchant" <?= ($methods['rocket']['account_type'] ?? '') === 'merchant' ? 'selected' : '' ?>><?= e(trans('type_merchant')) ?></option>
                    </select>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_name_en')) ?></label>
                    <input type="text" name="methods[rocket][title_en]" id="rocket_title_en"
                           value="<?= e($methods['rocket']['title_en'] ?? 'Rocket') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('admin_name_bn')) ?></label>
                        <button type="button" onclick="bnTranslate(this, '#rocket_title_en', '#rocket_title_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <input type="text" name="methods[rocket][title_bn]" id="rocket_title_bn"
                           value="<?= e($methods['rocket']['title_bn'] ?? 'রকেট') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_instructions')) ?> (EN)</label>
                    <textarea name="methods[rocket][desc_en]" id="rocket_desc_en" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['rocket']['desc_en'] ?? '') ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('payment_instructions')) ?> (BN)</label>
                        <button type="button" onclick="bnTranslate(this, '#rocket_desc_en', '#rocket_desc_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <textarea name="methods[rocket][desc_bn]" id="rocket_desc_bn" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['rocket']['desc_bn'] ?? '') ?></textarea>
                </div>
            </div>

            <label class="flex items-center gap-2.5 pt-2 text-xs font-medium text-stone-700 cursor-pointer">
                <input type="checkbox" name="methods[rocket][require_trx]" value="1"
                       <?= !empty($methods['rocket']['require_trx']) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-stone-300">
                <span><?= e(trans('payment_require_trx')) ?></span>
            </label>
        </div>
    </div>


    <!-- ==========================================
         5. BANK TRANSFER
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-4 bg-sky-50/50">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-2xl">🏦</span>
                <div>
                    <h2 class="font-bold text-stone-900 text-base"><?= e(trans('payment_method_bank')) ?></h2>
                    <p class="text-xs text-stone-500">Direct Corporate Bank Transfer / Deposit</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="methods[bank][enabled]" value="1" class="sr-only peer"
                       <?= !empty($methods['bank']['enabled']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-stone-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
            </label>
        </div>

        <div class="p-5 space-y-4">
            <!-- Icon Upload -->
            <div>
                <label class="block text-xs font-semibold text-stone-600 mb-2"><?= e(trans('admin_payment_icon')) ?></label>
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0 w-16 h-16 rounded-xl border-2 border-stone-200 flex items-center justify-center overflow-hidden bg-white">
                        <?php $bankIcon = $methods['bank']['custom_icon'] ?? ''; ?>
                        <?php $bankPreview = payment_method_icon($methods['bank']); ?>
                        <?php if ($bankPreview['type'] === 'img'): ?>
                            <img src="<?= e($bankPreview['src']) ?>" alt="Bank Transfer Icon" class="w-full h-full object-contain">
                        <?php else: ?>
                            <span class="text-3xl"><?= e($bankPreview['src']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="icons[bank]" accept="image/*" class="block w-full text-sm text-stone-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-stone-400 mt-1">SVG, PNG or JPG (max 500x500px)</p>
                        <?php if ($bankIcon): ?>
                            <label class="inline-flex items-center gap-2 mt-2 text-xs text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_icon[bank]" value="1" class="rounded border-red-300">
                                <span>Remove custom icon</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_bank_name')) ?></label>
                    <input type="text" name="methods[bank][bank_name]" placeholder="e.g. City Bank PLC / Dutch-Bangla Bank"
                           value="<?= e($methods['bank']['bank_name'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_name')) ?></label>
                    <input type="text" name="methods[bank][account_name]" placeholder="e.g. Metho Fresh Enterprise"
                           value="<?= e($methods['bank']['account_name'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_account_number')) ?></label>
                    <input type="text" name="methods[bank][account_no]" placeholder="e.g. 150XXXXXXXXXX"
                           value="<?= e($methods['bank']['account_no'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_branch_name')) ?></label>
                    <input type="text" name="methods[bank][branch_name]" placeholder="e.g. Chuadanga Branch"
                           value="<?= e($methods['bank']['branch_name'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_routing_number')) ?></label>
                    <input type="text" name="methods[bank][routing_no]" placeholder="e.g. 095XXXXXXXX"
                           value="<?= e($methods['bank']['routing_no'] ?? '') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-mono focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_name_en')) ?></label>
                    <input type="text" name="methods[bank][title_en]" id="bank_title_en"
                           value="<?= e($methods['bank']['title_en'] ?? 'Bank Transfer') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('admin_name_bn')) ?></label>
                        <button type="button" onclick="bnTranslate(this, '#bank_title_en', '#bank_title_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <input type="text" name="methods[bank][title_bn]" id="bank_title_bn"
                           value="<?= e($methods['bank']['title_bn'] ?? 'ব্যাংক ট্রান্সফার') ?>"
                           class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('payment_instructions')) ?> (EN)</label>
                    <textarea name="methods[bank][desc_en]" id="bank_desc_en" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['bank']['desc_en'] ?? '') ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-stone-600"><?= e(trans('payment_instructions')) ?> (BN)</label>
                        <button type="button" onclick="bnTranslate(this, '#bank_desc_en', '#bank_desc_bn')"
                                class="text-[11px] font-semibold text-brand-700 hover:underline">
                            <?= e(trans('admin_translate_btn')) ?>
                        </button>
                    </div>
                    <textarea name="methods[bank][desc_bn]" id="bank_desc_bn" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3.5 py-2 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($methods['bank']['desc_bn'] ?? '') ?></textarea>
                </div>
            </div>

            <label class="flex items-center gap-2.5 pt-2 text-xs font-medium text-stone-700 cursor-pointer">
                <input type="checkbox" name="methods[bank][require_trx]" value="1"
                       <?= !empty($methods['bank']['require_trx']) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-stone-300">
                <span><?= e(trans('payment_require_trx')) ?></span>
            </label>
        </div>
    </div>

    <!-- Save Button -->
    <div class="pt-4 flex items-center justify-end gap-3 sticky bottom-4 z-20">
        <button type="submit"
                class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3 text-sm transition shadow-lg shadow-brand-600/30 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <?= e(trans('admin_save')) ?>
        </button>
    </div>
</form>

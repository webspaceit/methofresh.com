<?php
// Shipping Methods Configuration Admin View
// Expects: $shipping = ['enabled','free_over','methods'=>[...]] (shipping_config())
//
// Method rows follow the announcement-offer pattern: one renderer closure
// serves both the saved rows and the JS "add" template (rendered with the
// placeholder index -1, whose key is swapped for a monotonic @@KEY@@ on
// insert — field keys are never rewritten, so each row's translate button
// keeps pointing at its own inputs).
$shipMethods = $shipping['methods'];
if ($shipMethods === []) {
    // Everything was removed — show one blank block to type into.
    $shipMethods = [[
        'id' => '', 'name_en' => '', 'name_bn' => '', 'enabled' => true,
        'base' => '', 'per_kg' => '', 'areas' => '', 'exclude' => '',
    ]];
}

$shipRowHtml = static function (int $key, array $row): string {
    ob_start();
    $rowEnabled = !array_key_exists('enabled', $row) || !empty($row['enabled']);
    $rowId = (string)($row['id'] ?? '');
    $baseVal = ($row['base'] ?? '') === '' ? '' : (string)(0 + (float)$row['base']);
    $perKgVal = ($row['per_kg'] ?? '') === '' ? '' : (string)(0 + (float)$row['per_kg']);
    $inputCls = 'w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none';
    ?>
    <div class="rounded-xl border border-stone-300 bg-stone-50/50 overflow-hidden" data-ship-row>
        <div class="flex items-center justify-between border-b border-brand-100 bg-brand-50 px-4 py-2.5">
            <span class="text-xs font-bold text-stone-700 uppercase tracking-wide">
                <?= e(trans('admin_shipping_method')) ?> <span class="ship-n"><?= $key + 1 ?></span>
            </span>
            <button type="button" data-ship-remove
                    class="text-xs font-semibold text-red-600 hover:text-red-700 focus:outline-none">
                <?= e(trans('admin_shipping_remove')) ?>
            </button>
        </div>
        <div class="p-4 space-y-3">
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                    <input type="checkbox" name="methods[<?= $key ?>][enabled]" value="1"
                           class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500"
                           <?= $rowEnabled ? 'checked' : '' ?>>
                    <?= e(trans('admin_shipping_enabled')) ?>
                </label>
                <input type="hidden" name="methods[<?= $key ?>][id]" value="<?= e($rowId) ?>">
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_shipping_name')) ?> (EN)</label>
                    <input type="text" name="methods[<?= $key ?>][name_en]" value="<?= e((string)($row['name_en'] ?? '')) ?>"
                           placeholder="e.g. Inside Dhaka Delivery" class="<?= $inputCls ?>">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_shipping_name')) ?> (BN)</label>
                        <?php $source = '[name="methods[' . $key . '][name_en]"]'; $target = '[name="methods[' . $key . '][name_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <input type="text" name="methods[<?= $key ?>][name_bn]" value="<?= e((string)($row['name_bn'] ?? '')) ?>"
                           placeholder="e.g. ঢাকার ভিতরে ডেলিভারি" class="<?= $inputCls ?>">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_shipping_base')) ?></label>
                        <input type="number" name="methods[<?= $key ?>][base]" min="0" step="0.01"
                               value="<?= e($baseVal) ?>" placeholder="60" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_shipping_per_kg')) ?></label>
                        <input type="number" name="methods[<?= $key ?>][per_kg]" min="0" step="0.01"
                               value="<?= e($perKgVal) ?>" placeholder="0" class="<?= $inputCls ?>">
                    </div>
                </div>
                <div class="flex items-end">
                    <p class="text-xs text-stone-400 pb-2"><?= e(trans('admin_shipping_per_kg_hint')) ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_shipping_areas')) ?></label>
                    <input type="text" name="methods[<?= $key ?>][areas]" value="<?= e((string)($row['areas'] ?? '')) ?>"
                           placeholder="ঢাকা, dhaka, গাজীপুর" class="<?= $inputCls ?>">
                    <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_shipping_areas_hint')) ?></p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_shipping_exclude')) ?></label>
                    <input type="text" name="methods[<?= $key ?>][exclude]" value="<?= e((string)($row['exclude'] ?? '')) ?>"
                           placeholder="ঢাকা, dhaka" class="<?= $inputCls ?>">
                    <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_shipping_exclude_hint')) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
};
?>
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_shipping_methods')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_shipping_methods_desc')) ?></p>
    </div>
</div>

<form method="post" action="<?= e(locale_url('/mf-dashboard/shipping')) ?>" class="space-y-6 max-w-4xl">
    <?= csrf_field() ?>

    <!-- ==========================================
         GLOBAL: charges on/off + free threshold
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3 bg-stone-50/60">
            <span class="material-symbols-outlined text-2xl text-brand-600">local_shipping</span>
            <div>
                <h2 class="font-bold text-stone-900 text-base"><?= e(trans('admin_shipping_charges_on')) ?></h2>
                <p class="text-xs text-stone-500"><?= e(trans('admin_shipping_charges_desc')) ?></p>
            </div>
        </div>
        <div class="p-5 space-y-4">
            <label class="flex items-center gap-2 text-sm font-medium text-stone-700">
                <input type="checkbox" name="shipping_enabled" value="1"
                       class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500"
                       <?= !empty($shipping['enabled']) ? 'checked' : '' ?>>
                <?= e(trans('admin_shipping_charges_on')) ?>
            </label>
            <div class="max-w-xs">
                <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_shipping_free_over')) ?></label>
                <input type="number" name="free_over" min="0" step="1"
                       value="<?= e((string)(float)$shipping['free_over']) ?>"
                       class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_shipping_free_over_hint')) ?></p>
            </div>
        </div>
    </div>

    <!-- ==========================================
         METHODS
         ========================================== -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden shadow-xs">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3 bg-stone-50/60">
            <span class="material-symbols-outlined text-2xl text-brand-600">shopping_cart_checkout</span>
            <div>
                <h2 class="font-bold text-stone-900 text-base"><?= e(trans('admin_shipping_methods')) ?></h2>
                <p class="text-xs text-stone-500"><?= e(trans('admin_shipping_methods_desc')) ?></p>
            </div>
        </div>
        <div class="p-5">
            <div class="space-y-6" id="ship-methods">
                <?php foreach ($shipMethods as $shipKey => $shipRow): ?>
                <?= $shipRowHtml((int)$shipKey, $shipRow) ?>
                <?php endforeach; ?>
            </div>

            <button type="button" id="add-ship-method"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-500 hover:border-brand-500 hover:text-brand-600 transition">
                <span class="material-symbols-outlined text-lg">add</span>
                <?= e(trans('admin_shipping_add')) ?>
            </button>
            <p class="text-xs text-stone-400 mt-3"><?= e(trans('admin_shipping_no_methods_hint')) ?></p>
        </div>
    </div>

    <button type="submit"
            class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 text-sm transition shadow-lg shadow-brand-600/20 cursor-pointer">
        <?= e(trans('admin_save')) ?>
    </button>
</form>

<script>
(function () {
    const container = document.getElementById('ship-methods');
    if (!container) return;

    // The template row is built with the placeholder index -1 and the
    // placeholder is swapped for @@KEY@@ across the WHOLE row HTML, so the
    // name attributes and the translate button's onclick selectors are
    // rewritten together — a selector can never go stale (announcement
    // pattern).
    const template = <?= json_encode(
        str_replace('methods[-1]', 'methods[@@KEY@@]', $shipRowHtml(-1, [
            'id' => '', 'name_en' => '', 'name_bn' => '', 'enabled' => true,
            'base' => '', 'per_kg' => '', 'areas' => '', 'exclude' => '',
        ])),
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    ) ?>;

    // Free index for the next added row: one past the largest key already in
    // the DOM. Monotonic and never reused — once a row has a key, its
    // fields (and their translate selectors) never change again.
    let key = 0;
    container.querySelectorAll('[name^="methods["]').forEach((el) => {
        const m = el.name.match(/^methods\[(\d+)\]/);
        if (m) key = Math.max(key, parseInt(m[1], 10) + 1);
    });

    function renumber() {
        // Display numbers only — field keys must stay untouched so each
        // row's translate button keeps pointing at its own inputs.
        container.querySelectorAll('[data-ship-row]').forEach((row, i) => {
            const n = row.querySelector('.ship-n');
            if (n) n.textContent = i + 1;
        });
    }

    document.getElementById('add-ship-method').addEventListener('click', () => {
        const wrap = document.createElement('div');
        wrap.innerHTML = template.replaceAll('@@KEY@@', String(key));
        key += 1;
        const node = wrap.firstElementChild;
        container.appendChild(node);
        renumber();
        const firstInput = node.querySelector('input[type="text"]');
        if (firstInput) firstInput.focus();
    });

    container.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ship-remove]');
        if (!btn) return;
        btn.closest('[data-ship-row]').remove();
        renumber();
    });
})();
</script>

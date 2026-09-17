<?php
$currentQuery = $_GET;
unset($currentQuery['page']);
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_products')) ?></h1>
        <p class="text-sm text-stone-500 mt-1" id="productCount"><?= e(format_number(count($products))) ?> <?= e(trans('admin_products_count')) ?></p>
    </div>
    <a href="<?= e(locale_url('/mf-dashboard/products/create')) ?>" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-bold">+ <?= e(trans('admin_add_product')) ?></a>
</div>

<form method="get" class="mb-5 max-w-md" autocomplete="off">
    <input type="text" name="q" id="adminQ" value="<?= e($search) ?>" placeholder="<?= e(trans('admin_search')) ?>"
           class="w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
</form>

<div class="bg-white rounded-2xl border border-stone-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-stone-500 border-b border-stone-100 uppercase">
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_thumb')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_name')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_sku')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_category')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_price')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_stock')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_featured')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_new')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_active')) ?></th>
                <th class="px-4 py-3 font-semibold text-right"><?= e(trans('action')) ?></th>
            </tr>
        </thead>
        <tbody id="productRows">
            <?php include views_path('admin/products/wsit__rows.php'); ?>
        </tbody>
    </table>
    <p id="productEmpty" class="text-center text-sm text-stone-400 py-12" <?= empty($products) ? '' : 'hidden' ?>><?= e(trans('admin_no_items')) ?></p>
</div>

<script>
(function () {
    const input = document.getElementById('adminQ');
    const rows = document.getElementById('productRows');
    const empty = document.getElementById('productEmpty');
    const countEl = document.getElementById('productCount');
    const url = '<?= e(locale_url('/mf-dashboard/products/search')) ?>';
    if (!input || !rows) { return; }
    let timer = null, ctrl = null, last = null;
    function run() {
        if (ctrl) { ctrl.abort(); }
        const q = input.value.trim();
        if (q === last) { return; }
        last = q;
        ctrl = new AbortController();
        fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal })
            .then(r => r.json())
            .then(d => {
                if (!d || typeof d.html !== 'string') { return; }
                rows.innerHTML = d.html || '';
                if (empty) { empty.hidden = (d.count || 0) > 0; }
                if (countEl && d.count_label) { countEl.textContent = d.count_label; }
            })
            .catch(() => {});
    }
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(run, 300);
    });
})();
</script>
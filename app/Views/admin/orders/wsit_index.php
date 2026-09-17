<?php
$statusColors = [
    'pending' => 'bg-amber-100 text-amber-700',
    'processing' => 'bg-sky-100 text-sky-700',
    'shipped' => 'bg-indigo-100 text-indigo-700',
    'delivered' => 'bg-green-100 text-green-700',
    'cancelled' => 'bg-red-100 text-red-700',
];
$statusLabels = [
    'pending' => trans('status_pending'),
    'processing' => trans('status_processing'),
    'shipped' => trans('status_shipped'),
    'delivered' => trans('status_delivered'),
    'cancelled' => trans('status_cancelled'),
];
$paymentLabels = [
    'cod'    => trans('payment_cod'),
    'bkash'  => trans('payment_bkash'),
];
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_orders')) ?></h1>
    <span class="text-sm text-stone-500" id="orderCount"><?= e(format_number($total)) ?> <?= e(trans('admin_orders_count')) ?></span>
</div>

<div class="flex flex-col sm:flex-row items-center gap-3 mb-5">
    <div class="flex flex-wrap gap-1.5">
        <?php foreach (array_merge(['all'], $statuses) as $value): ?>
        <a href="<?= e(locale_url('/mf-dashboard/orders') . '?status=' . $value) ?>"
           class="rounded-full px-3.5 py-1.5 text-xs font-semibold border <?= $status === $value ? 'bg-brand-600 border-brand-600 text-white' : 'bg-white border-stone-200 text-stone-600 hover:border-brand-600' ?>">
            <?= $value === 'all' ? e(trans('all')) : e($statusLabels[$value]) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <form method="get" class="sm:ml-auto flex gap-2" autocomplete="off">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="text" name="q" id="adminQ" value="<?= e($search) ?>" placeholder="<?= e(trans('admin_search')) ?>"
               class="rounded-xl border border-stone-200 bg-white px-4 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        <button type="submit" class="rounded-xl bg-stone-800 hover:bg-stone-900 text-white px-4 py-2 text-sm font-semibold"><?= e(trans('nav_search')) ?></button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-stone-200 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-stone-500 border-b border-stone-100 uppercase">
                <th class="px-4 py-3 font-semibold"><?= e(trans('order_number_col')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('admin_customer')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('total')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('payment')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('status')) ?></th>
                <th class="px-4 py-3 font-semibold"><?= e(trans('date')) ?></th>
                <th class="px-4 py-3 font-semibold text-right"><?= e(trans('action')) ?></th>
            </tr>
        </thead>
        <tbody id="orderRows">
            <?php include views_path('admin/orders/wsit__rows.php'); ?>
        </tbody>
    </table>
    <p id="orderEmpty" class="text-center text-sm text-stone-400 py-12" <?= empty($orders) ? '' : 'hidden' ?>><?= e(trans('admin_no_orders')) ?></p>
</div>

<?php if ($pages > 1): ?>
<nav class="mt-6 flex items-center justify-center gap-1">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"
           class="px-3.5 py-2 rounded-lg border text-sm <?= $i === $page ? 'bg-brand-600 border-brand-600 text-white font-bold' : 'bg-white border-stone-200 hover:border-brand-600' ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<script>
(function () {
    const input = document.getElementById('adminQ');
    const rows = document.getElementById('orderRows');
    const empty = document.getElementById('orderEmpty');
    const countEl = document.getElementById('orderCount');
    const statusInput = document.querySelector('input[name="status"]');
    const url = '<?= e(locale_url('/mf-dashboard/orders/search')) ?>';
    if (!input || !rows) { return; }
    let timer = null, ctrl = null, last = null;
    function run() {
        if (ctrl) { ctrl.abort(); }
        const q = input.value.trim();
        if (q === last) { return; }
        last = q;
        const st = statusInput ? statusInput.value : 'all';
        ctrl = new AbortController();
        fetch(url + '?status=' + encodeURIComponent(st) + '&q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: ctrl.signal })
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
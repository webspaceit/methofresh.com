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
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_dashboard')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('site_name')) ?></p>
    </div>
    <a href="<?= e(locale_url('/mf-dashboard/products/create')) ?>" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 text-sm font-bold">+ <?= e(trans('admin_add_product')) ?></a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl border border-stone-200 p-5">
        <p class="text-xs font-semibold text-stone-500 uppercase"><?= e(trans('admin_total_revenue')) ?></p>
        <p class="mt-2 text-2xl font-extrabold text-brand-700"><?= e(format_price($revenue)) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-stone-200 p-5">
        <p class="text-xs font-semibold text-stone-500 uppercase"><?= e(trans('admin_total_orders')) ?></p>
        <p class="mt-2 text-2xl font-extrabold text-stone-900"><?= e(format_number($ordersCount)) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-stone-200 p-5">
        <p class="text-xs font-semibold text-stone-500 uppercase"><?= e(trans('admin_total_products')) ?></p>
        <p class="mt-2 text-2xl font-extrabold text-stone-900"><?= e(format_number($productsCount)) ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-stone-200 p-5">
        <p class="text-xs font-semibold text-stone-500 uppercase"><?= e(trans('admin_total_customers')) ?></p>
        <p class="mt-2 text-2xl font-extrabold text-stone-900"><?= e(format_number($customers)) ?></p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-stone-100">
        <h2 class="font-bold text-stone-900"><?= e(trans('admin_recent_orders')) ?></h2>
        <a href="<?= e(locale_url('/mf-dashboard/orders')) ?>" class="text-sm font-semibold text-brand-700 hover:underline"><?= e(trans('view_all')) ?> →</a>
    </div>
    <?php if (empty($recentOrders)): ?>
    <p class="text-center text-sm text-stone-400 py-10"><?= e(trans('admin_no_orders')) ?></p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-stone-500 border-b border-stone-100 uppercase">
                    <th class="px-5 py-3 font-semibold"><?= e(trans('order_number_col')) ?></th>
                    <th class="px-5 py-3 font-semibold"><?= e(trans('admin_customer')) ?></th>
                    <th class="px-5 py-3 font-semibold"><?= e(trans('total')) ?></th>
                    <th class="px-5 py-3 font-semibold"><?= e(trans('status')) ?></th>
                    <th class="px-5 py-3 font-semibold"><?= e(trans('date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                <tr class="border-b border-stone-50 last:border-0 hover:bg-stone-50">
                    <td class="px-5 py-3"><a href="<?= e(locale_url('/mf-dashboard/orders/' . $order['id'])) ?>" class="font-bold text-brand-700 hover:underline"><?= e($order['order_number']) ?></a></td>
                    <td class="px-5 py-3 text-stone-600"><?= e(customer_display_name($order['shipping_name'], $order['shipping_email'] ?? null)) ?></td>
                    <td class="px-5 py-3 font-semibold"><?= e(format_price((float)$order['total'])) ?></td>
                    <td class="px-5 py-3"><span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-bold <?= $statusColors[$order['status']] ?? 'bg-stone-100 text-stone-600' ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td class="px-5 py-3 text-stone-500"><?= e(format_date($order['created_at'], 'd M Y')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
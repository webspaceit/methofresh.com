<?php
// Delivery Tracking — Material admin (locale-aware via trans()).
$filter   = (string)($filter ?? 'queue');
$queue    = (array)($queue ?? []);
$delivered= (array)($delivered ?? []);
$statusCounts = (array)($statusCounts ?? []);
$delivery = is_array($delivery ?? null) ? $delivery : ['delivered' => 0, 'total' => 0, 'rate' => 0.0];

$statusPill = [
    'pending'    => ['bg-surface-container-high text-on-surface', 'border-l-4 border-error'],
    'processing' => ['bg-secondary-fixed text-on-secondary-container', 'border-l-4 border-secondary'],
    'shipped'    => ['bg-primary-fixed text-primary', 'border-l-4 border-primary-container'],
    'delivered'  => ['bg-tertiary-fixed text-tertiary-container', 'border-l-4 border-tertiary-fixed-dim'],
    'cancelled'  => ['bg-error-container text-on-error-container', 'border-l-4 border-error'],
];
$statusLabels = [
    'pending'    => trans('status_pending'),
    'processing' => trans('status_processing'),
    'shipped'    => trans('status_shipped'),
    'delivered'  => trans('status_delivered'),
    'cancelled'  => trans('status_cancelled'),
];
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-secondary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
            <span class="material-symbols-outlined text-[26px]">local_shipping</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_delivery_tracking')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_delivery_tracking_sub')) ?></p>
        </div>
    </div>
    <div class="inline-flex p-1 rounded-xl bg-surface-container-low font-label-md text-label-md">
        <a class="px-3 py-1.5 rounded-lg <?= $filter === 'queue' ? 'bg-surface-container-lowest text-primary font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface transition-colors' ?>" href="<?= e(locale_url('/mf-dashboard/delivery-tracking?filter=queue')) ?>"><?= e(trans('admin_delivery_queue')) ?></a>
        <a class="px-3 py-1.5 rounded-lg <?= $filter === 'delivered' ? 'bg-surface-container-lowest text-primary font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface transition-colors' ?>" href="<?= e(locale_url('/mf-dashboard/delivery-tracking?filter=delivered')) ?>"><?= e(trans('admin_recent_delivered')) ?></a>
        <a class="px-3 py-1.5 rounded-lg <?= $filter === 'all' ? 'bg-surface-container-lowest text-primary font-bold shadow-xs' : 'text-on-surface-variant hover:text-on-surface transition-colors' ?>" href="<?= e(locale_url('/mf-dashboard/delivery-tracking?filter=all')) ?>"><?= e(trans('admin_all_orders')) ?></a>
    </div>
</section>

<!-- KPI row -->
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_needs_action')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number((int)($statusCounts['pending'] ?? 0))) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-error-container/50 flex items-center justify-center text-on-error-container">
                <span class="material-symbols-outlined text-[22px]">pending_actions</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="text-on-surface-variant"><?= e(trans('status_pending')) ?></span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span><?= e(format_number((int)($statusCounts['pending'] ?? 0))) ?>
            </span>
        </div>
    </div>
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_in_transit')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number((int)($statusCounts['shipped'] ?? 0))) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary-fixed/40 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[22px]">local_shipping</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="text-on-surface-variant"><?= e(trans('status_shipped')) ?></span>
            <span class="text-on-surface font-semibold"><?= e(format_number((int)($statusCounts['shipped'] ?? 0))) ?></span>
        </div>
    </div>
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_delivered_total')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number((int)($statusCounts['delivered'] ?? 0))) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-tertiary-fixed/40 flex items-center justify-center text-tertiary-container">
                <span class="material-symbols-outlined text-[22px]">checklist</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="text-on-surface-variant"><?= e(trans('admin_recent_delivered')) ?></span>
            <span class="text-on-surface font-semibold"><?= e(format_number((int)$delivery['delivered'])) ?></span>
        </div>
    </div>
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_delivery_rate')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number((float)$delivery['rate'], 1)) ?>%</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-[22px]">percent</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container">
            <div class="w-16 h-2 rounded-full bg-surface-container overflow-hidden">
                <div class="h-full bg-tertiary-container rounded-full" style="width: <?= e((string)min(100, max(0, (float)$delivery['rate']))) ?>%;"></div>
            </div>
            <span class="font-label-sm text-label-sm text-on-surface-variant"><?= e(format_number((int)$delivery['delivered'])) ?> / <?= e(format_number((int)$delivery['total'])) ?></span>
        </div>
    </div>
</section>

<?php if ($filter !== 'delivered'): ?>
<!-- Delivery queue -->
<section class="flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-secondary text-[22px]">pending_actions</span>
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_delivery_queue')) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_delivery_queue_sub')) ?></p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded-full bg-secondary-fixed text-on-secondary-container font-label-sm text-label-sm font-bold"><?= e(format_number(count($queue))) ?></span>
    </div>
    <?php if (empty($queue)): ?>
    <p class="py-12 text-center font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_no_queue')) ?></p>
    <?php else: ?>
    <div class="w-full overflow-x-auto mt-space-sm">
        <table class="w-full text-left border-collapse font-body-md text-body-md">
            <thead>
                <tr class="border-b border-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                    <th class="py-3 px-4 font-semibold"><?= e(trans('order_number_col')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('admin_customer')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('payment_method_lbl')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('status')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('admin_placed')) ?></th>
                    <th class="py-3 px-4 font-semibold text-right"><?= e(trans('action')) ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php foreach ($queue as $order):
                    $status = (string)($order['status'] ?? 'pending');
                    $pill = $statusPill[$status] ?? [$statusPill['pending'][0], $statusPill['pending'][1]];
                ?>
                <tr class="hover:bg-surface-container-low/40 transition-colors <?= e($pill[1]) ?>">
                    <td class="py-3.5 px-4 font-label-md text-label-md font-bold text-primary">#<?= e((string)$order['order_number']) ?></td>
                    <td class="py-3.5 px-4">
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface"><?= e(customer_display_name((string)($order['shipping_name'] ?? ''), $order['shipping_email'] ?? null)) ?></span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e((string)($order['shipping_city'] ?? '—')) ?> • <?= e((string)($order['shipping_phone'] ?? '')) ?></span>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-surface-container-high text-on-surface font-label-sm text-label-sm font-medium">
                            <span class="material-symbols-outlined text-[14px]">account_balance_wallet</span>
                            <?= e(payment_method_label($order['payment_method'])) ?>
                        </span>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-label-sm text-label-sm font-bold <?= e($pill[0]) ?>">
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                            <?= e($statusLabels[$status] ?? $status) ?>
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-on-surface-variant"><?= e(format_date((string)$order['created_at'], 'd M Y H:i')) ?></td>
                    <td class="py-3.5 px-4 text-right">
                        <a href="<?= e(locale_url('/mf-dashboard/orders/' . (int)$order['id'])) ?>" class="inline-flex items-center gap-1 p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="<?= e(trans('view')) ?>">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($filter !== 'queue'): ?>
<!-- Recently delivered -->
<section class="flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-tertiary-container text-[22px]">task_alt</span>
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_recent_delivered')) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_delivery_tracking_sub')) ?></p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm font-bold"><?= e(format_number(count($delivered))) ?></span>
    </div>
    <?php if (empty($delivered)): ?>
    <p class="py-12 text-center font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_no_delivered')) ?></p>
    <?php else: ?>
    <div class="w-full overflow-x-auto mt-space-sm">
        <table class="w-full text-left border-collapse font-body-md text-body-md">
            <thead>
                <tr class="border-b border-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                    <th class="py-3 px-4 font-semibold"><?= e(trans('order_number_col')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('admin_customer')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('total')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('status')) ?></th>
                    <th class="py-3 px-4 font-semibold"><?= e(trans('admin_placed')) ?></th>
                    <th class="py-3 px-4 font-semibold text-right"><?= e(trans('action')) ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php foreach ($delivered as $order): ?>
                <tr class="hover:bg-surface-container-low/40 transition-colors border-l-4 border-tertiary-fixed-dim">
                    <td class="py-3.5 px-4 font-label-md text-label-md font-bold text-primary">#<?= e((string)$order['order_number']) ?></td>
                    <td class="py-3.5 px-4">
                        <div class="flex flex-col">
                            <span class="font-semibold text-on-surface"><?= e(customer_display_name((string)($order['shipping_name'] ?? ''), $order['shipping_email'] ?? null)) ?></span>
                            <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e((string)($order['shipping_city'] ?? '—')) ?></span>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 font-label-lg text-label-lg font-bold text-on-surface"><?= e(admin_price((float)($order['total'] ?? 0))) ?></td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-label-sm text-label-sm font-bold <?= e($statusPill['delivered'][0]) ?>">
                            <span class="material-symbols-outlined text-[14px]">done_all</span>
                            <?= e(trans('status_delivered')) ?>
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-on-surface-variant"><?= e(format_date((string)$order['created_at'], 'd M Y H:i')) ?></td>
                    <td class="py-3.5 px-4 text-right">
                        <a href="<?= e(locale_url('/mf-dashboard/orders/' . (int)$order['id'])) ?>" class="inline-flex items-center gap-1 p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="<?= e(trans('view')) ?>">
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
</div>
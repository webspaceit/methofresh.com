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
?>
<?php foreach ($orders as $order): ?>
<tr class="border-b border-stone-50 last:border-0 hover:bg-stone-50">
    <td class="px-4 py-3 font-bold text-brand-700"><?= e($order['order_number']) ?></td>
    <td class="px-4 py-3">
        <p class="font-medium text-stone-800"><?= e(customer_display_name($order['shipping_name'], $order['shipping_email'] ?? null)) ?></p>
        <p class="text-[11px] text-stone-400"><?= e($order['shipping_phone']) ?></p>
    </td>
    <td class="px-4 py-3">
        <span class="text-xs font-semibold text-stone-700"><?= e(payment_method_label($order['payment_method'])) ?></span>
        <?php if (!empty($order['transaction_id'])): ?>
        <p class="text-[11px] font-mono text-stone-400">Trx: <?= e($order['transaction_id']) ?></p>
        <?php endif; ?>
    </td>
    <td class="px-4 py-3"><span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-bold <?= $statusColors[$order['status']] ?? 'bg-stone-100 text-stone-600' ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
    <td class="px-4 py-3 text-stone-500"><?= e(format_date($order['created_at'], 'd M Y H:i')) ?></td>
    <td class="px-4 py-3 text-right"><a href="<?= e(locale_url('/mf-dashboard/orders/' . $order['id'])) ?>" class="inline-block rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold hover:border-brand-600 hover:text-brand-700"><?= e(trans('view')) ?></a></td>
</tr>
<?php endforeach; ?>
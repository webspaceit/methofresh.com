<?php
$statusLabels = [
    'pending' => trans('status_pending'),
    'processing' => trans('status_processing'),
    'shipped' => trans('status_shipped'),
    'delivered' => trans('status_delivered'),
    'cancelled' => trans('status_cancelled'),
];
$statusColors = [
    'pending' => 'bg-amber-100 text-amber-700',
    'processing' => 'bg-sky-100 text-sky-700',
    'shipped' => 'bg-indigo-100 text-indigo-700',
    'delivered' => 'bg-green-100 text-green-700',
    'cancelled' => 'bg-red-100 text-red-700',
];

// What the customer is allowed to do with this order.
$canCancel = $order['status'] === 'pending'
    && ($order['payment_method'] === 'cod' || empty($order['transaction_id']));
$canReturn = $order['status'] === 'delivered';
?>
<section class="max-w-4xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <a href="<?= e(locale_url('/account')) ?>" class="hover:text-brand-700"><?= e(trans('nav_account')) ?></a>
        <span> / </span>
        <span class="text-stone-600">#<?= e($order['order_number']) ?></span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-extrabold text-brand-800"><?= e(trans('order_details')) ?> <span class="text-brand-700">#<?= e($order['order_number']) ?></span></h1>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-block rounded-full px-3 py-1 text-xs font-bold <?= $statusColors[$order['status']] ?? 'bg-stone-100 text-stone-600' ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
            <?php if ($canCancel): ?>
            <!-- The action sits right next to the status, so it can never be
                 missed at the bottom of a long page. -->
            <form method="post" action="<?= e(locale_url('/account/orders/' . $order['id'] . '/cancel')) ?>"
                  onsubmit="return confirm('<?= e(trans('cancel_order_confirm')) ?>')">
                <?= csrf_field() ?>
                <button class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <?= e(trans('cancel_order')) ?>
                </button>
            </form>
            <?php elseif ($canReturn): ?>
            <span class="inline-flex items-center gap-1.5 rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-xs font-bold text-stone-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 109-9M3 4v5h5"/></svg>
                <?= e(trans('return_request')) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($canCancel): ?>
    <p class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800"><?= e(trans('cancel_order_help')) ?></p>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 gap-4 mb-6">
        <div class="bg-card rounded-2xl border border-honey-600/15 p-5">
            <h2 class="font-bold text-sm mb-3 text-brand-800"><?= e(trans('shipping_address')) ?></h2>
            <p class="text-sm font-semibold"><?= e(customer_display_name($order['shipping_name'], $order['shipping_email'] ?? null)) ?></p>
            <p class="text-sm text-stone-500"><?= e($order['shipping_address']) ?></p>
            <p class="text-sm text-stone-500"><?= e($order['shipping_city']) ?><?= $order['shipping_postal'] ? ', ' . e($order['shipping_postal']) : '' ?></p>
            <p class="text-sm text-stone-500"><?= e($order['shipping_phone']) ?></p>
        </div>
        <div class="bg-card rounded-2xl border border-honey-600/15 p-5">
            <h2 class="font-bold text-sm mb-3 text-brand-800"><?= e(trans('order_details')) ?></h2>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('ordered_on')) ?></span><span class="font-medium"><?= e(format_date($order['created_at'])) ?></span></div>
                <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('payment')) ?></span><span class="font-medium"><?= e(payment_method_label($order['payment_method'])) ?></span></div>
                <?php if (!empty($order['transaction_id'])): ?>
                <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('payment_trx_id')) ?></span><span class="font-mono font-bold text-xs bg-honey-100/60 px-2 py-0.5 rounded text-honey-700"><?= e($order['transaction_id']) ?></span></div>
                <?php endif; ?>
                <?php if ($order['notes']): ?>
                <div class="flex justify-between gap-2"><span class="text-stone-500 shrink-0"><?= e(trans('notes')) ?></span><span class="font-medium text-right"><?= e($order['notes']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-x-auto mb-6">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-stone-500 border-b border-honey-600/15 uppercase">
                    <th class="px-4 py-3 font-semibold"><?= e(trans('order_items')) ?></th>
                    <th class="px-4 py-3 font-semibold text-right"><?= e(trans('unit_price')) ?></th>
                    <th class="px-4 py-3 font-semibold text-center"><?= e(trans('quantity')) ?></th>
                    <th class="px-4 py-3 font-semibold text-right"><?= e(trans('total')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                <tr class="border-b border-honey-600/15 last:border-0">
                    <td class="px-4 py-3 font-semibold text-brand-800"><?= e($item['product_name']) ?></td>
                    <td class="px-4 py-3 text-right text-stone-500"><?= e(format_price((float)$item['price'])) ?></td>
                    <td class="px-4 py-3 text-center"><?= e(format_number((int)$item['quantity'])) ?></td>
                    <td class="px-4 py-3 text-right font-semibold"><?= e(format_price((float)$item['total'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-card rounded-2xl border border-honey-600/15 p-5 max-w-sm ml-auto">
        <div class="space-y-1.5 text-sm">
            <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('subtotal')) ?></span><span><?= e(format_price((float)$order['subtotal'])) ?></span></div>
            <?php if ((float)$order['discount'] > 0): ?>
            <div class="flex justify-between text-green-600"><span><?= e(trans('discount')) ?></span><span>-<?= e(format_price((float)$order['discount'])) ?></span></div>
            <?php endif; ?>
            <div class="flex justify-between"><span class="text-stone-500"><?= e(trans('shipping')) ?></span><span><?= (float)$order['shipping'] > 0 ? e(format_price((float)$order['shipping'])) : e(trans('shipping_free')) ?></span></div>
            <div class="flex justify-between font-bold border-t border-honey-600/15 pt-2 text-base"><span><?= e(trans('grand_total')) ?></span><span class="text-brand-700"><?= e(format_price((float)$order['total'])) ?></span></div>
        </div>
    </div>

    <?php if ($canReturn): ?>
    <div class="mt-6 rounded-2xl border border-honey-600/20 bg-white p-5">
        <h2 class="font-bold text-brand-800"><?= e(trans('return_request')) ?></h2>
        <p class="mt-1 text-sm text-stone-500"><?= e(trans('return_request_help')) ?></p>
        <form method="post" action="<?= e(locale_url('/account/orders/' . $order['id'] . '/return-request')) ?>" class="mt-3 space-y-3">
            <?= csrf_field() ?>
            <label class="block text-xs font-semibold text-stone-600"><?= e(trans('return_request_details')) ?>
                <textarea name="details" required minlength="10" rows="4" class="mt-1 w-full rounded-xl border border-honey-600/20 px-3 py-2 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"></textarea>
            </label>
            <button class="rounded-xl bg-brand-700 px-4 py-2 text-sm font-bold text-white hover:bg-brand-800"><?= e(trans('return_request_send')) ?></button>
        </form>
    </div>
    <?php endif; ?>
</section>

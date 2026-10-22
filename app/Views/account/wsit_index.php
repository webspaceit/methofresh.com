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
?>
<section class="max-w-4xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e(trans('nav_account')) ?></span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-800"><?= e(trans('account')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('account_greeting')) ?> <span class="font-semibold text-stone-700"><?= e(user_display_name($user)) ?></span></p>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
            <?php if (!empty($user['is_admin'])): ?>
            <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="rounded-xl border border-brand-600 text-brand-700 bg-brand-50 px-4 py-2 text-xs sm:text-sm font-bold hover:bg-brand-100 transition">
                ⚙ <?= e(trans('nav_admin')) ?>
            </a>
            <?php endif; ?>
            <a href="<?= e(locale_url('/products')) ?>" class="rounded-xl bg-brand-600 text-white px-4 py-2 text-xs sm:text-sm font-bold hover:bg-brand-700 transition">
                <?= e(trans('continue_shopping')) ?>
            </a>
            <a href="<?= e(locale_url('/logout')) ?>" class="rounded-xl border border-stone-300 text-stone-700 px-4 py-2 text-xs sm:text-sm font-semibold hover:border-red-400 hover:text-red-600 hover:bg-red-50 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <?= e(trans('nav_logout')) ?>
            </a>
        </div>
    </div>

    <h2 class="font-bold text-lg text-brand-800 mb-4"><?= e(trans('order_history')) ?></h2>

    <?php if (empty($orders)): ?>
    <div class="text-center py-16 bg-card rounded-3xl border border-honey-600/15">
        <p class="text-6xl mb-4">📦</p>
        <h3 class="font-bold text-brand-800"><?= e(trans('no_orders')) ?></h3>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('no_orders_hint')) ?></p>
    </div>
    <?php else: ?>
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-stone-500 border-b border-honey-600/15 uppercase">
                    <th class="px-4 py-3 font-semibold"><?= e(trans('order_number_col')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e(trans('date')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e(trans('status')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e(trans('total')) ?></th>
                    <th class="px-4 py-3 font-semibold text-right"><?= e(trans('action')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr class="border-b border-honey-600/15 last:border-0 hover:bg-honey-100/40">
                    <td class="px-4 py-3 font-bold text-brand-700"><?= e($order['order_number']) ?></td>
                    <td class="px-4 py-3 text-stone-500"><?= e(format_date($order['created_at'], 'd M Y')) ?></td>
                    <td class="px-4 py-3"><span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-bold <?= $statusColors[$order['status']] ?? 'bg-stone-100 text-stone-600' ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td class="px-4 py-3 font-semibold"><?= e(format_price((float)$order['total'])) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center justify-end gap-3">
                            <?php if ($order['status'] === 'pending' && ($order['payment_method'] === 'cod' || empty($order['transaction_id']))): ?>
                            <!-- Cancel straight from the list, so nobody has to
                                 open the order first. -->
                            <form method="post" action="<?= e(locale_url('/account/orders/' . $order['id'] . '/cancel')) ?>"
                                  onsubmit="return confirm('<?= e(trans('cancel_order_confirm')) ?>')">
                                <?= csrf_field() ?>
                                <button class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 hover:bg-amber-100">
                                    <?= e(trans('cancel_order')) ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            <a href="<?= e(locale_url('/account/orders/' . $order['id'])) ?>" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline"><?= e(trans('view')) ?> →</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>
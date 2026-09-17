<section class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-3xl border border-stone-200 p-8 sm:p-12 shadow-sm">
        <div class="mx-auto w-20 h-20 rounded-full bg-green-100 flex items-center justify-center text-5xl">✅</div>
        <h1 class="mt-6 text-2xl sm:text-3xl font-extrabold text-stone-900"><?= e(trans('order_success')) ?></h1>
        <p class="mt-3 text-sm text-stone-500"><?= e(trans('order_success_msg')) ?></p>

        <div class="mt-6 rounded-2xl bg-stone-50 border border-stone-200 p-4">
            <p class="text-xs text-stone-500 font-semibold uppercase tracking-wide"><?= e(trans('order_number')) ?></p>
            <p class="text-xl font-extrabold text-brand-700 mt-1 tracking-wide"><?= e($order['order_number']) ?></p>
            <p class="text-xs text-stone-400 mt-2"><?= e(trans('payment_method_lbl')) ?>: <span class="font-semibold text-stone-700"><?= e(payment_method_label($order['payment_method'])) ?></span>
            <?php if (!empty($order['transaction_id'])): ?>
                <span class="inline-block ml-2 px-2 py-0.5 text-[11px] font-mono font-medium rounded-md bg-stone-200 text-stone-800">TrxID: <?= e($order['transaction_id']) ?></span>
            <?php endif; ?>
            </p>
        </div>

        <div class="mt-4 rounded-2xl bg-stone-50 border border-stone-200 p-4 text-left space-y-2">
            <h3 class="text-sm font-bold text-stone-800 mb-2"><?= e(trans('order_summary')) ?></h3>
            <div class="flex justify-between text-sm"><span class="text-stone-500"><?= e(trans('subtotal')) ?></span><span><?= e(format_price((float)$order['subtotal'])) ?></span></div>
            <div class="flex justify-between text-sm"><span class="text-stone-500"><?= e(trans('shipping')) ?></span><span><?= (float)$order['shipping'] > 0 ? e(format_price((float)$order['shipping'])) : e(trans('shipping_free')) ?></span></div>
            <div class="flex justify-between text-sm font-bold border-t border-stone-200 pt-2"><span><?= e(trans('grand_total')) ?></span><span class="text-brand-700"><?= e(format_price((float)$order['total'])) ?></span></div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= e(locale_url('/products')) ?>" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('continue_shopping2')) ?></a>
            <?php if (current_user()): ?>
            <a href="<?= e(locale_url('/account')) ?>" class="rounded-xl border border-stone-300 px-6 py-3 text-sm font-bold text-stone-700 hover:border-brand-600"><?= e(trans('nav_orders')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
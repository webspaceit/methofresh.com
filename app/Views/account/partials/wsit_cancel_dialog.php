<?php
/**
 * "Are you sure?" popup for cancelling an order.
 *
 * Replaces the browser's own confirm() box with something that looks like
 * the rest of the shop. The result of the cancellation is then shown as the
 * usual toast in the corner.
 *
 * Expects:
 *   $cancelOrderId     int    the order being cancelled
 *   $cancelAction      string the form action
 *   $cancelButtonClass string classes for the trigger button
 *   $cancelLabel       string text on the trigger button
 *   $cancelIcon        string optional svg path for the trigger icon
 */
$cancelOrderId     = (int)($cancelOrderId ?? 0);
$cancelAction      = (string)($cancelAction ?? '');
$cancelButtonClass = (string)($cancelButtonClass ?? '');
$cancelLabel       = (string)($cancelLabel ?? trans('cancel_order'));
$cancelFormId      = 'wsit-cancel-form-' . $cancelOrderId;
?>
<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" @click="open = true" class="<?= e($cancelButtonClass) ?>">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <?= e($cancelLabel) ?>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-stone-900/50 backdrop-blur-sm" @click="open = false"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl p-6 text-left"
             role="dialog" aria-modal="true">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </span>
                <div>
                    <h3 class="text-base font-extrabold text-stone-900"><?= e(trans('cancel_order_confirm_title')) ?></h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-stone-500"><?= e(trans('cancel_order_confirm_body')) ?></p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" @click="open = false"
                        class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-bold text-stone-700 transition hover:bg-stone-50">
                    <?= e(trans('cancel_order_keep')) ?>
                </button>
                <button type="submit" form="<?= e($cancelFormId) ?>"
                        class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-bold text-white transition hover:bg-amber-800">
                    <?= e(trans('cancel_order_confirm_btn')) ?>
                </button>
            </div>
        </div>
    </div>

    <form id="<?= e($cancelFormId) ?>" method="post" action="<?= e($cancelAction) ?>" class="hidden">
        <?= csrf_field() ?>
    </form>
</div>

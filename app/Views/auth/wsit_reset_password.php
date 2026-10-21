<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-card rounded-3xl border border-honey-600/15 p-6 sm:p-8 shadow-sm">
        <h1 class="text-2xl font-extrabold text-brand-800"><?= e(trans('reset_password')) ?></h1>
        <p class="text-sm text-stone-500 mt-2 mb-6"><?= e(trans('password_reset_password_rules')) ?></p>
        <form method="post" action="<?= e(locale_url('/reset-password')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label class="block text-xs font-semibold text-stone-500"><?= e(trans('password')) ?>
                <input type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </label>
            <label class="block text-xs font-semibold text-stone-500"><?= e(trans('confirm_password')) ?>
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </label>
            <button type="submit" class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 text-sm"><?= e(trans('reset_password')) ?></button>
        </form>
    </div>
</section>

<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-card rounded-3xl border border-honey-600/15 p-6 sm:p-8 shadow-sm">
        <h1 class="text-2xl font-extrabold text-brand-800"><?= e(trans('forgot_password')) ?></h1>
        <p class="text-sm text-stone-500 mt-2 mb-6"><?= e(trans('password_reset_help')) ?></p>
        <form method="post" action="<?= e(locale_url('/forgot-password')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <label class="block text-xs font-semibold text-stone-500"><?= e(trans('email')) ?>
                <input type="email" name="email" required autocomplete="email" class="mt-1 w-full rounded-xl border border-honey-600/15 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </label>
            <button type="submit" class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 text-sm"><?= e(trans('password_reset_send')) ?></button>
        </form>
        <p class="mt-5 text-center text-sm"><a class="font-semibold text-brand-700 hover:underline" href="<?= e(locale_url('/login')) ?>"><?= e(trans('back_to_login')) ?></a></p>
    </div>
</section>

<section class="max-w-md mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-8 shadow-sm">
        <div class="text-center mb-6">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-600 flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h1 class="mt-4 text-2xl font-extrabold text-stone-900"><?= e(trans('login_title')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('login_subtitle')) ?></p>
        </div>

        <form method="post" action="<?= e(locale_url('/login')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('email')) ?> *</label>
                <input type="email" name="email" required autofocus class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('password')) ?> *</label>
                <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 text-sm transition shadow-lg shadow-brand-600/20">
                <?= e(trans('sign_in')) ?>
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-stone-500">
            <?= e(trans('dont_have_account')) ?>
            <a href="<?= e(locale_url('/register')) ?>" class="font-semibold text-brand-700 hover:underline"><?= e(trans('register')) ?></a>
        </p>
    </div>
</section>
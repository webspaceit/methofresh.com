<section class="max-w-lg mx-auto px-4 py-16">
    <div class="bg-white rounded-3xl border border-stone-200 p-6 sm:p-8 shadow-sm">
        <div class="text-center mb-6">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-600 flex items-center justify-center">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h1 class="mt-4 text-2xl font-extrabold text-stone-900"><?= e(trans('register_title')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('register_subtitle')) ?></p>
        </div>

        <form method="post" action="<?= e(locale_url('/register')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('name')) ?> *</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('email')) ?> *</label>
                    <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('phone')) ?> *</label>
                    <input type="tel" name="phone" required value="<?= e($_POST['phone'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('address')) ?></label>
                <input type="text" name="address" value="<?= e($_POST['address'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('city')) ?></label>
                    <input type="text" name="city" value="<?= e($_POST['city'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('password')) ?> *</label>
                    <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('confirm_password')) ?> *</label>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 text-sm transition shadow-lg shadow-brand-600/20">
                <?= e(trans('create_account')) ?>
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-stone-500">
            <?= e(trans('have_account')) ?>
            <a href="<?= e(locale_url('/login')) ?>" class="font-semibold text-brand-700 hover:underline"><?= e(trans('login')) ?></a>
        </p>
    </div>
</section>
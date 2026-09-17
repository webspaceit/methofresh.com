<section class="max-w-lg mx-auto px-4 py-24 text-center">
    <p class="text-8xl font-black text-brand-600">404</p>
    <h1 class="mt-4 text-2xl font-extrabold text-stone-900"><?= e(trans('error_404_title')) ?></h1>
    <p class="mt-2 text-sm text-stone-500"><?= e(trans('error_404_msg')) ?></p>
    <a href="<?= e(locale_url('/')) ?>" class="inline-block mt-6 rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('go_home')) ?></a>
</section>
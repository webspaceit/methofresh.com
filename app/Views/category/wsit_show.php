<?php
$currentQuery = $_GET;
unset($currentQuery['page']);
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-4">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <a href="<?= e(locale_url('/categories')) ?>" class="hover:text-brand-700"><?= e(trans('nav_categories')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e($category['name']) ?></span>
    </nav>

    <div class="relative rounded-3xl overflow-hidden bg-gradient-to-r from-brand-700 to-emerald-700 text-white mb-8">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_90%_20%,white,transparent_40%)]"></div>
        <div class="relative px-6 sm:px-10 py-10">
            <h1 class="text-2xl sm:text-3xl font-extrabold"><?= e($category['name']) ?></h1>
            <?php if ($category['description']): ?>
            <p class="mt-2 max-w-xl text-sm text-emerald-50/90"><?= e($category['description']) ?></p>
            <?php endif; ?>
            <p class="mt-4 text-xs font-semibold text-lime-200"><?= e(format_number($total)) ?> <?= e(trans('products_count')) ?></p>
        </div>
    </div>

    <?php if (empty($products)): ?>
    <div class="text-center py-16">
        <p class="text-6xl mb-4">🧺</p>
        <h2 class="text-xl font-bold text-stone-800"><?= e(trans('no_products')) ?></h2>
        <a href="<?= e(locale_url('/products')) ?>" class="inline-block mt-5 rounded-xl bg-brand-600 text-white px-6 py-3 text-sm font-bold hover:bg-brand-700"><?= e(trans('view_all')) ?></a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($products as $product) include views_path('partials/wsit_product-card.php'); ?>
    </div>
    <?php include views_path('partials/wsit_pagination.php'); ?>
    <?php endif; ?>
</section>
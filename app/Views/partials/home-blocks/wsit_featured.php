<?php
// Homepage "Featured Products" block.
// Expects: $title (string), $featured (array of localized product rows).
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-end justify-between mb-5">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-stone-900"><?= e($title) ?></h2>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('tagline')) ?></p>
        </div>
        <a href="<?= e(locale_url('/products')) ?>" class="text-sm font-semibold text-brand-700 hover:underline shrink-0"><?= e(trans('view_all')) ?> →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($featured as $product) include views_path('partials/wsit_product-card.php'); ?>
    </div>
</section>
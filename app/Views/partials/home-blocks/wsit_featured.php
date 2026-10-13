<?php
// Homepage "Featured Products" block.
// Expects: $title (string), $featured (array of localized product rows).
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="text-center max-w-2xl mx-auto mb-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-stone-900 tracking-tight"><?= e($title) ?></h2>
        <div class="w-12 h-1 bg-amber-500 mx-auto mt-3 rounded-full"></div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        <?php foreach ($featured as $product) include views_path('partials/wsit_product-card.php'); ?>
    </div>
</section>
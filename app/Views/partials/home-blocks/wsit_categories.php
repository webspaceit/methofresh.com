<?php
// Homepage "Shop by Category" block.
// Expects: $title (string), $categories (array of localized category rows).
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <div class="text-center max-w-2xl mx-auto mb-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-stone-900 tracking-tight"><?= e($title) ?></h2>
        <div class="w-12 h-1 bg-amber-500 mx-auto mt-3 rounded-full"></div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
        <?php foreach ($categories as $category): ?>
        <?php
        $catImg = $category['image'] ?? '';
        $catImgUrl = $catImg !== '' ? image_url($catImg, $category['name'], 300) : '';
        ?>
        <a href="<?= e(locale_url('/categories/' . $category['slug'])) ?>" class="group rounded-2xl overflow-hidden border border-stone-200 bg-white hover:border-amber-400 hover:shadow-xl transition flex flex-col">
            <div class="bg-stone-100 h-36 sm:h-44 flex items-center justify-center p-4">
                <?php if ($catImgUrl !== ''): ?>
                <img src="<?= e($catImgUrl) ?>" alt="<?= e($category['name']) ?>" loading="lazy" class="max-h-full max-w-full object-contain group-hover:scale-110 transition duration-500">
                <?php else: ?>
                <span class="text-4xl sm:text-5xl group-hover:scale-110 transition">🛒</span>
                <?php endif; ?>
            </div>
            <div class="p-3.5 text-center">
                <p class="text-sm sm:text-base font-bold text-stone-900 group-hover:text-brand-700 transition"><?= e($category['name']) ?></p>
                <p class="text-[11px] text-amber-600 font-medium mt-0.5"><?= e(trans('view_all')) ?> →</p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php
// Homepage "Shop by Category" block.
// Expects: $title (string), $categories (array of localized category rows).
?>
<section class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-end justify-between mb-5">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-stone-900"><?= e($title) ?></h2>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('tagline')) ?></p>
        </div>
        <a href="<?= e(locale_url('/categories')) ?>" class="text-sm font-semibold text-brand-700 hover:underline shrink-0"><?= e(trans('view_all')) ?> →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <?php foreach ($categories as $category): ?>
        <?php
        $catImg = $category['image'] ?? '';
        $catImgUrl = $catImg !== '' ? image_url($catImg, $category['name'], 300) : '';
        ?>
        <a href="<?= e(locale_url('/categories/' . $category['slug'])) ?>" class="group relative rounded-2xl overflow-hidden bg-stone-100 border border-stone-200/80 h-36 sm:h-44 flex flex-col justify-between p-3.5 hover:shadow-lg hover:-translate-y-0.5 transition duration-300">
            <div class="w-full h-20 sm:h-24 flex items-center justify-center">
                <?php if ($catImgUrl !== ''): ?>
                <img src="<?= e($catImgUrl) ?>" alt="<?= e($category['name']) ?>" loading="lazy" class="max-h-full max-w-full object-contain group-hover:scale-110 transition duration-300">
                <?php else: ?>
                <span class="text-4xl sm:text-5xl group-hover:scale-110 transition">🛒</span>
                <?php endif; ?>
            </div>
            <div class="rounded-xl bg-white/95 backdrop-blur px-3 py-2 border border-stone-200/60 shadow-xs">
                <p class="text-stone-900 font-bold text-xs sm:text-sm group-hover:text-brand-700 transition truncate"><?= e($category['name']) ?></p>
                <p class="text-stone-500 text-[10px] sm:text-[11px] truncate"><?= e($category['description'] ?? '') ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
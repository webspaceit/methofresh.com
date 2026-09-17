<section class="max-w-7xl mx-auto px-4 py-8">
    <nav class="text-xs text-stone-400 mb-6">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700"><?= e(trans('nav_home')) ?></a>
        <span> / </span>
        <span class="text-stone-600"><?= e(trans('nav_categories')) ?></span>
    </nav>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900"><?= e(trans('nav_categories')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('tagline')) ?></p>
        </div>
        <a href="<?= e(locale_url('/products')) ?>" class="rounded-xl border border-stone-200 bg-white px-4 py-2 text-xs font-bold text-stone-700 hover:border-brand-600 hover:text-brand-700 transition shadow-xs">
            <?= e(trans('view_all')) ?> →
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($categories as $category): ?>
        <?php
        $catImg = $category['image'] ?? '';
        $catImgUrl = $catImg !== '' ? image_url($catImg, $category['name'], 400) : '';
        ?>
        <a href="<?= e(locale_url('/categories/' . $category['slug'])) ?>" class="group rounded-3xl bg-white border border-stone-200/90 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition duration-300 shadow-sm flex flex-col">
            <div class="aspect-[4/3] bg-stone-100 relative overflow-hidden flex items-center justify-center p-6">
                <?php if ($catImgUrl !== ''): ?>
                <img src="<?= e($catImgUrl) ?>" alt="<?= e($category['name']) ?>" loading="lazy"
                     class="w-full h-full object-contain group-hover:scale-110 transition duration-300">
                <?php else: ?>
                <span class="text-6xl group-hover:scale-110 transition duration-300">🥦</span>
                <?php endif; ?>
            </div>
            <div class="p-5 flex-1 flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-stone-900 group-hover:text-brand-700 transition"><?= e($category['name']) ?></h2>
                    <p class="text-xs text-stone-500 mt-1.5 line-clamp-2 leading-relaxed"><?= e($category['description'] ?? '') ?></p>
                </div>
                <div class="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs font-semibold text-brand-700">
                    <span><?= e(format_number((int)($category['product_count'] ?? 0))) ?> <?= e(trans('products_count')) ?></span>
                    <span class="inline-flex items-center gap-1 group-hover:translate-x-1 transition">→</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
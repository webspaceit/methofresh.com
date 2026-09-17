<?php
$currentQuery = $_GET;
unset($currentQuery['page']);

$buildFilterUrl = function (array $overrides = []) use ($currentQuery): string {
    $params = array_merge($currentQuery, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    $query = http_build_query($params);
    return locale_url('/products') . ($query !== '' ? '?' . $query : '');
};

$hasActiveFilters = ($categoryId > 0 || $search !== '' || !empty($minPrice) || !empty($maxPrice) || $inStock || $saleOnly);

$fromItem = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
$toItem = min($total, $page * $perPage);
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <!-- Top breadcrumb and title banner -->
    <div class="mb-6">
        <nav class="text-xs text-stone-400 mb-2 flex items-center gap-1.5 flex-wrap">
            <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700 transition"><?= e(trans('nav_home')) ?></a>
            <span>/</span>
            <a href="<?= e(locale_url('/products')) ?>" class="hover:text-brand-700 transition"><?= e(trans('nav_products')) ?></a>
            <?php if ($activeCategory): ?>
                <span>/</span>
                <span class="text-stone-700 font-semibold"><?= e($activeCategory['name']) ?></span>
            <?php endif; ?>
        </nav>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-stone-900 tracking-tight">
                    <?php if ($activeCategory): ?>
                        <?= e($activeCategory['name']) ?>
                    <?php elseif ($search !== ''): ?>
                        <?= e(trans('search_results')) ?> "<span class="text-brand-700"><?= e($search) ?></span>"
                    <?php else: ?>
                        <?= e(trans('products_title')) ?>
                    <?php endif; ?>
                </h1>
                <p class="text-sm text-stone-500 mt-1" id="products-summary-text">
                    <?php if ($total > 0): ?>
                        <?= str_replace(
                            [':total', ':from', ':to'],
                            [format_number($total), format_number($fromItem), format_number($toItem)],
                            e(trans('showing_products'))
                        ) ?>
                    <?php else: ?>
                        <span class="font-semibold text-stone-700"><?= e(format_number(0)) ?></span> <?= e(trans('products_count')) ?>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Quick Pagination & View Info in Top Header -->
            <?php if ($pages > 1): ?>
            <div class="flex items-center gap-2 self-start md:self-auto bg-stone-100/80 px-3 py-1.5 rounded-xl border border-stone-200 text-xs font-semibold text-stone-600">
                <span><?= str_replace([':total', ':current'], [format_number($pages), format_number($page)], e(trans('page_of'))) ?></span>
                <div class="flex items-center gap-1 ml-2">
                    <?php if ($page > 1): ?>
                    <a href="<?= e($buildFilterUrl(['page' => $page - 1])) ?>" class="p-1 rounded-lg bg-white border border-stone-200 hover:bg-stone-50 hover:text-brand-700 transition" title="<?= e(trans('previous')) ?>">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                    <a href="<?= e($buildFilterUrl(['page' => $page + 1])) ?>" class="p-1 rounded-lg bg-white border border-stone-200 hover:bg-stone-50 hover:text-brand-700 transition" title="<?= e(trans('next')) ?>">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Filter Tags (if any) -->
    <?php if ($hasActiveFilters): ?>
    <div class="mb-6 flex flex-wrap items-center gap-2 bg-stone-50 p-3 rounded-2xl border border-stone-200/80">
        <span class="text-xs font-bold text-stone-500 uppercase tracking-wider mr-1"><?= e(trans('filters')) ?>:</span>
        
        <?php if ($activeCategory): ?>
        <span class="inline-flex items-center gap-1.5 bg-white border border-stone-200 text-stone-800 text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xs">
            <span><?= e(trans('category')) ?>: <?= e($activeCategory['name']) ?></span>
            <a href="<?= e($buildFilterUrl(['cat' => null, 'category' => null])) ?>" class="text-stone-400 hover:text-red-500 font-bold">&times;</a>
        </span>
        <?php endif; ?>

        <?php if ($search !== ''): ?>
        <span class="inline-flex items-center gap-1.5 bg-white border border-stone-200 text-stone-800 text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xs">
            <span><?= e(trans('search_results')) ?>: "<?= e($search) ?>"</span>
            <a href="<?= e($buildFilterUrl(['q' => null])) ?>" class="text-stone-400 hover:text-red-500 font-bold">&times;</a>
        </span>
        <?php endif; ?>

        <?php if ($minPrice !== null || $maxPrice !== null): ?>
        <span class="inline-flex items-center gap-1.5 bg-white border border-stone-200 text-stone-800 text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xs">
            <span><?= e(trans('filter_price')) ?>: <?= e(format_price((float)($minPrice ?? 0))) ?> – <?= $maxPrice ? e(format_price((float)$maxPrice)) : '∞' ?></span>
            <a href="<?= e($buildFilterUrl(['min_price' => null, 'max_price' => null])) ?>" class="text-stone-400 hover:text-red-500 font-bold">&times;</a>
        </span>
        <?php endif; ?>

        <?php if ($inStock): ?>
        <span class="inline-flex items-center gap-1.5 bg-white border border-stone-200 text-stone-800 text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xs">
            <span><?= e(trans('in_stock_only')) ?></span>
            <a href="<?= e($buildFilterUrl(['in_stock' => null])) ?>" class="text-stone-400 hover:text-red-500 font-bold">&times;</a>
        </span>
        <?php endif; ?>

        <?php if ($saleOnly): ?>
        <span class="inline-flex items-center gap-1.5 bg-white border border-stone-200 text-stone-800 text-xs font-semibold px-3 py-1.5 rounded-xl shadow-xs">
            <span><?= e(trans('sale_only')) ?></span>
            <a href="<?= e($buildFilterUrl(['sale_only' => null])) ?>" class="text-stone-400 hover:text-red-500 font-bold">&times;</a>
        </span>
        <?php endif; ?>

        <a href="<?= e(locale_url('/products')) ?>" class="text-xs font-bold text-red-600 hover:text-red-700 hover:underline ml-auto py-1">
            <?= e(trans('clear_filters')) ?>
        </a>
    </div>
    <?php endif; ?>

    <div class="lg:grid lg:grid-cols-[280px,1fr] gap-8 items-start">

        <!-- Filters Sidebar -->
        <aside class="mb-6 lg:mb-0 lg:sticky lg:top-24 space-y-5" x-data="{ mobileOpen: false }">
            <div class="bg-white rounded-3xl border border-stone-200/90 shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <h2 class="text-base font-bold text-stone-900"><?= e(trans('filters')) ?></h2>
                    </div>
                    
                    <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-1.5 text-stone-500 hover:text-stone-800 rounded-lg hover:bg-stone-100">
                        <svg class="w-5 h-5 transition transform" :class="mobileOpen && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <?php if ($hasActiveFilters): ?>
                    <a href="<?= e(locale_url('/products')) ?>" class="hidden lg:inline text-xs font-semibold text-brand-700 hover:underline">
                        <?= e(trans('clear_filters')) ?>
                    </a>
                    <?php endif; ?>
                </div>

                <div :class="mobileOpen ? 'block' : 'hidden'" class="lg:block mt-4 space-y-6">
                    <!-- Categories section -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-stone-400 mb-3"><?= e(trans('filter_by_category')) ?></h3>
                        <ul class="space-y-1 text-sm font-medium">
                            <li>
                                <a href="<?= e($buildFilterUrl(['cat' => null, 'category' => null])) ?>"
                                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition <?= $categoryId === 0 ? 'bg-brand-600 text-white font-bold shadow-sm shadow-brand-600/30' : 'text-stone-700 hover:bg-stone-100' ?>">
                                    <span><?= e(trans('all_categories')) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= $categoryId === 0 ? 'bg-white/20 text-white font-semibold' : 'bg-stone-100 text-stone-500' ?>">
                                        <?= e(format_number($totalAllProducts)) ?>
                                    </span>
                                </a>
                            </li>
                            <?php foreach ($categories as $category): ?>
                            <?php $isActiveCat = ($categoryId === (int)$category['id']); ?>
                            <li>
                                <a href="<?= e($buildFilterUrl(['cat' => $category['slug'], 'category' => null])) ?>"
                                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition <?= $isActiveCat ? 'bg-brand-600 text-white font-bold shadow-sm shadow-brand-600/30' : 'text-stone-700 hover:bg-stone-100' ?>">
                                    <span><?= e($category['name']) ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full <?= $isActiveCat ? 'bg-white/20 text-white font-semibold' : 'bg-stone-100 text-stone-500' ?>">
                                        <?= e(format_number($category['product_count'] ?? 0)) ?>
                                    </span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Price Filter section -->
                    <div class="pt-4 border-t border-stone-100">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-stone-400 mb-3"><?= e(trans('filter_price')) ?></h3>
                        <form method="get" action="<?= e(locale_url('/products')) ?>" class="space-y-3">
                            <?php if ($categoryId > 0): ?>
                            <input type="hidden" name="category" value="<?= (int)$categoryId ?>">
                            <?php endif; ?>
                            <?php if ($search !== ''): ?>
                            <input type="hidden" name="q" value="<?= e($search) ?>">
                            <?php endif; ?>
                            <?php if ($sort !== 'newest'): ?>
                            <input type="hidden" name="sort" value="<?= e($sort) ?>">
                            <?php endif; ?>
                            <?php if ($perPage !== 12): ?>
                            <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">
                            <?php endif; ?>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[11px] font-semibold text-stone-500 block mb-1"><?= e(trans('min_price')) ?></label>
                                    <input type="number" name="min_price" value="<?= $minPrice !== null ? e((string)$minPrice) : '' ?>" placeholder="<?= app_locale() === 'bn' ? '০' : '0' ?>" min="0" step="5"
                                           class="w-full px-3 py-2 text-xs rounded-xl border border-stone-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-stone-500 block mb-1"><?= e(trans('max_price')) ?></label>
                                    <input type="number" name="max_price" value="<?= $maxPrice !== null ? e((string)$maxPrice) : '' ?>" placeholder="<?= app_locale() === 'bn' ? '১০০০' : '1000' ?>" min="0" step="5"
                                           class="w-full px-3 py-2 text-xs rounded-xl border border-stone-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                                </div>
                            </div>

                            <button type="submit" class="w-full py-2 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition shadow-xs">
                                <?= e(trans('apply_filter')) ?>
                            </button>
                        </form>
                    </div>

                    <!-- Availability & Deals Section -->
                    <div class="pt-4 border-t border-stone-100 space-y-2.5">
                        <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-stone-700 select-none">
                            <input type="checkbox" onchange="window.location.href='<?= e($buildFilterUrl(['in_stock' => $inStock ? null : 1])) ?>'"
                                   <?= $inStock ? 'checked' : '' ?>
                                   class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-stone-300">
                            <span><?= e(trans('in_stock_only')) ?></span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer text-sm font-medium text-stone-700 select-none">
                            <input type="checkbox" onchange="window.location.href='<?= e($buildFilterUrl(['sale_only' => $saleOnly ? null : 1])) ?>'"
                                   <?= $saleOnly ? 'checked' : '' ?>
                                   class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-stone-300">
                            <span><?= e(trans('sale_only')) ?></span>
                        </label>
                    </div>

                    <!-- Pagination Sidebar Info -->
                    <div class="pt-4 border-t border-stone-100 text-xs text-stone-500 flex items-center justify-between">
                        <span><?= str_replace([':current', ':total'], [format_number($page), format_number($pages)], e(trans('page_of'))) ?></span>
                        <span class="font-semibold text-stone-700"><?= format_number($total) ?> <?= e(trans('products_count')) ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Product Section -->
        <div>
            <!-- Top Controls Toolbar (Sort, Per-Page, Search, Active Stats) -->
            <div class="bg-white rounded-2xl border border-stone-200 p-3.5 mb-6 flex flex-wrap items-center justify-between gap-3 shadow-xs">
                <!-- Left: Per page selector & Searchbox -->
                <div class="flex items-center gap-3 flex-wrap flex-1 min-w-[280px]">
                    <!-- Per page selector -->
                    <div class="flex items-center gap-2 text-xs font-medium text-stone-600 shrink-0">
                        <label class="whitespace-nowrap"><?= e(trans('per_page')) ?>:</label>
                        <select onchange="window.location.href='<?= e($buildFilterUrl(['per_page' => 'PLACEHOLDER'])) ?>'.replace('PLACEHOLDER', this.value)"
                                class="rounded-xl border border-stone-200 bg-stone-50 px-2.5 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                            <?php foreach ([8, 12, 24, 36, 48] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= e(format_number($opt)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Live Searchbox beside Per Page -->
                    <form method="get" action="<?= e(locale_url('/products')) ?>" class="relative flex-1 min-w-[200px] max-w-sm" x-data="liveSearch('<?= e(addslashes($search)) ?>')">
                        <?php if ($categoryId > 0): ?>
                        <input type="hidden" name="category" value="<?= (int)$categoryId ?>">
                        <?php endif; ?>
                        <?php if ($sort !== 'newest'): ?>
                        <input type="hidden" name="sort" value="<?= e($sort) ?>">
                        <?php endif; ?>
                        <?php if ($perPage !== 12): ?>
                        <input type="hidden" name="per_page" value="<?= (int)$perPage ?>">
                        <?php endif; ?>
                        <?php if ($minPrice !== null): ?>
                        <input type="hidden" name="min_price" value="<?= e((string)$minPrice) ?>">
                        <?php endif; ?>
                        <?php if ($maxPrice !== null): ?>
                        <input type="hidden" name="max_price" value="<?= e((string)$maxPrice) ?>">
                        <?php endif; ?>
                        <?php if ($inStock): ?>
                        <input type="hidden" name="in_stock" value="1">
                        <?php endif; ?>
                        <?php if ($saleOnly): ?>
                        <input type="hidden" name="sale_only" value="1">
                        <?php endif; ?>

                        <div class="relative w-full" @click.outside="open = false">
                            <input type="text" name="q" autocomplete="off" x-model="query" role="combobox" aria-autocomplete="list"
                                   @input.debounce.300ms="runSearch()"
                                   @keydown.arrow-down.prevent="move(1)"
                                   @keydown.arrow-up.prevent="move(-1)"
                                   @keydown.enter.prevent="if (!$event.isComposing) go()"
                                   @keydown.escape="open = false"
                                   @focus="if (results.length) open = true"
                                   placeholder="<?= e(trans('nav_search')) ?>"
                                   class="w-full rounded-xl border border-stone-200 bg-stone-50 pl-8 pr-8 py-1.5 text-xs text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-stone-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                </svg>
                            </div>
                            <template x-if="query && query.length > 0">
                                <a href="<?= e($buildFilterUrl(['q' => null])) ?>" @click="query = ''" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-stone-400 hover:text-red-500 text-sm font-bold" title="<?= e(trans('clear_filters')) ?>">&times;</a>
                            </template>
                            <template x-if="!query || query.length === 0">
                                <button type="submit" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-stone-400 hover:text-brand-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </template>

                            <!-- Live Search Suggestions Dropdown -->
                            <div x-show="open" x-cloak
                                 class="absolute left-0 right-0 top-full mt-2 rounded-2xl border border-stone-200 bg-white shadow-2xl overflow-hidden z-50">
                                <template x-for="(r, i) in results" :key="r.id">
                                    <a :href="r.url" @mouseenter="active = i" @mousedown.prevent="go()"
                                       class="flex items-center gap-3 px-3.5 py-2.5 hover:bg-stone-50 transition border-b border-stone-50 last:border-0"
                                       :class="i === active ? 'bg-stone-50' : ''">
                                        <img :src="r.image" :alt="r.name" class="w-8 h-8 rounded-lg object-cover bg-stone-100 shrink-0" loading="lazy">
                                        <div class="flex-1 min-w-0">
                                            <span class="block text-xs font-semibold text-stone-900 truncate" x-text="r.name"></span>
                                            <span class="text-[11px] font-bold text-brand-700" x-text="r.price"></span>
                                        </div>
                                    </a>
                                </template>
                                <p x-show="ready && results.length === 0" class="px-3.5 py-2.5 text-xs text-stone-500"><?= e(trans('search_no_results')) ?></p>
                                <a :href="resultsUrl + '?q=' + encodeURIComponent(query.trim())" x-show="results.length > 0" x-cloak
                                   class="block px-3.5 py-2 border-t border-stone-100 text-xs font-bold text-brand-700 hover:bg-stone-50 transition"><?= e(trans('search_see_all')) ?> →</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right: Sort dropdown -->
                <div class="flex items-center gap-2 shrink-0">
                    <label class="text-xs font-medium text-stone-600 whitespace-nowrap"><?= e(trans('sort_default')) ?>:</label>
                    <select onchange="window.location.href='<?= e($buildFilterUrl(['sort' => 'PLACEHOLDER'])) ?>'.replace('PLACEHOLDER', this.value)"
                            class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-1.5 text-xs font-semibold focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= e(trans('sort_newest')) ?></option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>><?= e(trans('sort_price_low')) ?></option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= e(trans('sort_price_high')) ?></option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= e(trans('sort_popular')) ?></option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if (empty($products)): ?>
            <div class="text-center py-20 bg-white rounded-3xl border border-stone-200 shadow-xs">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-stone-100 flex items-center justify-center text-4xl">
                    🧺
                </div>
                <h2 class="text-xl font-bold text-stone-800"><?= e(trans('no_products')) ?></h2>
                <p class="text-sm text-stone-500 mt-1 max-w-sm mx-auto"><?= e(trans('no_products_hint')) ?></p>
                <a href="<?= e(locale_url('/products')) ?>" class="inline-flex items-center gap-2 mt-6 rounded-2xl bg-brand-600 text-white px-6 py-3 text-sm font-bold hover:bg-brand-700 shadow-sm transition">
                    <?= e(trans('view_all')) ?>
                </a>
            </div>
            <?php else: ?>
            <div id="products-grid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
                <?php foreach ($products as $product) include views_path('partials/wsit_product-card.php'); ?>
            </div>

            <!-- Scroll Sentinel (Observed for infinite scroll on bottom) -->
            <div id="scroll-sentinel" class="h-4 w-full"></div>

            <!-- Animated Loading Spinner when mouse scrolls to bottom -->
            <div id="scroll-loading-indicator" class="hidden my-8 text-center">
                <div class="inline-flex items-center gap-3 px-5 py-3 rounded-2xl bg-white border border-stone-200 shadow-md text-stone-700 text-sm font-semibold animate-pulse">
                    <svg class="w-5 h-5 animate-spin text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span><?= e(trans('loading')) ?></span>
                </div>
            </div>

            <!-- End of products message -->
            <div id="all-loaded-badge" class="<?= ($page >= $pages) ? 'block' : 'hidden' ?> my-8 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-stone-100 text-stone-500 text-xs font-semibold">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span><?= e(trans('all_products_loaded')) ?></span>
                </div>
            </div>

            <!-- Fallback Load More Button -->
            <?php if ($pages > 1): ?>
            <div id="load-more-container" class="<?= ($page >= $pages) ? 'hidden' : 'block' ?> my-6 text-center">
                <button id="load-more-btn"
                        data-page="<?= (int)$page ?>"
                        data-pages="<?= (int)$pages ?>"
                        data-total="<?= (int)$total ?>"
                        data-category="<?= (int)$categoryId ?>"
                        data-search="<?= e($search) ?>"
                        data-sort="<?= e($sort) ?>"
                        data-perpage="<?= (int)$perPage ?>"
                        data-minprice="<?= $minPrice !== null ? e((string)$minPrice) : '' ?>"
                        data-maxprice="<?= $maxPrice !== null ? e((string)$maxPrice) : '' ?>"
                        data-instock="<?= $inStock ? '1' : '0' ?>"
                        data-saleonly="<?= $saleOnly ? '1' : '0' ?>"
                        class="inline-flex items-center gap-2 rounded-2xl border-2 border-stone-200 bg-white px-8 py-3 text-sm font-bold text-stone-700 hover:border-brand-600 hover:text-brand-700 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    <span><?= e(trans('load_more_products')) ?></span>
                </button>
                <p class="text-xs text-stone-400 mt-2"><?= e(trans('scroll_for_more')) ?></p>
            </div>
            <?php endif; ?>

            <!-- Bottom Pagination Navigation -->
            <?php include views_path('partials/wsit_pagination.php'); ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Infinite Scroll & Dynamic Pagination Script -->
<script>
(function () {
    const grid = document.getElementById('products-grid');
    const btn = document.getElementById('load-more-btn');
    const sentinel = document.getElementById('scroll-sentinel');
    const loader = document.getElementById('scroll-loading-indicator');
    const allLoadedBadge = document.getElementById('all-loaded-badge');
    const loadMoreContainer = document.getElementById('load-more-container');
    const summaryText = document.getElementById('products-summary-text');

    if (!grid || !btn) return;

    let currentPage = parseInt(btn.dataset.page, 10) || 1;
    const maxPages = parseInt(btn.dataset.pages, 10) || 1;
    const totalProducts = parseInt(btn.dataset.total, 10) || 0;
    const perPage = parseInt(btn.dataset.perpage, 10) || 12;
    let isLoading = false;

    function toBengaliDigits(num) {
        <?php if (app_locale() === 'bn'): ?>
        const bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return String(num).replace(/[0-9]/g, w => bnDigits[+w]);
        <?php else: ?>
        return String(num);
        <?php endif; ?>
    }

    function updateSummary(loadedCount) {
        if (!summaryText) return;
        const fromStr = toBengaliDigits(1);
        const toStr = toBengaliDigits(Math.min(totalProducts, loadedCount));
        const totalStr = toBengaliDigits(totalProducts);
        summaryText.innerHTML = '<?= e(trans('showing_products')) ?>'
            .replace(':from', fromStr)
            .replace(':to', toStr)
            .replace(':total', totalStr);
    }

    function fetchNextPage() {
        if (isLoading || currentPage >= maxPages) return;
        isLoading = true;

        if (loader) loader.classList.remove('hidden');
        if (btn) btn.disabled = true;

        const nextPage = currentPage + 1;
        const params = new URLSearchParams({
            page: nextPage,
            per_page: perPage,
            category: btn.dataset.category || '0',
            q: btn.dataset.search || '',
            sort: btn.dataset.sort || 'newest',
            min_price: btn.dataset.minprice || '',
            max_price: btn.dataset.maxprice || '',
            in_stock: btn.dataset.instock || '0',
            sale_only: btn.dataset.saleonly || '0',
        });

        fetch('<?= locale_url('/products/load-more') ?>?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response error');
            return response.json();
        })
        .then(data => {
            if (data.html && data.html.trim()) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;
                while (tempDiv.firstChild) {
                    grid.appendChild(tempDiv.firstChild);
                }
            }

            currentPage = data.page;
            btn.dataset.page = currentPage;

            updateSummary(data.loaded || (currentPage * perPage));

            if (data.has_more && currentPage < maxPages) {
                if (btn) btn.disabled = false;
            } else {
                if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                if (allLoadedBadge) allLoadedBadge.classList.remove('hidden');
                if (observer && sentinel) observer.unobserve(sentinel);
            }
        })
        .catch(err => {
            console.error('Error loading products on scroll:', err);
            if (btn) btn.disabled = false;
        })
        .finally(() => {
            isLoading = false;
            if (loader) loader.classList.add('hidden');
        });
    }

    // Manual button click handler
    btn.addEventListener('click', fetchNextPage);

    // IntersectionObserver for bottom mouse scroll detection
    let observer = null;
    if ('IntersectionObserver' in window && sentinel) {
        observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isLoading && currentPage < maxPages) {
                    fetchNextPage();
                }
            });
        }, {
            rootMargin: '350px 0px',
            threshold: 0.01
        });

        observer.observe(sentinel);
    }

    // Fallback window scroll event listener
    let scrollTimeout = null;
    window.addEventListener('scroll', () => {
        if (scrollTimeout) return;
        scrollTimeout = setTimeout(() => {
            scrollTimeout = null;
            if (isLoading || currentPage >= maxPages) return;
            const scrollPos = window.innerHeight + window.pageYOffset;
            const threshold = document.documentElement.scrollHeight - 500;
            if (scrollPos >= threshold) {
                fetchNextPage();
            }
        }, 120);
    }, { passive: true });
})();
</script>
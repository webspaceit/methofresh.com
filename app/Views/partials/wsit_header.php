<?php
$path = current_page_path();
$locale = app_locale();
$currentUser = current_user();
$headerPhone = site_setting('footer_phone', '01700-000000');
$annOther = $locale === 'bn' ? 'en' : 'bn';
$annSlides = [];
foreach (announcement_offers() as $annOffer) {
    $annBadge = $annOffer['offer_' . $locale] !== '' ? $annOffer['offer_' . $locale] : $annOffer['offer_' . $annOther];
    $annMsg   = $annOffer['coupon_' . $locale] !== '' ? $annOffer['coupon_' . $locale] : $annOffer['coupon_' . $annOther];
    if ($annBadge === '' && $annMsg === '' && $annOffer['code'] === '') {
        continue;
    }
    $annSlides[] = ['badge' => $annBadge, 'msg' => $annMsg, 'code' => $annOffer['code']];
}
$headerCategories = app()->get('footer_categories') ?? \App\Models\wsit_Category::allStatic();
$headerQuick = array_slice(array_values($headerCategories), 0, 4);
$headerAllCategories = array_values($headerCategories);
$pathIsHome = $path === '';
?>
<!-- BEGIN: AnnouncementBar -->
<div class="bg-brand-800 text-white text-xs sm:text-sm py-2 px-4" data-purpose="announcement-bar">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-2">
        <div class="w-full md:w-auto md:flex-1 md:pr-6 min-w-0 h-6 overflow-hidden"
             x-data="{ i: 0, n: <?= count($annSlides) ?>, t: null }"
             x-init="n > 1 && (t = setInterval(() => i = (i + 1) % n, 4500))"
             @mouseenter="if (t) { clearInterval(t); t = null; }"
             @mouseleave="if (n > 1 && !t) { t = setInterval(() => i = (i + 1) % n, 4500); }">
            <div class="ann-slider flex flex-col" :style="'transform: translateY(calc(-' + i + ' * 100% / ' + n + '))'">
                <?php foreach ($annSlides as $annSlide): ?>
                <div class="w-full h-6 shrink-0 flex items-center space-x-2 min-w-0 mb-2">
                    <?php if ($annSlide['badge'] !== ''): ?>
                    <span class="bg-amber-500 text-stone-900 font-bold px-2 py-0.5 rounded text-[11px] uppercase tracking-wide whitespace-nowrap shrink-0"><?= e($annSlide['badge']) ?></span>
                    <?php endif; ?>
                    <?php if ($annSlide['msg'] !== '' || $annSlide['code'] !== ''): ?>
                    <span class="min-w-0 overflow-hidden text-ellipsis whitespace-nowrap"><?= e($annSlide['msg']) ?><?php if ($annSlide['code'] !== ''): ?> <strong class="text-amber-400 font-semibold tracking-wider"><?= e($annSlide['code']) ?></strong><?php endif; ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex items-center space-x-6 text-xs text-stone-300 shrink-0">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>
                <?= e(trans('ann_help')) ?>: <?= e($headerPhone) ?>
            </span>
            <span class="hidden sm:inline">|</span>
            <span class="hidden sm:inline"><?= e(trans('ann_cod')) ?></span>
        </div>
    </div>
</div>
<!-- END: AnnouncementBar -->
<!-- BEGIN: MainHeader -->
<header class="bg-[#142e1c] border-b border-white/15 top-0 z-50 shadow-sm" data-purpose="primary-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
        <div class="flex items-center justify-between gap-4">

            <!-- Mobile hamburger -->
            <button @click="openMobile = !openMobile" class="lg:hidden p-2 -ml-2 rounded-lg text-white hover:bg-white/10"
                    aria-label="<?= e(trans('nav_menu')) ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Logo -->
            <a href="<?= e(locale_url('/')) ?>" class="flex items-center gap-2 group shrink-0">
                <?php $logo = site_logo_url(); ?>
                <?php if ($logo !== ''): ?>
                    <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>"
                         style="<?= e(site_logo_style()) ?>"
                         class="object-contain max-w-[200px]">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-brand-700 flex items-center justify-center text-amber-400 font-bold text-xl shadow-inner">
                        <?= e(mb_substr(trans('site_name'), 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </a>

            <!-- Search Bar with Quick Tags -->
            <div class="hidden lg:flex flex-col flex-1 max-w-xl mx-8">
                <form action="<?= e(locale_url('/products')) ?>" method="get" x-data="liveSearch()">
                    <div class="relative" @click.outside="open = false">
                        <input type="text" name="q" autocomplete="off" x-model="query" role="combobox" aria-autocomplete="list"
                               @input.debounce.300ms="runSearch()"
                               @keydown.arrow-down.prevent="move(1)"
                               @keydown.arrow-up.prevent="move(-1)"
                               @keydown.enter.prevent="if (!$event.isComposing) go()"
                               @keydown.escape="open = false"
                               @focus="if (results.length) open = true"
                               placeholder="<?= e(trans('nav_search')) ?>"
                               class="w-full bg-stone-50 border border-stone-300 rounded-full py-2 pl-4 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-brand-700 focus:border-brand-700 focus:bg-white transition">
                        <button type="submit" class="absolute right-3 top-2.5 text-stone-300 hover:text-white" title="<?= e(trans('nav_products')) ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                        </button>
                        <div x-show="open" x-cloak
                             class="absolute left-0 right-0 top-full mt-2 rounded-xl border border-stone-200 bg-white shadow-xl overflow-hidden z-50">
                            <template x-for="(r, i) in results" :key="r.id">
                                <a :href="r.url" @mouseenter="active = i" @mousedown.prevent="go()"
                                   class="flex items-center gap-3 px-3 py-2.5 hover:bg-stone-50 transition"
                                   :class="i === active ? 'bg-stone-50' : ''">
                                    <img :src="r.image" :alt="r.name" class="w-9 h-9 rounded-lg object-cover bg-stone-100 shrink-0" loading="lazy">
                                    <span class="flex-1 min-w-0 text-sm font-medium text-stone-900 truncate" x-text="r.name"></span>
                                    <span class="text-sm font-bold text-brand-700 shrink-0" x-text="r.price"></span>
                                </a>
                            </template>
                            <p x-show="ready && results.length === 0" class="px-3 py-2.5 text-sm text-stone-500"><?= e(trans('search_no_results')) ?></p>
                            <a :href="resultsUrl + '?q=' + encodeURIComponent((query || '').trim())" x-show="results.length > 0" x-cloak
                               class="block px-3 py-2.5 border-t border-stone-200 text-sm font-semibold text-brand-700 hover:bg-stone-50 transition"><?= e(trans('search_see_all')) ?> →</a>
                        </div>
                    </div>
                </form>
                <?php if (count($headerQuick) > 0): ?>
                <div class="flex items-center gap-2 mt-1.5 text-[11px] text-stone-300 pl-3">
                    <span><?= e(trans('popular_label')) ?></span>
                    <?php foreach ($headerQuick as $quickCat): ?>
                    <a class="hover:text-white underline decoration-white/40" href="<?= e(locale_url('/categories/' . $quickCat['slug'])) ?>"><?= e($quickCat['name']) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Items -->
            <div class="flex items-center space-x-2 sm:space-x-4">

                <!-- Language switcher -->
                <div class="flex items-center rounded-lg border border-white/25 p-0.5 text-xs font-semibold" x-data>
                    <a href="<?= e(locale_url_for('en', '/' . $path)) ?>?lang=en"
                       class="px-2 py-1 rounded-md transition <?= $locale === 'en' ? 'bg-amber-500 text-stone-900' : 'text-stone-300 hover:text-white' ?>">EN</a>
                    <a href="<?= e(locale_url_for('bn', '/' . $path)) ?>?lang=bn"
                       class="px-2 py-1 rounded-md transition font-bengali <?= $locale === 'bn' ? 'bg-amber-500 text-stone-900' : 'text-stone-300 hover:text-white' ?>">বাংলা</a>
                </div>

                <!-- Account -->
                <?php if ($currentUser): ?>
                <div x-data="{ openAccount: false }" class="relative hidden sm:block">
                    <button @click="openAccount = !openAccount" @click.outside="openAccount = false"
                            class="flex flex-col items-center text-stone-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </button>
                    <div x-show="openAccount" x-cloak x-transition
                         class="absolute right-0 top-full mt-1.5 w-48 rounded-xl border border-stone-200 bg-white p-1.5 shadow-xl z-50 text-sm">
                        <a href="<?= e(locale_url('/account')) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-stone-50 font-medium text-stone-800 transition"><?= e(trans('nav_account')) ?></a>
                        <?php if ((int)$currentUser['is_admin'] === 1): ?>
                        <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-stone-50 font-semibold text-brand-700 transition"><?= e(trans('nav_admin')) ?></a>
                        <?php endif; ?>
                        <div class="my-1 border-t border-stone-100"></div>
                        <a href="<?= e(locale_url('/logout')) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 font-medium transition"><?= e(trans('nav_logout')) ?></a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?= e(locale_url('/login')) ?>" class="hidden sm:flex flex-col items-center text-stone-300 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-[11px] mt-0.5"><?= e(trans('nav_login')) ?></span>
                </a>
                <?php endif; ?>

                <!-- Cart -->
                <a href="<?= e(locale_url('/cart')) ?>" class="relative flex flex-col items-center text-stone-300 hover:text-white transition" aria-label="<?= e(trans('nav_cart')) ?>">
                    <span class="relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span x-show="cartCount > 0" x-cloak
                              class="absolute -top-1.5 -right-2 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 text-white text-[10px] font-bold px-1">
                            <span x-text="cartCount"></span>
                        </span>
                    </span>
                    <span class="text-[11px] mt-0.5"><?= e(trans('nav_cart')) ?></span>
                </a>

                <a class="hidden sm:inline-flex items-center gap-1.5 bg-amber-500 text-stone-900 hover:bg-amber-400 text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm" href="<?= e(locale_url('/products')) ?>">
                    <span><?= e(trans('order_btn')) ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Category Mega Navigation -->
    <nav class="border-t border-white/10 bg-[#142e1c]" data-purpose="main-navigation">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <ul class="flex items-center justify-start lg:justify-center space-x-6 sm:space-x-8 overflow-x-auto lg:overflow-x-visible py-2.5 text-sm font-medium text-stone-200 scrollbar-none whitespace-nowrap">
                <li><a class="<?= $pathIsHome ? 'text-amber-400 font-semibold border-b-2 border-amber-400 pb-1' : 'hover:text-amber-400' ?>" href="<?= e(locale_url('/')) ?>"><?= e(trans('nav_home')) ?></a></li>
                <li class="relative group">
                    <a class="hover:text-amber-400 flex items-center gap-1 py-1" href="<?= e(locale_url('/categories')) ?>">
                        <span><?= e(trans('nav_all_categories')) ?></span>
                        <svg class="w-3.5 h-3.5 text-stone-400 group-hover:text-amber-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                    <?php if (count($headerAllCategories) > 0): ?>
                    <div class="absolute left-0 top-full hidden group-hover:block z-50">
                        <div class="pt-2">
                            <div class="w-64 rounded-xl border border-white/10 bg-[#0B1F16] shadow-xl p-2">
                                <?php foreach ($headerAllCategories as $cat): ?>
                                <a href="<?= e(locale_url('/categories/' . $cat['slug'])) ?>" class="block px-3 py-2 rounded-lg text-sm font-medium text-stone-200 hover:bg-white/10 hover:text-amber-400 transition"><?= e($cat['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </li>
                <?php foreach ($headerQuick as $cat): ?>
                <li><a class="hover:text-amber-400 py-1" href="<?= e(locale_url('/categories/' . $cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
                <?php endforeach; ?>
                <li><a class="hover:text-amber-400 py-1" href="<?= e(locale_url('/#story')) ?>"><?= e(trans('land_story_badge')) ?></a></li>
                <li><a class="hover:text-amber-400 py-1" href="<?= e(locale_url('/#faq')) ?>"><?= e(trans('land_faq_title')) ?></a></li>
                <?php foreach (nav_menu_items() as $navItem): ?>
                <?php if (empty($navItem['enabled'])) continue; ?>
                <?php $navLabel = trim((string)($navItem['label_' . $locale] ?? '')) ?: trim((string)($navItem['label_en'] ?? '')); ?>
                <?php $navUrl = trim((string)($navItem['url'] ?? '')); ?>
                <?php if ($navLabel === '' || $navUrl === '') continue; ?>
                <li>
                    <a class="hover:text-amber-400 py-1 flex items-center gap-1.5" href="<?= e(nav_menu_url($navUrl)) ?>"<?= !empty($navItem['target']) ? ' target="_blank" rel="noopener"' : '' ?>>
                        <?php if (!empty($navItem['icon']) && nav_icon_valid($navItem['icon'])): ?><span class="material-symbols-outlined text-[16px]"><?= e($navItem['icon']) ?></span><?php endif; ?>
                        <?= e($navLabel) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <!-- Mobile search -->
    <div class="lg:hidden px-4 pb-3" x-data="liveSearch()">
        <form action="<?= e(locale_url('/products')) ?>" method="get">
            <div class="relative" @click.outside="open = false">
                <input type="text" name="q" autocomplete="off" x-model="query" role="combobox" aria-autocomplete="list"
                       @input.debounce.300ms="runSearch()"
                       @keydown.arrow-down.prevent="move(1)"
                       @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="if (!$event.isComposing) go()"
                       @keydown.escape="open = false"
                       @focus="if (results.length) open = true"
                       placeholder="<?= e(trans('nav_search')) ?>"
                       class="w-full bg-stone-50 border border-stone-300 rounded-full py-2 pl-4 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-700 focus:border-brand-700 transition">
                <div x-show="open" x-cloak
                     class="absolute left-0 right-0 top-full mt-2 rounded-xl border border-stone-200 bg-white shadow-xl overflow-hidden z-50">
                    <template x-for="(r, i) in results" :key="r.id">
                        <a :href="r.url" @mouseenter="active = i"
                           class="flex items-center gap-3 px-3 py-2.5 hover:bg-stone-50 transition"
                           :class="i === active ? 'bg-stone-50' : ''">
                            <img :src="r.image" :alt="r.name" class="w-9 h-9 rounded-lg object-cover bg-stone-100 shrink-0" loading="lazy">
                            <span class="flex-1 min-w-0 text-sm font-medium text-stone-900 truncate" x-text="r.name"></span>
                            <span class="text-sm font-bold text-brand-700 shrink-0" x-text="r.price"></span>
                        </a>
                    </template>
                    <p x-show="ready && results.length === 0" class="px-3 py-2.5 text-sm text-stone-500"><?= e(trans('search_no_results')) ?></p>
                    <a :href="resultsUrl + '?q=' + encodeURIComponent((query || '').trim())" x-show="results.length > 0" x-cloak
                       class="block px-3 py-2.5 border-t border-stone-200 text-sm font-semibold text-brand-700 hover:bg-stone-50 transition"><?= e(trans('search_see_all')) ?> →</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Mobile menu -->
    <div x-show="openMobile" x-cloak x-transition.opacity
         class="lg:hidden border-t border-stone-200 bg-white shadow-lg">
        <nav class="px-4 py-3 space-y-1 text-sm font-medium">
            <a href="<?= e(locale_url('/')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('nav_home')) ?></a>
            <a href="<?= e(locale_url('/products')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('nav_products')) ?></a>
            <a href="<?= e(locale_url('/categories')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('nav_all_categories')) ?></a>
            <?php foreach ($headerQuick as $cat): ?>
            <a href="<?= e(locale_url('/categories/' . $cat['slug'])) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(locale_url('/#story')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('land_story_badge')) ?></a>
            <?php foreach (nav_menu_items() as $navItem): ?>
            <?php if (empty($navItem['enabled'])) continue; ?>
            <?php $navLabel = trim((string)($navItem['label_' . $locale] ?? '')) ?: trim((string)($navItem['label_en'] ?? '')); ?>
            <?php $navUrl = trim((string)($navItem['url'] ?? '')); ?>
            <?php if ($navLabel === '' || $navUrl === '') continue; ?>
            <a href="<?= e(nav_menu_url($navUrl)) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50 flex items-center gap-2"<?= !empty($navItem['target']) ? ' target="_blank" rel="noopener"' : '' ?>>
                <?php if (!empty($navItem['icon']) && nav_icon_valid($navItem['icon'])): ?><span class="material-symbols-outlined text-[16px]"><?= e($navItem['icon']) ?></span><?php endif; ?>
                <?= e($navLabel) ?>
            </a>
            <?php endforeach; ?>
            <?php if ($currentUser): ?>
            <a href="<?= e(locale_url('/account')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('nav_account')) ?></a>
            <?php if ((int)$currentUser['is_admin'] === 1): ?>
            <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="block px-3 py-2 rounded-lg text-brand-700 font-semibold"><?= e(trans('nav_admin')) ?></a>
            <?php endif; ?>
            <a href="<?= e(locale_url('/logout')) ?>" class="block px-3 py-2 rounded-lg text-red-600 hover:bg-red-50"><?= e(trans('nav_logout')) ?></a>
            <?php else: ?>
            <a href="<?= e(locale_url('/login')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('nav_login')) ?></a>
            <a href="<?= e(locale_url('/register')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-50"><?= e(trans('nav_register')) ?></a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- In-page toast (from AJAX add-to-cart) -->
    <template x-teleport="body">
        <div class="fixed top-20 right-4 z-[60] space-y-2 w-[calc(100%-2rem)] max-w-sm">
            <div x-show="toast.show" x-transition.opacity.duration.300ms
                 class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium"
                 :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs"
                      x-text="toast.type === 'success' ? '✓' : '!'"></span>
                <span x-text="toast.message"></span>
                <button type="button" @click="toast.show = false" class="ml-auto text-white/80 hover:text-white">✕</button>
            </div>
        </div>
    </template>
</header>
<!-- END: MainHeader -->
<?php
$path = current_page_path();
$locale = app_locale();
$currentUser = current_user();
?>
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-stone-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center gap-3 h-16">

            <!-- Mobile hamburger -->
            <button @click="openMobile = !openMobile" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-stone-100"
                    aria-label="<?= e(trans('nav_menu')) ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Brand -->
            <a href="<?= e(locale_url('/')) ?>" class="flex items-center gap-2 shrink-0">
                <?php $logo = site_logo_url(); ?>
                <?php if ($logo !== ''): ?>
                    <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>" class="h-9 w-auto max-w-[150px] object-contain">
                <?php else: ?>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white text-lg font-black"><?= e(mb_substr(trans('site_name'), 0, 1)) ?></span>
                <?php endif; ?>
                <span class="hidden sm:block text-lg font-extrabold tracking-tight text-brand-700"><?= e(trans('site_name')) ?></span>
            </a>

            <!-- Desktop nav -->
            <nav class="hidden lg:flex items-center gap-1 ml-6 text-sm font-medium">
                <a href="<?= e(locale_url('/')) ?>" class="px-3 py-2 rounded-lg hover:bg-stone-100 <?= $path === '' ? 'text-brand-700' : '' ?>"><?= e(trans('nav_home')) ?></a>
                <a href="<?= e(locale_url('/products')) ?>" class="px-3 py-2 rounded-lg hover:bg-stone-100 <?= str_starts_with($path, 'products') ? 'text-brand-700' : '' ?>"><?= e(trans('nav_products')) ?></a>
                <a href="<?= e(locale_url('/categories')) ?>" class="px-3 py-2 rounded-lg hover:bg-stone-100 <?= str_starts_with($path, 'categories') ? 'text-brand-700' : '' ?>"><?= e(trans('nav_categories')) ?></a>
                <?php foreach (\App\Models\wsit_Page::activeMenu() as $menuPage): ?>
                    <a href="<?= e(locale_url('/pages/' . $menuPage['slug'])) ?>" class="px-3 py-2 rounded-lg hover:bg-stone-100 <?= str_starts_with($path, 'pages/') ? 'text-brand-700' : '' ?>"><?= e($menuPage['title']) ?></a>
                <?php endforeach; ?>
            </nav>

            <!-- Search (desktop) -->
            <form action="<?= e(locale_url('/products')) ?>" method="get" class="hidden md:flex flex-1 max-w-md mx-auto" x-data="liveSearch()">
                <div class="relative w-full" @click.outside="open = false">
                    <input type="text" name="q" autocomplete="off" x-model="query" role="combobox" aria-autocomplete="list"
                           @input.debounce.300ms="runSearch()"
                           @keydown.arrow-down.prevent="move(1)"
                           @keydown.arrow-up.prevent="move(-1)"
                           @keydown.enter.prevent="if (!$event.isComposing) go()"
                           @keydown.escape="open = false"
                           @focus="if (results.length) open = true"
                           placeholder="<?= e(trans('nav_search')) ?>"
                           class="w-full rounded-xl border border-stone-200 bg-stone-100 pl-4 pr-10 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
                    <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                        </svg>
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
                           class="block px-3 py-2.5 border-t border-stone-100 text-sm font-semibold text-brand-700 hover:bg-stone-50 transition"><?= e(trans('search_see_all')) ?> →</a>
                    </div>
                </div>
            </form>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">

                <!-- Language switcher -->
                <div class="flex items-center rounded-lg border border-stone-200 p-0.5 text-xs font-semibold" x-data>
                    <a href="<?= e(locale_url_for('en', '/' . $path)) ?>?lang=en"
                       class="px-2 py-1 rounded-md transition <?= $locale === 'en' ? 'bg-brand-600 text-white' : 'text-stone-600 hover:text-brand-700' ?>">EN</a>
                    <a href="<?= e(locale_url_for('bn', '/' . $path)) ?>?lang=bn"
                       class="px-2 py-1 rounded-md transition font-bengali <?= $locale === 'bn' ? 'bg-brand-600 text-white' : 'text-stone-600 hover:text-brand-700' ?>"
                       style="font-family: 'Noto Sans Bengali', sans-serif;">বাংলা</a>
                </div>

                <!-- Account -->
                <?php if ($currentUser): ?>
                <div x-data="{ openAccount: false }" class="relative hidden sm:block">
                    <button @click="openAccount = !openAccount" @click.outside="openAccount = false"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium hover:bg-stone-100 transition">
                        <svg class="w-5 h-5 text-stone-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="hidden xl:inline max-w-[8rem] truncate"><?= e(user_display_name($currentUser)) ?></span>
                        <svg class="w-3.5 h-3.5 text-stone-400 transition" :class="openAccount ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="openAccount" x-cloak x-transition
                         class="absolute right-0 top-full mt-1.5 w-48 rounded-xl border border-stone-200 bg-white p-1.5 shadow-xl z-50 text-sm">
                        <a href="<?= e(locale_url('/account')) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-stone-50 font-medium text-stone-800 transition">
                            <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?= e(trans('nav_account')) ?>
                        </a>
                        <?php if ((int)$currentUser['is_admin'] === 1): ?>
                        <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-stone-50 font-semibold text-brand-700 transition">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <?= e(trans('nav_admin')) ?>
                        </a>
                        <?php endif; ?>
                        <div class="my-1 border-t border-stone-100"></div>
                        <a href="<?= e(locale_url('/logout')) ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 font-medium transition">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <?= e(trans('nav_logout')) ?>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?= e(locale_url('/login')) ?>" class="hidden sm:block px-3 py-2 rounded-lg text-sm font-medium hover:bg-stone-100"><?= e(trans('nav_login')) ?></a>
                <?php endif; ?>

                <!-- Cart -->
                <a href="<?= e(locale_url('/cart')) ?>" class="relative px-2 py-2 rounded-lg hover:bg-stone-100" aria-label="<?= e(trans('nav_cart')) ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="cartCount > 0" x-cloak
                          class="absolute -top-0.5 -right-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 text-white text-[10px] font-bold px-1">
                        <span x-text="cartCount"></span>
                    </span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile search -->
    <div class="md:hidden px-4 pb-3" x-data="liveSearch()">
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
                       class="w-full rounded-xl border border-stone-200 bg-stone-100 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
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
                       class="block px-3 py-2.5 border-t border-stone-100 text-sm font-semibold text-brand-700 hover:bg-stone-50 transition"><?= e(trans('search_see_all')) ?> →</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Mobile menu -->
    <div x-show="openMobile" x-cloak x-transition.opacity
         class="lg:hidden border-t border-stone-100 bg-white shadow-lg">
        <nav class="px-4 py-3 space-y-1 text-sm font-medium">
            <a href="<?= e(locale_url('/')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e(trans('nav_home')) ?></a>
            <a href="<?= e(locale_url('/products')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e(trans('nav_products')) ?></a>
            <a href="<?= e(locale_url('/categories')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e(trans('nav_categories')) ?></a>
            <?php foreach (\App\Models\wsit_Page::activeMenu() as $menuPage): ?>
            <a href="<?= e(locale_url('/pages/' . $menuPage['slug'])) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e($menuPage['title']) ?></a>
            <?php endforeach; ?>
            <?php if ($currentUser): ?>
            <a href="<?= e(locale_url('/account')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e(trans('nav_account')) ?></a>
            <?php if ((int)$currentUser['is_admin'] === 1): ?>
            <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="block px-3 py-2 rounded-lg text-brand-700 font-semibold"><?= e(trans('nav_admin')) ?></a>
            <?php endif; ?>
            <a href="<?= e(locale_url('/logout')) ?>" class="block px-3 py-2 rounded-lg text-red-600 hover:bg-red-50"><?= e(trans('nav_logout')) ?></a>
            <?php else: ?>
            <a href="<?= e(locale_url('/login')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e(trans('nav_login')) ?></a>
            <a href="<?= e(locale_url('/register')) ?>" class="block px-3 py-2 rounded-lg hover:bg-stone-100"><?= e(trans('nav_register')) ?></a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- In-page toast (from AJAX add-to-cart) -->
    <template x-teleport="body">
        <div class="fixed top-20 right-4 z-[60] space-y-2 w-[calc(100%-2rem)] max-w-sm">
            <div x-show="toast.show" x-transition.opacity.duration.300ms
                 class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium"
                 :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs"
                      x-text="toast.type === 'success' ? '✓' : '!'"></span>
                <span x-text="toast.message"></span>
                <button type="button" @click="toast.show = false" class="ml-auto text-white/80 hover:text-white">✕</button>
            </div>
        </div>
    </template>
</header>
<?php
$pageTitle = $pageTitle ?? trans('admin');
$currentUser = current_user();
$locale = app_locale();
$adminPath = current_page_path();
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <?php $favicon = site_favicon_url(); ?>
    <?php if ($favicon !== ''): ?>
    <link rel="icon" href="<?= e($favicon) ?>">
    <?php else: ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🥦</text></svg>">
    <?php endif; ?>
    <script>
    (function(){
    var _e = <?= json_encode(trans('admin_translate_empty')) ?>;
    var _f = <?= json_encode(trans('admin_translate_failed')) ?>;
    window.bnTranslate = function(btn, srcSel, tgtSel) {
        var srcEl = document.querySelector(srcSel);
        var tgtEl = document.querySelector(tgtSel);
        if (!srcEl || !tgtEl) return;
        var qSrc = window.Quill && Quill.find(srcEl);
        var text = String(qSrc ? qSrc.root.innerText : (srcEl.value || '')).trim();
        if (!text) { alert(_e); return; }
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '\u2026';
        fetch('https://api.mymemory.translated.net/get?langpair=en%7Cbn&q=' + encodeURIComponent(text))
            .then(function(r){ return r.json(); })
            .then(function(d){
                var tr = d && d.responseData && d.responseData.translatedText
                    ? d.responseData.translatedText.trim() : '';
                if (!tr) throw new Error('empty');
                var qTgt = window.Quill && Quill.find(tgtEl);
                if (qTgt) {
                    var h = tr.split(/\n+/).filter(Boolean)
                        .map(function(s){ return '<p>'+s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</p>'; })
                        .join('');
                    qTgt.clipboard.dangerouslyPasteHTML(h||'<p></p>');
                } else {
                    tgtEl.value = tr;
                    tgtEl.dispatchEvent(new Event('input'));
                }
            })
            .catch(function(){ alert(_f); })
            .finally(function(){ btn.disabled=false; btn.innerHTML=orig; });
    };
    })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: <?= $locale === 'bn' ? '[\'"Noto Sans Bengali"\', \'sans-serif\']' : '[\'ui-sans-serif\', \'system-ui\', \'-apple-system\', \'BlinkMacSystemFont\', \'"Segoe UI"\', \'Roboto\', \'"Helvetica Neue"\', \'Arial\', \'sans-serif\']' ?>,
                        bengali: ['"Noto Sans Bengali"', 'sans-serif'],
                    },
                    colors: { brand: { 50:'#f0fdf4',100:'#dcfce7',500:'#22c55e',600:'#16a34a',700:'#15803d',800:'#166534' } }
                }
            }
        }
    </script>
    <?php if ($locale === 'bn'): ?>
    <style>body{font-family:'Noto Sans Bengali',sans-serif;}</style>
    <?php endif; ?>
</head>
<body class="bg-stone-100 text-stone-900 font-sans" x-data="{ sidebarOpen: false }">

<div class="min-h-screen flex flex-col lg:flex-row">

    <!-- Mobile top bar -->
    <div class="lg:hidden flex items-center justify-between bg-stone-900 text-white px-4 py-3 z-40 sticky top-0">
        <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="flex items-center gap-2 font-extrabold">
            <?php $logo = site_logo_url(); ?>
            <?php if ($logo !== ''): ?>
                <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>" class="h-8 w-auto max-w-[120px] object-contain">
            <?php else: ?>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600"><?= e(mb_substr(app_name(), 0, 1)) ?></span>
            <?php endif; ?>
            <?= e(trans('admin')) ?>
        </a>
        <div class="flex items-center gap-2">
            <a href="<?= e(locale_url('/')) ?>" target="_blank" rel="noopener" class="text-xs px-3 py-2 rounded-lg bg-stone-800 hover:bg-stone-700">🏠</a>
            <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg bg-stone-800 hover:bg-stone-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'block' : 'hidden'" class="lg:block lg:w-64 shrink-0 bg-stone-900 text-stone-300">
        <div class="lg:sticky lg:top-0 lg:h-screen flex flex-col overflow-y-auto">
            <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="hidden lg:flex items-center gap-2 px-5 py-5 font-extrabold text-white border-b border-stone-800">
                <?php $logo = site_logo_url(); ?>
                <?php if ($logo !== ''): ?>
                    <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>" class="h-9 w-auto max-w-[140px] object-contain">
                <?php else: ?>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white"><?= e(mb_substr(app_name(), 0, 1)) ?></span>
                <?php endif; ?>
                <span><?= e(trans('admin')) ?></span>
            </a>
            <nav class="px-3 py-4 space-y-1 text-sm font-medium">
                <a href="<?= e(locale_url('/mf-dashboard')) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= $adminPath === 'mf-dashboard' ? 'bg-brand-600 text-white' : 'hover:bg-stone-800 hover:text-white' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
                    <?= e(trans('admin_dashboard')) ?>
                </a>
                <a href="<?= e(locale_url('/mf-dashboard/products')) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= str_starts_with($adminPath, 'mf-dashboard/products') ? 'bg-brand-600 text-white' : 'hover:bg-stone-800 hover:text-white' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <?= e(trans('admin_products')) ?>
                </a>
                <a href="<?= e(locale_url('/mf-dashboard/categories')) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= str_starts_with($adminPath, 'mf-dashboard/categories') ? 'bg-brand-600 text-white' : 'hover:bg-stone-800 hover:text-white' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4 4m4-4l-4-4m-4 10H4m0 0l4 4m-4-4l4-4"/></svg>
                    <?= e(trans('admin_categories')) ?>
                </a>
                <a href="<?= e(locale_url('/mf-dashboard/orders')) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= str_starts_with($adminPath, 'mf-dashboard/orders') ? 'bg-brand-600 text-white' : 'hover:bg-stone-800 hover:text-white' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <?= e(trans('admin_orders')) ?>
                </a>
                <a href="<?= e(locale_url('/mf-dashboard/payment-methods')) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= str_starts_with($adminPath, 'mf-dashboard/payment-methods') ? 'bg-brand-600 text-white' : 'hover:bg-stone-800 hover:text-white' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <?= e(trans('admin_payment_methods')) ?>
                </a>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-stone-300 hover:bg-stone-800 hover:text-white transition">
                        <span class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16M4 9h16M4 13h10M4 17h6M4 21h2"/></svg>
                            <?php echo e(trans("admin_manage_content")); ?>
                        </span>
                        <svg class="w-4 h-4 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-transition class="mt-1 space-y-1" :class="open ? 'block' : 'hidden'">
                        <a href="<?= e(locale_url('/mf-dashboard/pages')) ?>" class="flex items-center gap-3 pl-11 pr-3 py-2 rounded-xl text-stone-300 hover:bg-stone-800 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16M4 9h16M4 13h10M4 17h6M4 21h2"/></svg>
                            <?= e(trans('admin_pages')) ?>
                        </a>
                        <a href="<?= e(locale_url('/mf-dashboard/homepage')) ?>" class="flex items-center gap-3 pl-11 pr-3 py-2 rounded-xl text-stone-300 hover:bg-stone-800 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5A1.5 1.5 0 014.5 8h15A1.5 1.5 0 0121 9.5v9a1.5 1.5 0 01-1.5 1.5h-15A1.5 1.5 0 013 18.5v-9zM6 5h12M4 12h16"/></svg>
                            <?= e(trans('admin_homepage')) ?>
                        </a>
                        <a href="<?= e(locale_url('/mf-dashboard/footer')) ?>" class="flex items-center gap-3 pl-11 pr-3 py-2 rounded-xl text-stone-300 hover:bg-stone-800 hover:text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16V8zM12 3v18"/></svg>
                            <?= e(trans('admin_footer')) ?>
                        </a>
                    </div>
                </div>
                
                <a href="<?= e(locale_url('/mf-dashboard/settings')) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl <?= str_starts_with($adminPath, 'mf-dashboard/settings') ? 'bg-brand-600 text-white' : 'hover:bg-stone-800 hover:text-white' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?= e(trans('admin_settings')) ?>
                </a>
            </nav>

            <!-- Settings link replaces the old per-session language switcher.
                 Language is now controlled globally from the Settings page. -->
            <div class="px-5 py-3 border-t border-stone-800">
                <a href="<?= e(locale_url('/mf-dashboard/settings')) ?>"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-stone-400 hover:bg-stone-800 hover:text-white transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <?= e(trans('admin_site_language')) ?>:
                    <span class="ml-auto font-bold text-brand-400 font-bengali"><?= app_locale() === 'bn' ? 'বাংলা' : 'English' ?></span>
                </a>
            </div>

            <!-- Bottom Menu: View Website & Admin User -->
            <div class="mt-auto px-5 py-4 border-t border-stone-800 space-y-3">
                <a href="<?= e(locale_url('/')) ?>" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-stone-300 hover:bg-stone-800 hover:text-white transition group" title="<?= e(trans('admin_view_website')) ?>">
                    <svg class="w-4 h-4 text-stone-400 group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <span><?= e(trans('admin_view_website')) ?></span>
                    <span class="ml-auto text-[10px] text-stone-500 group-hover:text-stone-300">↗</span>
                </a>
                <?php $adminName = user_display_name($currentUser); ?>
                <div class="flex items-center gap-3 pt-2 border-t border-stone-800/80">
                    <div class="w-9 h-9 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold shrink-0"><?= e(mb_substr($adminName, 0, 1)) ?></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-white truncate"><?= e($adminName) ?></p>
                        <a href="<?= e(locale_url('/logout')) ?>" class="text-[11px] text-stone-500 hover:text-red-400"><?= e(trans('nav_logout')) ?></a>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 min-w-0">
        <?php include views_path('partials/wsit_flash.php'); ?>
        <div class="p-4 sm:p-6 lg:p-8">
            <?= $content ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
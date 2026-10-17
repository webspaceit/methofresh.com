<?php
// Hero — rotating banner. Slides are configured in Admin → Homepage.
$heroSlides = array_values(array_filter(
    $heroSlides ?? hero_slides(),
    static fn($s) => !empty($s['enabled'])
));
$slideCount = count($heroSlides);
$locale = app_locale();
$heroSpeed = hero_carousel_speed();
$heroHeight = hero_carousel_height();

// Order CTA — WhatsApp (if a number is configured) else the contact page.
$waUrl = whatsapp_order_url();
$ctaMainUrl = locale_url('/products');
$ctaContactUrl = locale_url('/contact');
$heroPhone = site_setting('footer_phone', '01700-000000');

// Story section image — use dedicated story image; fall back to first hero slide.
$storyImageRaw = story_image_path();
if ($storyImageRaw !== '') {
    $storyImage = image_url($storyImageRaw, homepage_section('land_story_image_label'), 900);
} else {
    $storyImage = '';
    foreach ($heroSlides as $slide) {
        $img = trim((string)($slide['image'] ?? ''));
        if ($img !== '') {
            $storyImage = image_url($img, homepage_section('land_story_image_label'), 900);
            break;
        }
    }
}

$renderBlocks = array_values($blocks ?? []);
usort($renderBlocks, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
$hasProductBlocks = false;
foreach ($renderBlocks as $block) {
    if (!empty($block['visible']) && in_array($block['id'], ['categories', 'featured', 'new'], true)) {
        $hasProductBlocks = true;
        break;
    }
}

// Category filter tabs (reference-style pill tabs under the products heading).
$tabCategories = array_values($categories ?? []);
?>
<style>
    /* Slides are stacked in the SAME grid cell so the slider grows to fit the
       tallest slide. They are not absolutely positioned and have no internal
       overflow, so the mouse wheel always scrolls the page — never the hero. */
    .hero-slider { display: grid; }
    .hero-slide { grid-area: 1 / 1; opacity: 0; visibility: hidden; transition: opacity .8s ease, visibility .8s ease; z-index: 0; pointer-events: none; }
    .hero-slide.active { opacity: 1; visibility: visible; z-index: 10; pointer-events: auto; }
    .hero-dot { width: .625rem; height: .625rem; border-radius: 9999px; background: rgba(27,67,50,.22); transition: all .3s ease; }
    .hero-dot.active { background: #D97706; width: 1.5rem; }
    .faq-details { border: 1px solid #e7e5e4; border-radius: 1rem; background: #ffffff; overflow: hidden; }
    .faq-details[open] { box-shadow: 0 4px 14px rgba(0,0,0,.06); }
    @media (prefers-reduced-motion: reduce) { .hero-slide { transition: none; } }
</style>

<!-- ============ TRUST BADGES ============ -->
<section class="bg-amber-50/60 border-b border-amber-200/50 py-3" data-purpose="trust-badges">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-around gap-4 text-xs sm:text-sm font-medium text-stone-700 text-center">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span><?= e(homepage_section('land_trust_1')) ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span><?= e(homepage_section('land_trust_2')) ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span><?= e(homepage_section('land_trust_3')) ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span><?= e(homepage_section('land_trust_4')) ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ============ HERO ============ -->
<section class="relative pt-8 pb-14 sm:py-16 overflow-hidden" data-purpose="hero-section">
    <div class="hero-slider relative" style="min-height:<?= (int)$heroHeight ?>px" data-interval="<?= (int)$heroSpeed ?>">
        <div class="absolute -top-6 -right-6 w-40 h-40 bg-amber-200/40 rounded-full blur-2xl -z-0"></div>
        <div class="absolute -bottom-6 -left-6 w-40 h-40 bg-emerald-200/40 rounded-full blur-2xl -z-0"></div>

        <?php foreach ($heroSlides as $i => $slide): ?>
        <?php
            $image     = trim((string)($slide['image'] ?? ''));
            $emoji     = trim((string)($slide['emoji'] ?? '🍯'));
            $title     = trim((string)($slide['title_' . $locale] ?? '')) ?: trim((string)($slide['title_en'] ?? ''));
            $subtitle  = trim((string)($slide['subtitle_' . $locale] ?? '')) ?: trim((string)($slide['subtitle_en'] ?? ''));
            $btn1Text  = trim((string)($slide['btn1_text_' . $locale] ?? '')) ?: trim((string)($slide['btn1_text_en'] ?? ''));
            $btn1Link  = trim((string)($slide['btn1_link'] ?? '/products'));
            $btn2Text  = trim((string)($slide['btn2_text_' . $locale] ?? '')) ?: trim((string)($slide['btn2_text_en'] ?? ''));
            $btn2Link  = trim((string)($slide['btn2_link'] ?? '/categories'));
        ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?> flex items-center" data-index="<?= $i ?>" role="group" aria-label="Slide <?= $i + 1 ?> of <?= $slideCount ?>">
            <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                    <!-- Hero Text Content -->
                    <div class="lg:col-span-7 space-y-5">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100/70 border border-emerald-300 text-brand-800 text-xs sm:text-sm font-semibold">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" fill-rule="evenodd"/></svg>
                            <span><?= e(site_setting('tagline_' . $locale, trans('tagline'))) ?></span>
                        </div>
                        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-stone-900 tracking-tight leading-tight">
                            <?= e($title) ?>
                            <span class="text-brand-700 block mt-1 sm:mt-2"><?= e(homepage_section('land_hero_highlight')) ?></span>
                        </h1>
                        <?php if ($subtitle !== ''): ?>
                        <p class="text-stone-600 text-base sm:text-lg leading-relaxed max-w-2xl">
                            <?= e($subtitle) ?>
                        </p>
                        <?php endif; ?>
                        <div class="flex flex-wrap items-center gap-4 pt-1">
                            <a href="<?= e(locale_url($btn1Link)) ?>" class="inline-flex items-center justify-center px-8 py-3.5 rounded-lg bg-brand-700 hover:bg-brand-800 text-white font-semibold text-base transition shadow-md hover:shadow-lg gap-2">
                                <?= e($btn1Text) ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                            <?php if ($btn2Text !== ''): ?>
                            <a href="<?= e(locale_url($btn2Link)) ?>" class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg border border-stone-300 bg-white hover:bg-stone-50 text-stone-700 font-semibold text-base transition">
                                <?= e($btn2Text) ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-3 gap-2 sm:gap-4 pt-4 border-t border-stone-200 text-xs sm:text-sm text-stone-600">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"/></svg>
                                <span><?= e(homepage_section('land_hero_1')) ?></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"/></svg>
                                <span><?= e(homepage_section('land_hero_2')) ?></span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" fill-rule="evenodd"/></svg>
                                <span><?= e(homepage_section('land_hero_3')) ?></span>
                            </div>
                        </div>
                    </div>
                    <!-- Hero Image Card with Stamp Badge -->
                    <div class="lg:col-span-5 relative">
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl border-4 border-white <?= $image !== '' ? 'bg-amber-900/10' : 'bg-gradient-to-br from-amber-100 via-white to-emerald-50' ?>">
                            <?php if ($image !== ''): ?>
                            <img src="<?= e(image_url($image)) ?>" alt="<?= e($title) ?>" class="w-full h-[340px] sm:h-[420px] object-cover">
                            <?php else: ?>
                            <div class="w-full h-[340px] sm:h-[420px] flex items-center justify-center select-none">
                                <span class="text-[8rem] sm:text-[10rem] opacity-90"><?= e($emoji) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="absolute bottom-5 left-5 bg-white/95 backdrop-blur-sm px-4 py-3 rounded-xl shadow-lg border border-amber-200 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-700">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs text-stone-500 font-medium"><?= e(homepage_section('land_stamp_label')) ?></div>
                                <div class="text-sm font-bold text-stone-900"><?= e(homepage_section('land_stamp_text')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($slideCount > 1): ?>
        <button type="button" class="hero-prev absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white border border-stone-200 text-stone-500 hover:text-brand-700 hover:border-brand-700 p-2 shadow-sm transition focus:outline-none" aria-label="<?= e(trans('previous')) ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" class="hero-next absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white border border-stone-200 text-stone-500 hover:text-brand-700 hover:border-brand-700 p-2 shadow-sm transition focus:outline-none" aria-label="<?= e(trans('next')) ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2">
            <?php for ($d = 0; $d < $slideCount; $d++): ?>
            <button type="button" class="hero-dot <?= $d === 0 ? 'active' : '' ?>" data-goto="<?= $d ?>" aria-label="Go to slide <?= $d + 1 ?>"></button>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    var root = document.querySelector('.hero-slider');
    if (!root) return;
    var slides = Array.prototype.slice.call(root.querySelectorAll('.hero-slide'));
    if (!slides.length) return;
    var dots = Array.prototype.slice.call(root.querySelectorAll('.hero-dot'));
    var prev = root.querySelector('.hero-prev');
    var next = root.querySelector('.hero-next');
    var idx = 0;

    function show(n) {
        idx = (n + slides.length) % slides.length;
        slides.forEach(function (s, i) {
            s.classList.toggle('active', i === idx);
        });
        dots.forEach(function (d, i) {
            d.classList.toggle('active', i === idx);
        });
    }

    function nextSlide() { show(idx + 1); }
    function prevSlide() { show(idx - 1); }

    var interval = parseInt(root.getAttribute('data-interval') || '5500', 10);
    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var timer = null;

    function start() {
        if (reduced || slides.length < 2) return;
        stop();
        timer = setInterval(nextSlide, interval);
    }
    function stop() { if (timer) clearInterval(timer); timer = null; }

    if (prev) prev.addEventListener('click', function () { stop(); prevSlide(); start(); });
    if (next) next.addEventListener('click', function () { stop(); nextSlide(); start(); });
    dots.forEach(function (d) {
        d.addEventListener('click', function () {
            stop();
            show(parseInt(d.getAttribute('data-goto'), 10) || 0);
            start();
        });
    });
    if (slides.length > 1) {
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
    }

    start();
})();
</script>

<!-- ============ VALUE RIBBON ============ -->
<section class="border-y border-stone-200 bg-white py-6" data-purpose="value-ribbon">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="flex flex-col items-center">
                <span class="text-2xl mb-1"><?= e(homepage_section('land_value_1_icon')) ?></span>
                <span class="text-sm sm:text-base font-semibold text-stone-800"><?= e(homepage_section('land_value_1')) ?></span>
                <span class="text-xs text-stone-500"><?= e(homepage_section('land_value_1_text')) ?></span>
            </div>
            <div class="flex flex-col items-center">
                <span class="text-2xl mb-1"><?= e(homepage_section('land_value_2_icon')) ?></span>
                <span class="text-sm sm:text-base font-semibold text-stone-800"><?= e(homepage_section('land_value_2')) ?></span>
                <span class="text-xs text-stone-500"><?= e(homepage_section('land_value_2_text')) ?></span>
            </div>
            <div class="flex flex-col items-center">
                <span class="text-2xl mb-1"><?= e(homepage_section('land_value_3_icon')) ?></span>
                <span class="text-sm sm:text-base font-semibold text-stone-800"><?= e(homepage_section('land_value_3')) ?></span>
                <span class="text-xs text-stone-500"><?= e(homepage_section('land_value_3_text')) ?></span>
            </div>
            <div class="flex flex-col items-center">
                <span class="text-2xl mb-1"><?= e(homepage_section('land_value_4_icon')) ?></span>
                <span class="text-sm sm:text-base font-semibold text-stone-800"><?= e(homepage_section('land_value_4')) ?></span>
                <span class="text-xs text-stone-500"><?= e(homepage_section('land_value_4_text')) ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ============ PRODUCTS ============ -->
<?php if ($hasProductBlocks): ?>
<section id="products" class="pt-14 sm:pt-16">
    <div class="text-center max-w-2xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-stone-900 tracking-tight"><?= e(homepage_section('land_products')) ?></h2>
        <p class="text-stone-600 mt-2 text-sm sm:text-base"><?= e(homepage_section('land_products_text')) ?></p>
        <div class="w-16 h-1 bg-amber-500 mx-auto mt-4 rounded-full"></div>
    </div>
    <?php if (count($tabCategories) > 0): ?>
    <div class="flex flex-wrap justify-center gap-2 mt-8 px-4 text-sm font-medium">
        <a class="px-5 py-2 rounded-full bg-brand-700 text-white shadow-sm" href="<?= e(locale_url('/products')) ?>"><?= e(homepage_section('land_tab_all')) ?></a>
        <?php foreach ($tabCategories as $tabCat): ?>
        <a class="px-5 py-2 rounded-full bg-white border border-stone-300 text-stone-700 hover:border-brand-700 hover:text-brand-700 transition" href="<?= e(locale_url('/categories/' . $tabCat['slug'])) ?>"><?= e($tabCat['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php foreach ($renderBlocks as $block): ?>
<?php if (empty($block['visible'])) continue; ?>
<?php $title = block_title($block, $block['label_key'] ?? ''); ?>
<?php if ($block['id'] === 'categories'): ?>
<?php if (empty($categories)) continue; ?>
<?php include views_path('partials/home-blocks/wsit_categories.php'); ?>
<?php elseif ($block['id'] === 'featured'): ?>
<?php if (empty($featured)) continue; ?>
<?php include views_path('partials/home-blocks/wsit_featured.php'); ?>
<?php elseif ($block['id'] === 'new'): ?>
<?php if (empty($newArrivals)) continue; ?>
<?php include views_path('partials/home-blocks/wsit_new.php'); ?>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>

<!-- ============ OUR STORY ============ -->
<section id="story" class="py-16 sm:py-20 bg-[#F4EFE6] border-y border-stone-200/80" data-purpose="our-story">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-5 order-2 lg:order-1">
                <div class="rounded-2xl overflow-hidden shadow-lg border-4 border-white">
                    <?php if ($storyImage !== ''): ?>
                    <img src="<?= e($storyImage) ?>" alt="<?= e(homepage_section('land_story_image_label')) ?>" loading="lazy" class="w-full h-80 sm:h-96 object-cover">
                    <?php else: ?>
                    <div class="w-full h-80 sm:h-96 bg-gradient-to-br from-amber-100 via-white to-emerald-50 flex items-center justify-center">
                        <span class="text-[7rem] select-none opacity-40">🍯</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lg:col-span-7 order-1 lg:order-2 space-y-5">
                <span class="text-amber-700 font-semibold text-xs tracking-wider uppercase"><?= e(homepage_section('land_story_badge')) ?></span>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-stone-900 leading-snug"><?= e(homepage_section('land_story_title')) ?></h2>
                <p class="text-stone-600 text-sm sm:text-base leading-relaxed"><?= e(homepage_section('land_story_par')) ?></p>
                <ul class="space-y-3.5 pt-2">
                    <?php foreach (['land_story_1', 'land_story_2', 'land_story_3', 'land_story_4'] as $key): ?>
                    <li class="flex items-start gap-3">
                        <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs mt-0.5 flex-shrink-0">✓</span>
                        <span class="text-sm sm:text-base text-stone-700"><?= e(homepage_section($key)) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ============ WHY US ============ -->
<section class="py-16 sm:py-20" data-purpose="why-choose-us">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-12">
            <h2 class="text-3xl font-bold text-stone-900"><?= e(homepage_section('land_why_title')) ?></h2>
            <div class="w-12 h-1 bg-amber-500 mx-auto mt-3 rounded-full"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $reasons = [
                ['icon' => homepage_section('land_why_1_icon'), 'title' => 'land_why_1', 'text' => 'land_why_1_text'],
                ['icon' => homepage_section('land_why_2_icon'), 'title' => 'land_why_2', 'text' => 'land_why_2_text'],
                ['icon' => homepage_section('land_why_3_icon'), 'title' => 'land_why_3', 'text' => 'land_why_3_text'],
                ['icon' => homepage_section('land_why_4_icon'), 'title' => 'land_why_4', 'text' => 'land_why_4_text'],
            ];
            ?>
            <?php foreach ($reasons as $reason): ?>
            <div class="bg-white p-6 rounded-2xl border border-stone-200 shadow-sm text-center flex flex-col items-center hover:-translate-y-1 transition duration-300 hover:border-amber-400">
                <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mb-4 text-xl"><?= $reason['icon'] ?></div>
                <h3 class="font-bold text-stone-900 text-base mb-2"><?= e(homepage_section($reason['title'])) ?></h3>
                <p class="text-stone-500 text-xs sm:text-sm leading-relaxed"><?= e(homepage_section($reason['text'])) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ HOW TO ORDER ============ -->
<section id="how" class="py-14 sm:py-16 bg-stone-100/70 border-t border-stone-200" data-purpose="how-to-order">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-stone-900"><?= e(homepage_section('land_how_title')) ?></h2>
            <div class="w-12 h-1 bg-amber-500 mx-auto mt-2 rounded-full"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            <?php
            $steps = [
                ['n' => homepage_section('land_how_1_num'), 'title' => 'land_how_1', 'text' => 'land_how_1_text'],
                ['n' => homepage_section('land_how_2_num'), 'title' => 'land_how_2', 'text' => 'land_how_2_text'],
                ['n' => homepage_section('land_how_3_num'), 'title' => 'land_how_3', 'text' => 'land_how_3_text'],
            ];
            ?>
            <?php foreach ($steps as $i => $step): ?>
            <div class="bg-white p-6 rounded-2xl border border-stone-200 relative">
                <span class="w-7 h-7 rounded-full bg-brand-700 text-white font-bold text-sm flex items-center justify-center mb-3"><?= $step['n'] ?></span>
                <h3 class="font-bold text-stone-900 text-base mb-1"><?= e(homepage_section($step['title'])) ?></h3>
                <p class="text-stone-500 text-xs sm:text-sm leading-relaxed"><?= e(homepage_section($step['text'])) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<section class="py-16 sm:py-20" data-purpose="customer-reviews">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-xl mx-auto mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-stone-900"><?= e(homepage_section('land_testimonials')) ?></h2>
            <div class="w-12 h-1 bg-amber-500 mx-auto mt-2 rounded-full"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php for ($t = 1; $t <= 3; $t++): ?>
            <div class="bg-white p-6 rounded-2xl border border-stone-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="text-amber-500 text-sm mb-3"><?= e(homepage_section('land_testimonial_' . $t . '_stars')) ?></div>
                    <p class="text-stone-700 text-sm italic leading-relaxed">“<?= e(homepage_section('land_testimonial_' . $t)) ?>”</p>
                </div>
                <div class="mt-5 pt-4 border-t border-stone-100">
                     <span class="font-bold text-stone-900 text-sm block"><?= e(homepage_section('land_testimonial_' . $t . '_by')) ?></span>
                    <span class="text-stone-400 text-xs"><?= e(homepage_section('land_testimonial_' . $t . '_loc')) ?></span>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ============ FAQ ============ -->
<section id="faq" class="py-14 sm:py-16 bg-[#F8F5EE] border-t border-stone-200" data-purpose="faq-accordion">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-stone-900"><?= e(homepage_section('land_faq_title')) ?></h2>
            <div class="w-12 h-1 bg-amber-500 mx-auto mt-2 rounded-full"></div>
        </div>
        <div class="space-y-3">
            <?php for ($q = 1; $q <= 4; $q++): ?>
            <details class="faq-details group">
                <summary class="flex cursor-pointer items-center justify-between gap-4 px-4 py-4 text-sm sm:text-base font-semibold text-stone-800 list-none [&::-webkit-details-marker]:hidden">
                    <?= e(homepage_section('land_faq_' . $q . '_q')) ?>
                    <span class="text-stone-400 transition-transform duration-200 group-open:rotate-180 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </summary>
                <p class="px-4 pb-4 text-sm text-stone-600 leading-relaxed border-t border-stone-100 pt-3"><?= e(homepage_section('land_faq_' . $q . '_a')) ?></p>
            </details>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ============ FINAL CTA ============ -->
<section class="bg-brand-800 text-white py-14 sm:py-16 relative overflow-hidden" data-purpose="cta-banner">
    <div class="max-w-4xl mx-auto px-4 text-center space-y-6 relative z-10">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight"><?= e(homepage_section('land_cta_title')) ?></h2>
        <p class="text-stone-200 text-sm sm:text-base max-w-xl mx-auto"><?= e(homepage_section('land_cta_text')) ?></p>
        <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
            <?php if ($waUrl !== ''): ?>
            <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-6 py-3.5 rounded-xl transition shadow-lg text-sm sm:text-base">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                <span><?= e(homepage_section('land_cta_wa')) ?></span>
            </a>
            <?php endif; ?>
            <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $heroPhone)) ?>" class="inline-flex items-center gap-2 bg-white hover:bg-stone-100 text-stone-900 font-semibold px-6 py-3.5 rounded-xl transition shadow-lg text-sm sm:text-base">
                <svg class="w-4 h-4 text-brand-700" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                <span><?= e(homepage_section('land_cta_call')) ?> <?= e($heroPhone) ?></span>
            </a>
        </div>
    </div>
</section>

<!-- ============ NEWSLETTER ============ -->
<section class="max-w-7xl mx-auto px-4 py-14">
    <div class="rounded-3xl bg-brand-800 text-white p-8 sm:p-12 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold"><?= e(homepage_section('footer_newsletter')) ?></h2>
        <p class="mt-2 text-stone-200"><?= e(homepage_section('footer_newsletter_text')) ?></p>
        <form method="post" action="<?= e(locale_url('/')) ?>" class="mt-6 mx-auto flex flex-col sm:flex-row gap-3 max-w-md">
            <?= csrf_field() ?>
            <input type="email" name="newsletter_email" required placeholder="<?= e(homepage_section('newsletter_placeholder')) ?>"
                   class="flex-1 rounded-xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-amber-400">
            <button type="submit" class="rounded-xl bg-amber-500 hover:bg-amber-600 text-stone-900 px-6 py-3 text-sm font-bold transition"><?= e(homepage_section('newsletter_btn')) ?></button>
        </form>
    </div>
</section>
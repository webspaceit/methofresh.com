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
?>
<style>
    .hero-slide { opacity: 0; visibility: hidden; transition: opacity .8s ease, visibility .8s ease; z-index: 0; pointer-events: none; }
    .hero-slide.active { opacity: 1; visibility: visible; z-index: 10; pointer-events: auto; }
    .hero-dot { width: .625rem; height: .625rem; border-radius: 9999px; background: rgba(255,255,255,.4); transition: all .3s ease; }
    .hero-dot.active { background: #fff; width: 1.5rem; }
    @media (prefers-reduced-motion: reduce) { .hero-slide { transition: none; } }
</style>
<section class="relative overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-emerald-700 text-white">
    <div class="hero-slider relative" style="min-height:<?= (int)$heroHeight ?>px" data-interval="<?= (int)$heroSpeed ?>">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_20%_30%,white,transparent_40%),radial-gradient(circle_at_80%_70%,white,transparent_35%)]"></div>

        <?php foreach ($heroSlides as $i => $slide): ?>
        <?php
            $image     = trim((string)($slide['image'] ?? ''));
            $emoji     = trim((string)($slide['emoji'] ?? '🥬'));
            $title     = trim((string)($slide['title_' . $locale] ?? '')) ?: trim((string)($slide['title_en'] ?? ''));
            $subtitle  = trim((string)($slide['subtitle_' . $locale] ?? '')) ?: trim((string)($slide['subtitle_en'] ?? ''));
            $btn1Text  = trim((string)($slide['btn1_text_' . $locale] ?? '')) ?: trim((string)($slide['btn1_text_en'] ?? ''));
            $btn1Link  = trim((string)($slide['btn1_link'] ?? '/products'));
            $btn2Text  = trim((string)($slide['btn2_text_' . $locale] ?? '')) ?: trim((string)($slide['btn2_text_en'] ?? ''));
            $btn2Link  = trim((string)($slide['btn2_link'] ?? '/categories'));
        ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?> absolute inset-0" data-index="<?= $i ?>" role="group" aria-label="Slide <?= $i + 1 ?> of <?= $slideCount ?>">
            <?php if ($image !== ''): ?>
            <img src="<?= e(image_url($image)) ?>" alt="" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-800/95 via-brand-700/75 to-brand-600/40"></div>
            <?php endif; ?>
            <?php if ($image === ''): ?>
            <div class="absolute inset-y-0 right-[-5%] w-[45%] opacity-20">
                <div class="absolute inset-0 flex items-center justify-center text-[16rem] select-none"><?= e($emoji) ?></div>
            </div>
            <?php endif; ?>
            <div class="relative z-10 flex h-full max-w-7xl mx-auto flex-col justify-center px-4 py-16 sm:py-24">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur" style="width:fit-content">
                    <span class="h-2 w-2 rounded-full bg-lime-300 animate-pulse"></span>
                    <?= e(site_setting('tagline_' . $locale, trans('tagline'))) ?>
                </span>
                <h1 class="mt-5 max-w-2xl text-3xl sm:text-5xl font-extrabold leading-tight tracking-tight">
                    <?= e($title) ?>
                </h1>
                <?php if ($subtitle !== ''): ?>
                <p class="mt-4 max-w-xl text-sm sm:text-base text-emerald-50/90">
                    <?= e($subtitle) ?>
                </p>
                <?php endif; ?>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="<?= e(locale_url($btn1Link)) ?>" class="inline-flex items-center gap-2 rounded-xl bg-white text-brand-700 font-bold px-6 py-3 text-sm shadow-lg hover:bg-emerald-50 transition">
                        <?= e($btn1Text) ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
                    </a>
                    <?php if ($btn2Text !== ''): ?>
                    <a href="<?= e(locale_url($btn2Link)) ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/40 px-6 py-3 text-sm font-bold hover:bg-white/10 transition">
                        <?= e($btn2Text) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($slideCount > 1): ?>
        <button type="button" class="hero-prev absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white/15 hover:bg-white/25 p-2 transition focus:outline-none focus:ring-2 focus:ring-white" aria-label="<?= e(trans('previous')) ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" class="hero-next absolute right-3 sm:right-4 top-1/2 -translate-y-1/2 z-20 rounded-full bg-white/15 hover:bg-white/25 p-2 transition focus:outline-none focus:ring-2 focus:ring-white" aria-label="<?= e(trans('next')) ?>">
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

<?php
// Feature cards
$features = [
    ['icon' => '🌿', 'title' => trans('best_quality'), 'text' => trans('best_quality_text')],
    ['icon' => '🛵', 'title' => trans('fast_delivery'), 'text' => trans('fast_delivery_text')],
    ['icon' => '💸', 'title' => trans('best_price'), 'text' => trans('best_price_text')],
    ['icon' => '🔒', 'title' => trans('secure_payment'), 'text' => trans('secure_payment_text')],
];
?>
<section class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ($features as $feature): ?>
    <div class="flex items-start gap-3 rounded-2xl bg-white border border-stone-200 p-4 shadow-sm">
        <span class="text-3xl"><?= $feature['icon'] ?></span>
        <div>
            <p class="font-bold text-stone-800 text-sm"><?= e($feature['title']) ?></p>
            <p class="text-xs text-stone-500 mt-0.5"><?= e($feature['text']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<?php
// Homepage blocks — order, visibility and titles are configured in Admin → Homepage.
$renderBlocks = array_values($blocks ?? []);
usort($renderBlocks, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
?>
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

<?php
// Newsletter CTA strip
?>
<section class="max-w-7xl mx-auto px-4 py-10">
    <div class="rounded-3xl bg-brand-600 text-white p-8 sm:p-12 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold"><?= e(trans('footer_newsletter')) ?></h2>
        <p class="mt-2 text-emerald-50"><?= e(trans('footer_newsletter_text')) ?></p>
        <form method="post" action="<?= e(locale_url('/')) ?>" class="mt-6 mx-auto flex flex-col sm:flex-row gap-3 max-w-md">
            <?= csrf_field() ?>
            <input type="email" name="newsletter_email" required placeholder="<?= e(trans('newsletter_placeholder')) ?>"
                   class="flex-1 rounded-xl border-0 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-white">
            <button type="submit" class="rounded-xl bg-stone-900 hover:bg-stone-800 text-white px-6 py-3 text-sm font-bold transition"><?= e(trans('newsletter_btn')) ?></button>
        </form>
    </div>
</section>
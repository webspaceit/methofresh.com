<?php $page = $page ?? null; $contactForm = $contactForm ?? false; $captchaEnabled = $captchaEnabled ?? true; ?>
<?php if ($contactForm): ?>
    <!-- The "contact" CMS page uses the same standardized layout as /contact -->
    <section class="max-w-7xl mx-auto px-4 py-8">
        <nav class="text-xs text-stone-400 mb-2 flex items-center gap-1.5 flex-wrap">
            <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700 transition"><?= e(trans('nav_home')) ?></a>
            <span>/</span>
            <span class="text-stone-700 font-semibold"><?= e($page['title']) ?></span>
        </nav>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-800 tracking-tight"><?= e($page['title']) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('contact_subtitle')) ?></p>

        <?php if (!empty($page['content'])): ?>
        <div class="prose prose-stone max-w-none text-stone-700 leading-relaxed text-[15px] space-y-4 mt-6">
            <!-- <?= $page['content'] /* admin-authored HTML, kept unescaped */ ?> -->
        </div>
        <?php endif; ?>

        <div class="mt-6 lg:grid lg:grid-cols-[1fr,380px] gap-8 items-start">
            <!-- Contact form -->
            <div class="bg-card rounded-2xl border border-honey-600/15 shadow-sm p-6 sm:p-8">
                <?php $contactRedirect = '/pages/' . ($page['slug'] ?? 'contact'); ?>
                <?php include views_path('contact/wsit_form.php'); ?>
            </div>

            <!-- Contact info (real data from Settings → Footer) -->
            <?php include views_path('contact/wsit_info_card.php'); ?>
        </div>
    </section>
<?php else: ?>

<div class="bg-cream min-h-screen">

    <div class="relative bg-brand-700 bg-gradient-to-br from-brand-700 to-brand-900 text-white">
        <?php if (!empty($page['image'])): ?>
            <img src="<?= e(image_url($page['image'])) ?>" alt="<?= e($page['title']) ?>"
                 class="absolute inset-0 w-full h-full object-cover opacity-25">
        <?php endif; ?>
        <div class="max-w-7xl mx-auto px-4 py-12 sm:py-16 relative">
            <p class="text-xs font-semibold text-brand-100 mb-2 flex items-center gap-1.5">
                <a href="<?= e(locale_url('/')) ?>" class="hover:text-white transition"><?= e(trans('nav_home')) ?></a>
                <span>/</span>
                <span><?= e($page['title']) ?></span>
            </p>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight"><?= e($page['title']) ?></h1>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8 sm:py-12">
        <div class="bg-card rounded-3xl border border-honey-600/15 shadow-sm p-6 sm:p-10">
            <?php if (!empty($page['content'])): ?>
                <div class="prose prose-stone max-w-none text-stone-700 leading-relaxed text-[15px] space-y-4">
                    <?= $page['content'] /* admin-authored HTML, kept unescaped */ ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-stone-500"><?= e(trans('page_empty')) ?></p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php endif; ?>
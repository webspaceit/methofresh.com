<?php $page = $page ?? null; $contactForm = $contactForm ?? false; $captchaEnabled = $captchaEnabled ?? true; ?>
<div class="bg-stone-50 min-h-screen">

    <div class="relative bg-brand-600 bg-gradient-to-br from-brand-600 to-brand-800 text-white">
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
        <div class="bg-white rounded-3xl border border-stone-200/90 shadow-sm p-6 sm:p-10">
            <?php if (!empty($page['content'])): ?>
                <div class="prose prose-stone max-w-none text-stone-700 leading-relaxed text-[15px] space-y-4">
                    <?= $page['content'] /* admin-authored HTML, kept unescaped */ ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-stone-500"><?= e(trans('page_empty')) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($contactForm)): ?>
            <div class="bg-white rounded-3xl border border-stone-200/90 shadow-sm p-6 sm:p-10 mt-8">
                <h2 class="text-xl font-bold text-stone-900 mb-6"><?= e(trans('contact_send')) ?></h2>
                <?php $contactRedirect = '/pages/' . ($page['slug'] ?? 'contact'); ?>
                <?php include views_path('contact/wsit_form.php'); ?>
            </div>
        <?php endif; ?>
    </div>

</div>
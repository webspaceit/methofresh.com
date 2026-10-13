<?php
$captcha = $captcha ?? null;
$captchaEnabled = $captchaEnabled ?? true;
?>
<section class="max-w-7xl mx-auto px-4 py-8">
    <!-- Breadcrumb + page title, matching Products / Categories / Cart -->
    <nav class="text-xs text-stone-400 mb-2 flex items-center gap-1.5 flex-wrap">
        <a href="<?= e(locale_url('/')) ?>" class="hover:text-brand-700 transition"><?= e(trans('nav_home')) ?></a>
        <span>/</span>
        <span class="text-stone-700 font-semibold"><?= e(trans('contact')) ?></span>
    </nav>
    <h1 class="text-2xl sm:text-3xl font-extrabold text-brand-800 tracking-tight"><?= e(trans('contact')) ?></h1>
    <p class="text-sm text-stone-500 mt-1"><?= e(trans('contact_subtitle')) ?></p>

    <div class="mt-6 lg:grid lg:grid-cols-[1fr,380px] gap-8 items-start">
        <!-- Contact form -->
        <div class="bg-card rounded-2xl border border-honey-600/15 shadow-sm p-6 sm:p-8">
            <?php include views_path('contact/wsit_form.php'); ?>
        </div>

        <!-- Contact info (real data from Settings → Footer) -->
        <?php include views_path('contact/wsit_info_card.php'); ?>
    </div>
</section>
<?php
// SEO Settings — Material admin (locale-aware via trans()).
$ogImage     = site_setting('seo_og_image', '');
$orgName     = site_setting('seo_org_name', app_name());
$twitter     = site_setting('seo_twitter_handle', '');
$defaultDesc = site_setting('seo_default_description', '');
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-primary-fixed/40 flex items-center justify-center text-primary shrink-0">
            <svg viewBox="0 0 24 24" fill="currentColor" class="text-[26px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm2 4h12v2H6V8Zm0 4h8v2H6v-2Z"/></svg>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_seo')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_seo_sub')) ?></p>
        </div>
    </div>
</section>

<form method="post" action="<?= e(locale_url('/mf-dashboard/seo')) ?>" class="flex flex-col gap-space-lg">
    <?= csrf_field() ?>

    <!-- Default OG Image -->
    <section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
        <h2 class="font-label-lg text-label-lg text-on-surface font-bold"><?= e(trans('admin_seo_og_image')) ?></h2>
        <div>
            <label class="font-label-md text-label-md text-on-surface-variant"><?= e(trans('admin_seo_og_image_label')) ?></label>
            <input type="url" name="seo_og_image" value="<?= e($ogImage) ?>" placeholder="https://methofresh.com/assets/img/og-default.jpg"
                   class="mt-1.5 w-full rounded-xl border border-surface-container px-space-md py-2.5 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1"><?= e(trans('admin_seo_og_image_hint')) ?></p>
        </div>
        <?php if ($ogImage !== ''): ?>
        <img src="<?= e($ogImage) ?>" alt="OG preview" class="rounded-xl border border-surface-container max-w-xs">
        <?php endif; ?>
    </section>

    <!-- Organization -->
    <section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
        <h2 class="font-label-lg text-label-lg text-on-surface font-bold"><?= e(trans('admin_seo_org')) ?></h2>
        <div>
            <label class="font-label-md text-label-md text-on-surface-variant"><?= e(trans('admin_seo_org_name_label')) ?></label>
            <input type="text" name="seo_org_name" value="<?= e($orgName) ?>" placeholder="MethoFresh"
                   class="mt-1.5 w-full rounded-xl border border-surface-container px-space-md py-2.5 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
        </div>
    </section>

    <!-- Twitter -->
    <section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
        <h2 class="font-label-lg text-label-lg text-on-surface font-bold"><?= e(trans('admin_seo_twitter')) ?></h2>
        <div>
            <label class="font-label-md text-label-md text-on-surface-variant"><?= e(trans('admin_seo_twitter_label')) ?></label>
            <input type="text" name="seo_twitter_handle" value="<?= e($twitter) ?>" placeholder="@methofresh"
                   class="mt-1.5 w-full rounded-xl border border-surface-container px-space-md py-2.5 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
        </div>
    </section>

    <!-- Default description -->
    <section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
        <h2 class="font-label-lg text-label-lg text-on-surface font-bold"><?= e(trans('admin_seo_default_desc')) ?></h2>
        <div>
            <label class="font-label-md text-label-md text-on-surface-variant"><?= e(trans('admin_seo_default_desc_label')) ?></label>
            <textarea name="seo_default_description" rows="3" maxlength="160"
                      class="mt-1.5 w-full rounded-xl border border-surface-container px-space-md py-2.5 text-sm focus:ring-2 focus:ring-primary focus:outline-none"><?= e($defaultDesc) ?></textarea>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1"><?= e(trans('admin_seo_default_desc_hint')) ?></p>
        </div>
    </section>

    <!-- Info box -->
    <section class="p-space-lg rounded-2xl bg-surface-container-low shadow-sm flex flex-col gap-space-sm">
        <h2 class="font-label-lg text-label-lg text-on-surface font-bold"><?= e(trans('admin_seo_auto_title')) ?></h2>
        <ul class="font-body-md text-body-md text-on-surface-variant space-y-1.5 pl-space-md list-disc">
            <li><?= e(trans('admin_seo_auto_canonical')) ?></li>
            <li><?= e(trans('admin_seo_auto_hreflang')) ?></li>
            <li><?= e(trans('admin_seo_auto_product_schema')) ?></li>
            <li><?= e(trans('admin_seo_auto_titles')) ?></li>
        </ul>
    </section>

    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-primary text-on-primary px-space-xl py-3 text-sm font-bold hover:bg-primary-container transition-colors"><?= e(trans('admin_save')) ?></button>
    </div>
</form>
</div>

<?php
// $globalLocale is the current site-wide default locale from the settings table.
$globalLocale = $globalLocale ?? 'en';
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_settings')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_site_language_desc')) ?></p>
    </div>
</div>

<div class="max-w-xl space-y-6">

    <!-- ── Site-wide default language ── -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Globe icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_site_language')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_site_language_current')) ?>:
                    <span class="font-semibold text-brand-700 <?= $globalLocale === 'bn' ? 'font-bengali' : '' ?>"><?= $globalLocale === 'bn' ? 'বাংলা' : 'English' ?></span>
                </p>
            </div>
        </div>

        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings')) ?>" class="p-5">
            <?= csrf_field() ?>
            <div class="space-y-3">
                <!-- English option -->
                <label class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                              <?= $globalLocale === 'en' ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                    <input type="radio" name="locale" value="en"
                           <?= $globalLocale === 'en' ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand-600 focus:ring-brand-500">
                    <div class="flex-1">
                        <p class="font-bold text-stone-900">English</p>
                        <p class="text-xs text-stone-500 mt-0.5">New visitors will see the site in English by default</p>
                    </div>
                    <?php if ($globalLocale === 'en'): ?>
                    <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white px-2 py-0.5 rounded-full">Active</span>
                    <?php endif; ?>
                </label>

                <!-- Bengali option -->
                <label class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                              <?= $globalLocale === 'bn' ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                    <input type="radio" name="locale" value="bn"
                           <?= $globalLocale === 'bn' ? 'checked' : '' ?>
                           class="w-4 h-4 text-brand-600 focus:ring-brand-500">
                    <div class="flex-1">
                        <p class="font-bold text-stone-900 font-bengali" style="font-family: 'Noto Sans Bengali', sans-serif;">বাংলা <span class="font-normal text-stone-500 text-sm font-sans">(Bengali)</span></p>
                        <p class="text-xs text-stone-500 mt-0.5 font-bengali" style="font-family: 'Noto Sans Bengali', sans-serif;">নতুন দর্শকরা ডিফল্টভাবে সাইটটি বাংলায় দেখবেন</p>
                    </div>
                    <?php if ($globalLocale === 'bn'): ?>
                    <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white px-2 py-0.5 rounded-full font-bengali">সক্রিয়</span>
                    <?php endif; ?>
                </label>
            </div>

            <div class="mt-6 flex items-center gap-4">
                <button type="submit"
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                    <?= e(trans('admin_save')) ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ── Site logo ── -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Image icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-5-5L5 21"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_logo')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_logo_desc')) ?></p>
            </div>
        </div>

        <div class="p-5">
            <div class="flex items-center gap-4 mb-5">
                <p class="text-xs font-semibold text-stone-500 shrink-0"><?= e(trans('admin_logo_current')) ?></p>
                <?php $logo = site_logo_url(); ?>
                <?php if ($logo !== ''): ?>
                    <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>" class="h-10 w-auto max-w-[180px] object-contain bg-stone-50 border border-stone-200 rounded-lg p-1">
                <?php else: ?>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white text-lg font-black"><?= e(mb_substr(trans('site_name'), 0, 1)) ?></span>
                    <span class="text-xs text-stone-400"><?= e(trans('admin_logo_default')) ?></span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/logo')) ?>" enctype="multipart/form-data" class="space-y-3">
                <?= csrf_field() ?>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml"
                       class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-xl file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
                <div class="flex items-center gap-4">
                    <button type="submit"
                            class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                        <?= e(trans('admin_logo_save')) ?>
                    </button>
                    <?php if ($logo !== ''): ?>
                    <button type="submit" name="remove_logo" value="1"
                            class="rounded-xl border border-red-200 text-red-600 hover:bg-red-50 px-6 py-2.5 text-sm font-bold transition">
                        <?= e(trans('admin_logo_remove')) ?>
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Site favicon ── -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Favicon icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="4"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12l2.5 2.5L16 9"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_favicon')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_favicon_desc')) ?></p>
            </div>
        </div>

        <div class="p-5">
            <div class="flex items-center gap-4 mb-5">
                <p class="text-xs font-semibold text-stone-500 shrink-0"><?= e(trans('admin_favicon_current')) ?></p>
                <?php $favicon = site_favicon_url(); ?>
                <?php if ($favicon !== ''): ?>
                    <img src="<?= e($favicon) ?>" alt="favicon" class="h-10 w-10 object-contain bg-stone-50 border border-stone-200 rounded-lg p-1">
                <?php else: ?>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-stone-50 border border-stone-200 text-lg">🥦</span>
                    <span class="text-xs text-stone-400"><?= e(trans('admin_favicon_default')) ?></span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/favicon')) ?>" enctype="multipart/form-data" class="space-y-3">
                <?= csrf_field() ?>
                <input type="file" name="favicon" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,image/x-icon"
                       class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-xl file:border-0 file:bg-stone-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-stone-700 hover:file:bg-stone-200">
                <div class="flex items-center gap-4">
                    <button type="submit"
                            class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                        <?= e(trans('admin_favicon_save')) ?>
                    </button>
                    <?php if ($favicon !== ''): ?>
                    <button type="submit" name="remove_favicon" value="1"
                            class="rounded-xl border border-red-200 text-red-600 hover:bg-red-50 px-6 py-2.5 text-sm font-bold transition">
                        <?= e(trans('admin_favicon_remove')) ?>
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Contact form captcha ── -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Lock icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="11" width="18" height="10" rx="2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 11V8a5 5 0 0110 0v3"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_contact_captcha')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_contact_captcha_desc')) ?></p>
            </div>
        </div>

        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/captcha')) ?>" class="p-5">
            <?= csrf_field() ?>
            <?php $captchaOn = contact_captcha_enabled(); ?>
            <label class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition <?= $captchaOn ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                <input type="checkbox" name="contact_captcha" value="1" <?= $captchaOn ? 'checked' : '' ?> class="w-5 h-5 rounded text-brand-600 focus:ring-brand-500">
                <div class="flex-1">
                    <p class="font-bold text-stone-900"><?= e(trans('admin_contact_captcha_enable')) ?></p>
                    <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_contact_captcha_hint')) ?></p>
                </div>
                <?php if ($captchaOn): ?>
                <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white px-2 py-0.5 rounded-full">Active</span>
                <?php endif; ?>
            </label>
            <div class="mt-6">
                <button type="submit"
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                    <?= e(trans('admin_save')) ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ── Contact notification email ── -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Envelope icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9 6 9-6"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_contact_email')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_contact_email_desc')) ?></p>
            </div>
        </div>

        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/contact-email')) ?>" class="p-5 space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('admin_contact_email_label')) ?></label>
                <input type="email" name="contact_email" required value="<?= e($contactEmail ?? 'support@methofresh.com') ?>"
                       class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                       placeholder="support@methofresh.com">
                <p class="text-xs text-stone-400 mt-1.5"><?= e(trans('admin_contact_email_hint')) ?></p>
            </div>
            <div class="pt-2">
                <button type="submit"
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                    <?= e(trans('admin_save')) ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ── Info note ── -->
    <div class="flex gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <svg class="mt-0.5 w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M12 12v4"/>
        </svg>
        <p><?= e(trans('admin_site_language_note')) ?></p>
    </div>

</div>

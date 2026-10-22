<?php
// $globalLocale is the current site-wide default locale from the settings table.
$globalLocale = $globalLocale ?? 'en';
$isUrlMismatch = $globalLocale !== app_locale();
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_settings')) ?></h1>
        <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_site_language_desc')) ?></p>
    </div>
    <?php if ($isUrlMismatch): ?>
    <a href="<?= e(locale_url_for($globalLocale, '/mf-dashboard/settings')) ?>"
       class="inline-flex items-center gap-2 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 text-xs font-semibold px-4 py-2 hover:bg-amber-200 transition">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Site default is <strong class="ml-1"><?= $globalLocale === 'bn' ? 'বাংলা' : 'English' ?></strong> — switch to that URL →
    </a>
    <?php endif; ?>
</div>

<div class="max-w-xl space-y-6">

    <!-- ── Site-wide default language ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
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

        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings')) ?>" class="p-5" id="lang-form">
            <?= csrf_field() ?>
            <div class="space-y-3">
                <!-- English option -->
                <label id="label-en" class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                              <?= $globalLocale === 'en' ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                    <input type="radio" name="locale" value="en"
                           <?= $globalLocale === 'en' ? 'checked' : '' ?>
                           onchange="submitLangForm(this)"
                           class="w-4 h-4 text-brand-600 focus:ring-brand-500">
                    <div class="flex-1">
                        <p class="font-bold text-stone-900">English</p>
                        <p class="text-xs text-stone-500 mt-0.5">New visitors will see the site in English by default</p>
                    </div>
                    <span id="badge-en" class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white px-2 py-0.5 rounded-full <?= $globalLocale === 'en' ? '' : 'hidden' ?>">Active</span>
                </label>

                <!-- Bengali option -->
                <label id="label-bn" class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                              <?= $globalLocale === 'bn' ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                    <input type="radio" name="locale" value="bn"
                           <?= $globalLocale === 'bn' ? 'checked' : '' ?>
                           onchange="submitLangForm(this)"
                           class="w-4 h-4 text-brand-600 focus:ring-brand-500">
                    <div class="flex-1">
                        <p class="font-bold text-stone-900 font-bengali" style="font-family: 'Noto Sans Bengali', sans-serif;">বাংলা <span class="font-normal text-stone-500 text-sm font-sans">(Bengali)</span></p>
                        <p class="text-xs text-stone-500 mt-0.5 font-bengali" style="font-family: 'Noto Sans Bengali', sans-serif;">নতুন দর্শকরা ডিফল্টভাবে সাইটটি বাংলায় দেখবেন</p>
                    </div>
                    <span id="badge-bn" class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white px-2 py-0.5 rounded-full font-bengali <?= $globalLocale === 'bn' ? '' : 'hidden' ?>">সক্রিয়</span>
                </label>
            </div>
        </form>
        <script>
        function submitLangForm(radio) {
            // Update label highlight immediately for instant visual feedback
            ['en','bn'].forEach(function(loc) {
                var lbl = document.getElementById('label-' + loc);
                var bdg = document.getElementById('badge-' + loc);
                if (!lbl) return;
                if (loc === radio.value) {
                    lbl.classList.add('border-brand-500', 'bg-brand-50');
                    lbl.classList.remove('border-stone-200');
                    if (bdg) bdg.classList.remove('hidden');
                } else {
                    lbl.classList.remove('border-brand-500', 'bg-brand-50');
                    lbl.classList.add('border-stone-200');
                    if (bdg) bdg.classList.add('hidden');
                }
            });
            // Submit the form
            document.getElementById('lang-form').submit();
        }
        </script>
    </div>

    <!-- ── Site logo ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
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
            <?php
                $logo   = site_logo_url();
                $logoDim = site_logo_dimensions();
            ?>
            <!-- Current image preview using saved dimensions -->
            <div class="flex items-center gap-3 mb-4">
                <?php if ($logo !== ''): ?>
                    <img src="<?= e($logo) ?>" alt="<?= e(trans('site_name')) ?>"
                         style="<?= e(site_logo_style()) ?>"
                         class="object-contain bg-stone-50 border border-stone-200 rounded-xl p-1 max-w-[240px]">
                    <span class="text-xs text-stone-500"><?= e(trans('admin_logo_current')) ?></span>
                <?php else: ?>
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-brand-600 text-white text-xl font-black"><?= e(mb_substr(trans('site_name'), 0, 1)) ?></span>
                    <span class="text-xs text-stone-400"><?= e(trans('admin_logo_default')) ?></span>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/logo')) ?>" enctype="multipart/form-data" id="form-logo">
                <?= csrf_field() ?>
                <div class="space-y-4">
                    <!-- Image file -->
                    <div>
                        <label class="block text-xs font-semibold text-stone-600 mb-1"><?= e(trans('admin_logo_save')) ?></label>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml"
                               onchange="document.getElementById('form-logo').submit()"
                               class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <p class="text-xs text-stone-400 mt-1">PNG, JPG, WebP or SVG — max 2 MB. Uploads automatically on file select.</p>
                    </div>

                    <!-- Dimensions -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1">Height (px)</label>
                            <input type="number" name="logo_height" min="10" max="200" value="<?= (int)$logoDim['height'] ?>"
                                   class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <p class="text-xs text-stone-400 mt-1">10–200 px. Default: 40</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-600 mb-1">Width (px) <span class="font-normal text-stone-400">— 0 = auto</span></label>
                            <input type="number" name="logo_width" min="0" max="600" value="<?= (int)$logoDim['width'] ?>"
                                   class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <p class="text-xs text-stone-400 mt-1">0–600 px. 0 = auto</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-bold transition">
                            <?= e(trans('admin_save')) ?>
                        </button>
                        <?php if ($logo !== ''): ?>
                        <button type="submit" name="remove_logo" value="1"
                                class="rounded-xl border border-red-200 text-red-600 hover:bg-red-50 px-5 py-2.5 text-sm font-bold transition">
                            <?= e(trans('admin_logo_remove')) ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Site favicon ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
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
            <?php $favicon = site_favicon_url(); ?>
            <!-- Current image preview -->
            <div class="flex items-center gap-3 mb-4">
                <?php if ($favicon !== ''): ?>
                    <img src="<?= e($favicon) ?>" alt="favicon"
                         class="h-14 w-14 object-contain bg-stone-50 border border-stone-200 rounded-xl p-1">
                    <span class="text-xs text-stone-500"><?= e(trans('admin_favicon_current')) ?></span>
                <?php else: ?>
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-stone-50 border border-stone-200 text-2xl">🥦</span>
                    <span class="text-xs text-stone-400"><?= e(trans('admin_favicon_default')) ?></span>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/favicon')) ?>" enctype="multipart/form-data" id="form-favicon">
                <?= csrf_field() ?>
                <div class="space-y-3">
                    <input type="file" name="favicon" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,image/x-icon"
                           onchange="document.getElementById('form-favicon').submit()"
                           class="block w-full text-sm text-stone-600 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <p class="text-xs text-stone-400">PNG, JPG, WebP, SVG or ICO — max 1 MB. Uploads automatically on file select.</p>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 text-sm font-bold transition">
                            <?= e(trans('admin_favicon_save')) ?>
                        </button>
                        <?php if ($favicon !== ''): ?>
                        <button type="submit" name="remove_favicon" value="1"
                                class="rounded-xl border border-red-200 text-red-600 hover:bg-red-50 px-5 py-2.5 text-sm font-bold transition">
                            <?= e(trans('admin_favicon_remove')) ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Customer accounts at checkout ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Person-add icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 8v6M22 11h-6"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_checkout_accounts')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_checkout_accounts_desc')) ?></p>
            </div>
        </div>

        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/account-settings')) ?>" class="p-5 space-y-4">
            <?= csrf_field() ?>
            <?php $autoAccount = $autoAccount ?? true; $requireLogin = $requireLogin ?? false; ?>

            <label class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                          <?= $autoAccount ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                <input type="checkbox" name="auto_create_account_on_order" value="1"
                       <?= $autoAccount ? 'checked' : '' ?>
                       class="w-5 h-5 rounded text-brand-600 focus:ring-brand-500 mt-0.5">
                <div class="flex-1">
                    <p class="font-bold text-stone-900 text-sm"><?= e(trans('admin_auto_create_account')) ?></p>
                    <p class="text-xs text-stone-500 mt-0.5 leading-relaxed"><?= e(trans('admin_auto_create_account_hint')) ?></p>
                </div>
            </label>

            <label class="flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                          <?= $requireLogin ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                <input type="checkbox" name="require_login_for_existing_account" value="1"
                       <?= $requireLogin ? 'checked' : '' ?>
                       class="w-5 h-5 rounded text-brand-600 focus:ring-brand-500 mt-0.5">
                <div class="flex-1">
                    <p class="font-bold text-stone-900 text-sm"><?= e(trans('admin_require_login_existing')) ?></p>
                    <p class="text-xs text-stone-500 mt-0.5 leading-relaxed"><?= e(trans('admin_require_login_existing_hint')) ?></p>
                </div>
            </label>

            <div class="pt-1">
                <button type="submit"
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                    <?= e(trans('admin_save')) ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ── Contact form captcha ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
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
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
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

    <!-- ── Announcement bar ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Megaphone icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_announcement')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_announcement_desc')) ?></p>
            </div>
        </div>

        <?php
        // Stored rows prefilled; language defaults apply until the first save
        // (see announcement_offers()).
        $annRows = announcement_offers();
        if ($annRows === []) {
            // Everything was cleared — show one blank block to type into.
            $annRows = [['offer_en' => '', 'offer_bn' => '', 'coupon_en' => '', 'coupon_bn' => '', 'code' => '']];
        }

        // One renderer for saved rows (real keys) and the JS "add" template.
        $annRowHtml = static function (int $key, array $row): string {
            ob_start();
            ?>
            <div class="rounded-xl border border-stone-300 bg-stone-50/50 overflow-hidden" data-ann-row>
                <div class="flex items-center justify-between border-b border-brand-100 bg-brand-50 px-4 py-2.5">
                    <span class="text-xs font-bold text-brand-700 uppercase tracking-wide">
                        <?= e(trans('admin_announcement_offer')) ?> <span class="ann-n"><?= $key + 1 ?></span>
                    </span>
                    <button type="button"
                            class="ann-remove inline-flex items-center gap-1 text-xs font-semibold text-red-500 hover:text-red-600 focus:outline-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        <?= e(trans('admin_announcement_remove')) ?>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_announcement_badge')) ?></label>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-semibold text-stone-400"><?= e(trans('admin_lang_en')) ?></label>
                                <input type="text" name="offers[<?= $key ?>][offer_en]" value="<?= e((string)$row['offer_en']) ?>"
                                       class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-semibold text-stone-400"><?= e(trans('admin_lang_bn')) ?></label>
                                    <?php $source = '[name="offers[' . $key . '][offer_en]"]'; $target = '[name="offers[' . $key . '][offer_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                                </div>
                                <input type="text" name="offers[<?= $key ?>][offer_bn]" value="<?= e((string)$row['offer_bn']) ?>"
                                       class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_announcement_message')) ?></label>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-semibold text-stone-400"><?= e(trans('admin_lang_en')) ?></label>
                                <input type="text" name="offers[<?= $key ?>][coupon_en]" value="<?= e((string)$row['coupon_en']) ?>"
                                       class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-[11px] font-semibold text-stone-400"><?= e(trans('admin_lang_bn')) ?></label>
                                    <?php $source = '[name="offers[' . $key . '][coupon_en]"]'; $target = '[name="offers[' . $key . '][coupon_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                                </div>
                                <input type="text" name="offers[<?= $key ?>][coupon_bn]" value="<?= e((string)$row['coupon_bn']) ?>"
                                       class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="sm:max-w-[16rem]">
                        <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_announcement_code')) ?></label>
                        <input type="text" name="offers[<?= $key ?>][code]" value="<?= e((string)$row['code']) ?>"
                               class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm font-semibold uppercase tracking-wider focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <p class="text-xs text-stone-400 mt-1.5"><?= e(trans('admin_announcement_code_hint')) ?></p>
                    </div>
                </div>
            </div>
            <?php
            return (string)ob_get_clean();
        };

        // Blank row for the "Add offer" button; keys must stay collision-free.
        $annTemplate = str_replace(
            'offers[' . count($annRows) . ']',
            'offers[@@KEY@@]',
            $annRowHtml(count($annRows), ['offer_en' => '', 'offer_bn' => '', 'coupon_en' => '', 'coupon_bn' => '', 'code' => ''])
        );
        ?>
        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/announcement')) ?>" class="p-5 space-y-5">
            <?= csrf_field() ?>

            <div id="ann-offers" class="space-y-6">
                <?php foreach ($annRows as $annKey => $annRow): ?>
                <?= $annRowHtml((int)$annKey, $annRow) ?>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button type="submit"
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                    <?= e(trans('admin_save')) ?>
                </button>
                <button type="button" id="ann-add"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 px-4 py-2.5 text-sm font-bold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <?= e(trans('admin_announcement_add')) ?>
                </button>
            </div>
        </form>
        <script>
        (function () {
            const container = document.getElementById('ann-offers');
            const addBtn = document.getElementById('ann-add');
            const tpl = <?= json_encode($annTemplate) ?>;
            let key = container.querySelectorAll('[data-ann-row]').length;

            function renumber() {
                container.querySelectorAll('[data-ann-row]').forEach(function (row, i) {
                    const n = row.querySelector('.ann-n');
                    if (n) n.textContent = String(i + 1);
                });
            }

            function bindRemove() {
                container.querySelectorAll('.ann-remove').forEach(function (btn) {
                    btn.onclick = function () {
                        // Keys stay monotonic after removals — never reused.
                        btn.closest('[data-ann-row]').remove();
                        renumber();
                    };
                });
            }

            addBtn.addEventListener('click', function () {
                container.insertAdjacentHTML('beforeend', tpl.replaceAll('@@KEY@@', String(key)));
                key += 1;
                bindRemove();
                renumber();
            });

            bindRemove();
        })();
        </script>
    </div>

    <!-- ── Checkout cities ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Location pin icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_cities')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_cities_desc')) ?></p>
            </div>
        </div>

        <?php
        // Saved rows prefilled (default: the built-in 64 districts).
        $cityRows = checkout_cities();
        if ($cityRows === []) {
            // Everything was cleared — show one blank block to type into.
            $cityRows = [['name_en' => '', 'name_bn' => '']];
        }

        // One renderer for saved rows (real keys) and the JS "add" template.
        $cityRowHtml = static function (int $key, array $row): string {
            ob_start();
            $inputCls = 'w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none';
            ?>
            <div class="rounded-xl border border-stone-300 bg-stone-50/50 overflow-hidden" data-city-row>
                <div class="flex items-center justify-between border-b border-brand-100 bg-brand-50 px-4 py-2.5">
                    <span class="text-xs font-bold text-brand-700 uppercase tracking-wide">
                        <?= e(trans('admin_cities_city')) ?> <span class="city-n"><?= $key + 1 ?></span>
                    </span>
                    <button type="button" data-city-remove
                            class="text-xs font-semibold text-red-600 hover:text-red-700 focus:outline-none">
                        <?= e(trans('admin_shipping_remove')) ?>
                    </button>
                </div>
                <div class="grid sm:grid-cols-2 gap-3 p-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_cities_name')) ?> (EN)</label>
                        <input type="text" name="cities[<?= $key ?>][name_en]" value="<?= e((string)($row['name_en'] ?? '')) ?>"
                               placeholder="e.g. Dhaka" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_cities_name')) ?> (BN)</label>
                            <?php $source = '[name="cities[' . $key . '][name_en]"]'; $target = '[name="cities[' . $key . '][name_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                        </div>
                        <input type="text" name="cities[<?= $key ?>][name_bn]" value="<?= e((string)($row['name_bn'] ?? '')) ?>"
                               placeholder="e.g. ঢাকা" class="<?= $inputCls ?>">
                    </div>
                </div>
            </div>
            <?php
            return (string)ob_get_clean();
        };

        // Template row: -1 placeholder swapped for @@KEY@@ across the WHOLE
        // row HTML, so name attributes and translate onclick selectors stay
        // in sync (monotonic key — never rewritten after insert).
        $cityTemplate = str_replace('cities[-1]', 'cities[@@KEY@@]', $cityRowHtml(-1, [
            'name_en' => '', 'name_bn' => '',
        ]));
        ?>
        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/cities')) ?>" class="p-5 space-y-5">
            <?= csrf_field() ?>

            <p class="text-xs text-stone-400"><?= e(trans('admin_cities_hint')) ?></p>

            <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1" id="checkout-cities">
                <?php foreach ($cityRows as $cityKey => $cityRow): ?>
                <?= $cityRowHtml((int)$cityKey, $cityRow) ?>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-wrap gap-3 pt-1">
                <button type="submit"
                        class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 text-sm font-bold transition">
                    <?= e(trans('admin_save')) ?>
                </button>
                <button type="button" id="add-city"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 px-4 py-2.5 text-sm font-bold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <?= e(trans('admin_cities_add')) ?>
                </button>
            </div>
        </form>
        <script>
        (function () {
            const container = document.getElementById('checkout-cities');
            if (!container) return;

            const template = <?= json_encode($cityTemplate, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

            // Free index for the next added row: one past the largest key in
            // the DOM. Monotonic and never reused — a row's fields (and its
            // translate selectors) never change once inserted.
            let key = 0;
            container.querySelectorAll('[name^="cities["]').forEach((el) => {
                const m = el.name.match(/^cities\[(\d+)\]/);
                if (m) key = Math.max(key, parseInt(m[1], 10) + 1);
            });

            function renumber() {
                // Display numbers only — field keys stay untouched.
                container.querySelectorAll('[data-city-row]').forEach((row, i) => {
                    const n = row.querySelector('.city-n');
                    if (n) n.textContent = i + 1;
                });
            }

            document.getElementById('add-city').addEventListener('click', () => {
                const wrap = document.createElement('div');
                wrap.innerHTML = template.replaceAll('@@KEY@@', String(key));
                key += 1;
                const node = wrap.firstElementChild;
                container.appendChild(node);
                renumber();
                const firstInput = node.querySelector('input[type="text"]');
                if (firstInput) firstInput.focus();
            });

            container.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-city-remove]');
                if (!btn) return;
                btn.closest('[data-city-row]').remove();
                renumber();
            });
        })();
        </script>
    </div>

    <!-- ── Top navigation menu ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Menu icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_nav_menu')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_nav_menu_desc')) ?></p>
            </div>
        </div>

        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/nav-menu')) ?>" class="p-5">
            <?= csrf_field() ?>
            <div class="space-y-4" id="nav-menu">
                <?php $navItems = nav_menu_items(); ?>
                <?php foreach ($navItems as $navKey => $nav): $navNum = (int)$navKey + 1; ?>
                <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
                        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_nav_link')) ?> <?= $navNum ?></span>
                        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                            <input type="checkbox" name="nav_menu[<?= $navKey ?>][enabled]" value="1"
                                   class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= !empty($nav['enabled']) ? 'checked' : '' ?>>
                            <?= e(trans('admin_nav_enabled')) ?>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                            <input type="checkbox" name="nav_menu[<?= $navKey ?>][target]" value="1"
                                   class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= !empty($nav['target']) ? 'checked' : '' ?>>
                            <?= e(trans('admin_nav_new_tab')) ?>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                            <?= e(trans('admin_nav_order')) ?>
                            <input type="number" name="nav_menu[<?= $navKey ?>][order]" value="<?= (int)$nav['order'] ?>" min="0"
                                   class="w-16 rounded-lg border border-stone-200 px-2 py-1 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
                            <input type="checkbox" name="nav_menu_remove[<?= $navKey ?>]" value="1"
                                   class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
                            <?= e(trans('admin_nav_remove')) ?>
                        </label>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_nav_label')) ?> (EN)</label>
                            <input type="text" name="nav_menu[<?= $navKey ?>][label_en]" value="<?= e($nav['label_en'] ?? '') ?>"
                                   placeholder="e.g. About" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_nav_label')) ?> (BN)</label>
                                <?php $source = '[name="nav_menu[' . $navKey . '][label_en]"]'; $target = '[name="nav_menu[' . $navKey . '][label_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                            </div>
                            <input type="text" name="nav_menu[<?= $navKey ?>][label_bn]" value="<?= e($nav['label_bn'] ?? '') ?>"
                                   placeholder="e.g. আমাদের সম্পর্কে" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_nav_url')) ?></label>
                            <input type="text" name="nav_menu[<?= $navKey ?>][url]" value="<?= e($nav['url'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_nav_url_hint')) ?>" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_nav_url_help')) ?></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_nav_icon')) ?></label>
                            <input type="text" name="nav_menu[<?= $navKey ?>][icon]" value="<?= e($nav['icon'] ?? '') ?>"
                                   list="nav-icon-list" placeholder="e.g. contact_support" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <p class="text-xs text-stone-400 mt-1"><?= e(trans('admin_nav_icon_hint')) ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <datalist id="nav-icon-list">
                <?php foreach (NAV_ICON_NAMES as $iconName): ?>
                <option value="<?= e($iconName) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <button type="button" id="add-nav-menu"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-500 hover:border-brand-500 hover:text-brand-600 transition">
                <span class="text-lg leading-none">+</span> <?= e(trans('admin_nav_add')) ?>
            </button>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
            </div>
        </form>
    </div>

    <!-- ── Facebook Pixel ── -->
    <div class="bg-card rounded-2xl border border-honey-600/15 overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-3">
            <!-- Pixel icon -->
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V5m6 14V5M5 7h14M5 7l-1 12h16l-1-12"/>
                </svg>
            </span>
            <div>
                <h2 class="font-bold text-stone-900"><?= e(trans('admin_facebook_pixel')) ?></h2>
                <p class="text-xs text-stone-500 mt-0.5"><?= e(trans('admin_facebook_pixel_desc')) ?></p>
            </div>
        </div>

        <?php
        $pixelCfg = facebook_pixel_config();
        $pixelId = $pixelCfg['pixel_id'] ?? '';
        $pixelEnabled = !empty($pixelCfg['enabled']);
        $pixelTestMode = !empty($pixelCfg['test_mode']);
        $pixelEvents = $pixelCfg['events'] ?? [];
        ?>
        <form method="post" action="<?= e(locale_url('/mf-dashboard/settings/facebook-pixel')) ?>" class="p-5 space-y-5">
            <?= csrf_field() ?>

            <!-- Pixel ID -->
            <div>
                <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('admin_facebook_pixel_id')) ?></label>
                <input type="text" name="facebook_pixel_id" value="<?= e($pixelId) ?>"
                       placeholder="<?= e(trans('admin_facebook_pixel_id_hint')) ?>"
                       class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none font-mono">
            </div>

            <!-- Master switch + test mode -->
            <div class="grid sm:grid-cols-2 gap-4">
                <label class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                              <?= $pixelEnabled ? 'border-brand-500 bg-brand-50' : 'border-stone-200 hover:border-stone-300' ?>">
                    <input type="checkbox" name="facebook_pixel_enabled" value="1"
                           <?= $pixelEnabled ? 'checked' : '' ?>
                           class="w-5 h-5 rounded text-brand-600 focus:ring-brand-500">
                    <div class="flex-1">
                        <p class="font-bold text-stone-900 text-sm"><?= e(trans('admin_facebook_pixel_enabled')) ?></p>
                        <p class="text-xs text-stone-500 mt-0.5">Loads the fbq library + PageView on every page</p>
                    </div>
                    <?php if ($pixelEnabled): ?>
                    <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-brand-600 text-white px-2 py-0.5 rounded-full">Active</span>
                    <?php endif; ?>
                </label>

                <label class="flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition
                              <?= $pixelTestMode ? 'border-amber-400 bg-amber-50' : 'border-stone-200 hover:border-stone-300' ?>">
                    <input type="checkbox" name="facebook_pixel_test_mode" value="1"
                           <?= $pixelTestMode ? 'checked' : '' ?>
                           class="w-5 h-5 rounded text-amber-500 focus:ring-amber-500">
                    <div class="flex-1">
                        <p class="font-bold text-stone-900 text-sm"><?= e(trans('admin_facebook_pixel_test_mode')) ?></p>
                        <p class="text-xs text-stone-500 mt-0.5">Events logged, not sent to Facebook</p>
                    </div>
                </label>
            </div>

            <!-- Standard events -->
            <div>
                <p class="text-xs font-bold text-stone-500 uppercase tracking-wide mb-2"><?= e(trans('admin_facebook_pixel_events')) ?></p>
                <div class="space-y-2">
                    <?php
                    $eventDefs = [
                        'view_content'      => 'admin_facebook_pixel_view_content',
                        'add_to_cart'       => 'admin_facebook_pixel_add_to_cart',
                        'initiate_checkout' => 'admin_facebook_pixel_checkout',
                        'purchase'          => 'admin_facebook_pixel_purchase',
                    ];
                    foreach ($eventDefs as $eventKey => $labelKey):
                        $on = !empty($pixelEvents[$eventKey]);
                    ?>
                    <label class="flex items-center justify-between gap-3 p-3 rounded-xl border border-stone-200 hover:bg-stone-50 cursor-pointer transition">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="facebook_pixel_event[<?= e($eventKey) ?>]" value="1"
                                   <?= $on ? 'checked' : '' ?>
                                   class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm font-medium text-stone-700"><?= e(trans($labelKey)) ?></span>
                        </div>
                        <span class="text-[10px] font-mono font-semibold text-stone-400 uppercase tracking-wide"><?= e($eventKey) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
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

<?php ob_start(); ?>
<div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_nav_link')) ?> {{KEYNUM}}</span>
        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
            <input type="checkbox" name="nav_menu[{{KEY}}][enabled]" value="1" checked class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            <?= e(trans('admin_nav_enabled')) ?>
        </label>
        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
            <input type="checkbox" name="nav_menu[{{KEY}}][target]" value="1" class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            <?= e(trans('admin_nav_new_tab')) ?>
        </label>
        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
            <?= e(trans('admin_nav_order')) ?>
            <input type="number" name="nav_menu[{{KEY}}][order]" value="0" min="0"
                   class="w-16 rounded-lg border border-stone-200 px-2 py-1 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </label>
        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
            <input type="checkbox" name="nav_menu_remove[{{KEY}}]" value="1" class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
            <?= e(trans('admin_nav_remove')) ?>
        </label>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_nav_label')) ?> (EN)</label>
            <input type="text" name="nav_menu[{{KEY}}][label_en]" value="" placeholder="e.g. About" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_nav_label')) ?> (BN)</label>
                <?php $source = '[name="nav_menu[{{KEY}}][label_en]"]'; $target = '[name="nav_menu[{{KEY}}][label_bn]"]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
            </div>
            <input type="text" name="nav_menu[{{KEY}}][label_bn]" value="" placeholder="e.g. আমাদের সম্পর্কে" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_nav_url')) ?></label>
            <input type="text" name="nav_menu[{{KEY}}][url]" value="" placeholder="<?= e(trans('admin_nav_url_hint')) ?>" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_nav_icon')) ?></label>
            <input type="text" name="nav_menu[{{KEY}}][icon]" value="" list="nav-icon-list" placeholder="e.g. contact_support" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
    </div>
</div>
<?php $navMenuTemplate = ob_get_clean(); ?>

<script>
(function () {
    const container = document.getElementById('nav-menu');
    const addBtn = document.getElementById('add-nav-menu');
    const tpl = <?= json_encode($navMenuTemplate) ?>;

    if (!container || !addBtn) {
        return;
    }

    function addRow() {
        const key = container.children.length;
        const html = tpl.replaceAll('{{KEY}}', String(key)).replaceAll('{{KEYNUM}}', String(key + 1));
        container.insertAdjacentHTML('beforeend', html);
    }

    addBtn.addEventListener('click', addRow);
})();
</script>

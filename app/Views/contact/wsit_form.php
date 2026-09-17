<?php $captchaEnabled = $captchaEnabled ?? true; $contactRedirect = $contactRedirect ?? '/contact'; ?>
<form method="post" action="<?= e(locale_url('/contact/submit')) ?>" class="space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="_redirect" value="<?= e($contactRedirect) ?>">

    <div>
        <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('full_name')) ?></label>
        <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>"
               class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
               placeholder="<?= e(trans('field_full_name')) ?>">
    </div>

    <div>
        <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('email')) ?></label>
        <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"
               class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
               placeholder="you@example.com">
    </div>

    <div>
        <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('phone')) ?></label>
        <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>"
               class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
               placeholder="+8801711-111111">
    </div>

    <div>
        <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('subject')) ?></label>
        <input type="text" name="subject" required value="<?= e($_POST['subject'] ?? '') ?>"
               class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
               placeholder="<?= e(trans('contact_subject_placeholder')) ?>">
    </div>

    <div>
        <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('message')) ?></label>
        <textarea name="message" rows="5" required
                  class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none resize-y"
                  placeholder="<?= e(trans('contact_message_placeholder')) ?>"><?= e($_POST['message'] ?? '') ?></textarea>
    </div>

    <?php if ($captchaEnabled): ?>
    <div>
        <label class="block text-sm font-semibold text-stone-700 mb-1.5"><?= e(trans('captcha')) ?></label>
        <div class="flex flex-col sm:flex-row gap-3 items-start">
            <div class="flex items-center gap-3 bg-stone-100 rounded-xl px-4 py-3 border border-stone-200">
                <img src="<?= e(locale_url('/contact/captcha')) ?>" alt="Captcha" id="captcha-img"
                     class="h-12 cursor-pointer">
                <button type="button" id="captcha-refresh" class="text-stone-400 hover:text-stone-600" title="<?= e(trans('captcha_refresh')) ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>
            <input type="text" name="captcha" required class="flex-1 rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="<?= e(trans('captcha_placeholder')) ?>">
        </div>
    </div>

    <script>
        var captchaUrl = '<?= locale_url('/contact/captcha') ?>' + '?' + (new Date().getTime());
        document.getElementById('captcha-img').addEventListener('click', function() {
            this.src = captchaUrl = '<?= locale_url('/contact/captcha') ?>' + '?' + (new Date().getTime());
        });
        document.getElementById('captcha-refresh').addEventListener('click', function() {
            document.getElementById('captcha-img').src = captchaUrl = '<?= locale_url('/contact/captcha') ?>' + '?' + (new Date().getTime());
        });
    </script>
    <?php endif; ?>

    <button type="submit" class="w-full rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-4 py-3 text-sm font-bold transition">
        <?= e(trans('contact_send')) ?>
    </button>
</form>

<?php $captcha = $captcha ?? null; $captchaEnabled = $captchaEnabled ?? true; ?>
<div class="bg-stone-50 min-h-screen">

    <div class="max-w-7xl mx-auto px-4 py-8 sm:py-12">
        <div class="max-w-2xl mx-auto">

            <div class="text-center mb-8">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-stone-900"><?= e(trans('contact')) ?></h1>
                <p class="mt-3 text-stone-500"><?= e(trans('contact_subtitle')) ?></p>
            </div>

            <div class="bg-white rounded-2xl border border-stone-200 shadow-sm p-6 sm:p-8">
                <?php include views_path('contact/wsit_form.php'); ?>
            </div>

            <div class="mt-8 bg-white rounded-2xl border border-stone-200 shadow-sm p-6 sm:p-8">
                <h2 class="text-lg font-bold text-stone-800 mb-3"><?= e(trans('contact_info')) ?></h2>
                <div class="space-y-2 text-sm text-stone-600">
                    <p>✉️ <a href="mailto:support@methofresh.com" class="text-brand-600 hover:underline">support@methofresh.com</a></p>
                    <p>📞 +880 1711-111111</p>
                    <p>🏢 House 12, Road 5, Dhanmondi, Dhaka — Bangladesh</p>
                </div>
            </div>

        </div>
    </div>

</div>

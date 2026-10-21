<?php
// WhatsApp + Messenger automation settings (Material admin).
$values   = (array)($values ?? []);
$stats    = (array)($stats ?? []);
$conv     = (array)($conversationStats ?? []);
$waReady  = (bool)($waReady ?? false);
$fbReady  = (bool)($fbReady ?? false);
$waWebhookUrl = (string)($waWebhookUrl ?? '');
$fbWebhookUrl = (string)($fbWebhookUrl ?? '');
$enabled  = (string)($values['enabled'] ?? '0') === '1';

$v = static fn(string $key, string $default = ''): string => (string)($values[$key] ?? $default);

$L = static function (string $en, string $bn): string {
    return app_locale() === 'bn' ? $bn : $en;
};

$input = static function (string $name, string $label, string $value, string $type = 'text', string $hint = '', string $placeholder = '') use ($L): void {
    ?>
    <label class="flex flex-col gap-1">
        <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($label) ?></span>
        <input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"
               placeholder="<?= e($placeholder) ?>" autocomplete="off" spellcheck="false"
               class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface placeholder-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/40">
        <?php if ($hint !== ''): ?>
        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($hint) ?></span>
        <?php endif; ?>
    </label>
    <?php
};
?>
<div class="flex flex-col gap-space-lg">

<!-- Header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
            <span class="material-symbols-outlined text-[26px]">smart_toy</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_messaging')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_messaging_sub')) ?></p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl <?= $enabled ? 'bg-primary-fixed/40 text-primary' : 'bg-surface-container text-on-surface-variant' ?> font-label-md text-label-md font-bold">
            <span class="material-symbols-outlined text-[18px]"><?= $enabled ? 'toggle_on' : 'toggle_off' ?></span>
            <?= e($enabled ? $L('Automation ON', 'অটোমেশন চালু') : $L('Automation OFF', 'অটোমেশন বন্ধ')) ?>
        </span>
        <a href="<?= e(locale_url('/mf-dashboard/message-logs')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-surface-container-low text-on-surface font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[18px]">forum</span><?= e(trans('admin_message_logs')) ?>
        </a>
        <a href="<?= e(locale_url('/mf-dashboard/conversations')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-surface-container-low text-on-surface font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[18px]">support_agent</span><?= e(trans('admin_conversations')) ?>
        </a>
    </div>
</section>

<!-- Today's stats -->
<section class="grid grid-cols-2 lg:grid-cols-4 gap-space-md">
    <?php
    $cards = [
        ['send', $L('Sent today', 'আজ পাঠানো'), (int)($stats['outbound'] ?? 0), 'text-primary', 'bg-primary-fixed/30'],
        ['inbox', $L('Received today', 'আজ প্রাপ্ত'), (int)($stats['inbound'] ?? 0), 'text-secondary', 'bg-secondary-fixed/40'],
        ['mark_chat_read', $L('Delivered / read', 'ডেলিভারড / পড়া'), (int)($stats['delivered'] ?? 0), 'text-on-surface', 'bg-surface-container'],
        ['error', $L('Failed', 'ব্যর্থ'), (int)($stats['failed'] ?? 0), 'text-error', 'bg-error-container/50'],
    ];
    foreach ($cards as [$icon, $label, $count, $color, $bg]): ?>
    <div class="flex items-center gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <span class="w-11 h-11 rounded-xl <?= $bg ?> flex items-center justify-center <?= $color ?> shrink-0">
            <span class="material-symbols-outlined text-[22px]"><?= e($icon) ?></span>
        </span>
        <div class="min-w-0">
            <p class="font-headline-sm text-headline-sm text-on-surface font-bold leading-tight"><?= e(format_number($count)) ?></p>
            <p class="font-body-sm text-body-sm text-on-surface-variant truncate"><?= e($label) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<?php if (!$enabled): ?>
<div class="flex items-start gap-3 p-space-md rounded-xl bg-surface-container-low border border-surface-container-high">
    <span class="material-symbols-outlined text-[22px] text-secondary shrink-0">info</span>
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        <?= e($L(
            'Automation is off. Credentials can be saved now; no message will be sent until you enable it.',
            'অটোমেশন বন্ধ আছে। এখন ক্রেডেনশিয়াল সেভ করতে পারেন; চালু করার আগে কোনো মেসেজ পাঠানো হবে না।'
        )) ?>
    </p>
</div>
<?php endif; ?>

<form method="post" action="<?= e(locale_url('/mf-dashboard/messaging/save')) ?>" class="flex flex-col gap-space-lg">
    <?= csrf_field() ?>

    <!-- Master switch -->
    <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center gap-space-md">
            <span class="w-11 h-11 rounded-xl bg-primary-fixed/30 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined text-[22px]">power_settings_new</span>
            </span>
            <div>
                <p class="font-label-md text-label-md text-on-surface font-bold"><?= e($L('Enable messaging automation', 'মেসেজিং অটোমেশন চালু করুন')) ?></p>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e($L('Order confirmations, status updates, reminders and chatbot replies.', 'অর্ডার কনফার্মেশন, স্ট্যাটাস আপডেট, রিমাইন্ডার ও চ্যাটবট রিপ্লাই।')) ?></p>
            </div>
        </div>
        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?> class="w-5 h-5 rounded accent-primary">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($L('Enabled', 'চালু')) ?></span>
        </label>
    </section>

    <!-- WhatsApp -->
    <section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[22px] text-primary">chat</span>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">WhatsApp Cloud API</h2>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full <?= $waReady ? 'bg-primary-fixed/40 text-primary' : 'bg-surface-container text-on-surface-variant' ?> font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[14px]"><?= $waReady ? 'check_circle' : 'pending' ?></span>
                <?= e($waReady ? $L('Configured', 'কনফিগার করা আছে') : $L('Not configured', 'কনফিগার করা নেই')) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <?php
            $input('wa_phone_number_id', $L('Phone number ID', 'ফোন নম্বর আইডি'), $v('wa_phone_number_id'), 'text', $L('Meta App → WhatsApp → API Setup', 'মেটা অ্যাপ → WhatsApp → API Setup'));
            $input('wa_business_account_id', $L('WhatsApp Business Account ID', 'হোয়াটসঅ্যাপ বিজনেস অ্যাকাউন্ট আইডি'), $v('wa_business_account_id'));
            $input('wa_access_token', $L('Permanent access token', 'পার্মানেন্ট অ্যাক্সেস টোকেন'), $v('wa_access_token'), 'password', $L('System user token — never shared publicly.', 'সিস্টেম ইউজার টোকেন — কারো সাথে শেয়ার করবেন না।'));
            $input('wa_app_secret', $L('App secret (webhook signature)', 'অ্যাপ সিক্রেট (ওয়েবহুক সিগনেচার)'), $v('wa_app_secret'), 'password');
            $input('wa_verify_token', $L('Webhook verify token', 'ওয়েবহুক ভেরিফাই টোকেন'), $v('wa_verify_token'), 'text', $L('Any random string you also paste in Meta.', 'যেকোনো র‍্যান্ডম স্ট্রিং, মেটাতেও একই দিতে হবে।'));
            ?>
            <label class="flex flex-col gap-1">
                <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($L('Template language', 'টেমপ্লেটের ভাষা')) ?></span>
                <select name="wa_template_lang" class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <?php foreach (['bn' => 'Bengali (bn)', 'en' => 'English (en)', 'en_US' => 'English US (en_US)'] as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= $v('wa_template_lang', 'bn') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php $input('api_version', $L('Graph API version', 'গ্রাফ API ভার্সন'), $v('api_version', 'v21.0'), 'text', $L('Example: v21.0', 'উদাহরণ: v21.0')); ?>
        </div>

        <div class="pt-space-sm border-t border-surface-container">
            <p class="font-label-md text-label-md text-on-surface font-bold mb-space-sm"><?= e($L('Approved template names', 'অনুমোদিত টেমপ্লেটের নাম')) ?></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
                <?php
                $input('wa_tpl_order_confirmation', $L('Order confirmation', 'অর্ডার কনফার্মেশন'), $v('wa_tpl_order_confirmation', 'order_confirmation'));
                $input('wa_tpl_status_update', $L('Status update', 'স্ট্যাটাস আপডেট'), $v('wa_tpl_status_update', 'order_status_update'));
                $input('wa_tpl_delivery_reminder', $L('Delivery reminder', 'ডেলিভারি রিমাইন্ডার'), $v('wa_tpl_delivery_reminder', 'delivery_reminder'));
                $input('wa_tpl_order_delivered', $L('Order delivered', 'অর্ডার ডেলিভারড'), $v('wa_tpl_order_delivered', 'order_delivered'));
                ?>
            </div>
        </div>

        <div class="rounded-xl bg-surface-container-low p-space-md">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1"><?= e($L('Callback URL', 'কলব্যাক URL')) ?></p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="<?= e($waWebhookUrl) ?>" class="flex-1 rounded-lg border border-surface-container-high bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface">
                <button type="button" data-copy="<?= e($waWebhookUrl) ?>" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-surface-container-high text-on-surface font-label-sm text-label-sm font-semibold hover:bg-surface-container-highest transition-colors">
                    <span class="material-symbols-outlined text-[16px]">content_copy</span><?= e(trans('admin_copy')) ?>
                </button>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1"><?= e($L('Verify token must match the value above.', 'ভেরিফাই টোকেন উপরের মানের সাথে মিলতে হবে।')) ?></p>
        </div>
    </section>

    <!-- Messenger -->
    <section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[22px] text-secondary">forum</span>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold">Facebook Messenger</h2>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full <?= $fbReady ? 'bg-primary-fixed/40 text-primary' : 'bg-surface-container text-on-surface-variant' ?> font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[14px]"><?= $fbReady ? 'check_circle' : 'pending' ?></span>
                <?= e($fbReady ? $L('Configured', 'কনফিগার করা আছে') : $L('Not configured', 'কনফিগার করা নেই')) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
            <?php
            $input('fb_page_access_token', $L('Page access token', 'পেজ অ্যাক্সেস টোকেন'), $v('fb_page_access_token'), 'password');
            $input('fb_app_secret', $L('App secret (webhook signature)', 'অ্যাপ সিক্রেট (ওয়েবহুক সিগনেচার)'), $v('fb_app_secret'), 'password');
            $input('fb_verify_token', $L('Webhook verify token', 'ওয়েবহুক ভেরিফাই টোকেন'), $v('fb_verify_token'));
            $input('admin_alert_phone', $L('Owner alert phone', 'মালিকের অ্যালার্ট নম্বর'), $v('admin_alert_phone'), 'text', $L('Handover requests are forwarded here on WhatsApp.', 'হ্যান্ডঅভার অনুরোধ এই WhatsApp নম্বরে পাঠানো হবে।'), '01XXXXXXXXX');
            ?>
        </div>

        <div class="rounded-xl bg-surface-container-low p-space-md">
            <p class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider mb-1"><?= e($L('Callback URL', 'কলব্যাক URL')) ?></p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="<?= e($fbWebhookUrl) ?>" class="flex-1 rounded-lg border border-surface-container-high bg-surface px-3 py-2 font-body-sm text-body-sm text-on-surface">
                <button type="button" data-copy="<?= e($fbWebhookUrl) ?>" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-surface-container-high text-on-surface font-label-sm text-label-sm font-semibold hover:bg-surface-container-highest transition-colors">
                    <span class="material-symbols-outlined text-[16px]">content_copy</span><?= e(trans('admin_copy')) ?>
                </button>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant mt-1"><?= e($L('Subscribe to messages, messaging_postbacks and message_deliveries.', 'messages, messaging_postbacks ও message_deliveries ইভেন্টে সাবস্ক্রাইব করুন।')) ?></p>
        </div>
    </section>

    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-bold hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-[18px]">save</span><?= e(trans('admin_save')) ?>
        </button>
    </div>
</form>

<!-- Test send -->
<section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
    <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-[22px] text-on-surface-variant">send</span>
        <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e($L('Send a test message', 'টেস্ট মেসেজ পাঠান')) ?></h2>
    </div>
    <form method="post" action="<?= e(locale_url('/mf-dashboard/messaging/test')) ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        <?= csrf_field() ?>
        <label class="flex flex-col gap-1 sm:col-span-3">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($L('Channel', 'চ্যানেল')) ?></span>
            <select name="channel" class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
                <option value="whatsapp">WhatsApp</option>
                <option value="messenger">Messenger</option>
            </select>
        </label>
        <label class="flex flex-col gap-1 sm:col-span-6">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e($L('Recipient (phone or PSID)', 'প্রাপক (ফোন বা PSID)')) ?></span>
            <input type="text" name="recipient" placeholder="01XXXXXXXXX" class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface placeholder-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/40">
        </label>
        <button type="submit" class="sm:col-span-3 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-surface-container-high text-on-surface font-label-md text-label-md font-bold hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[18px]">send</span><?= e($L('Send test', 'টেস্ট পাঠান')) ?>
        </button>
    </form>
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        <?= e($L('Every send is recorded in the message log. Save credentials first.', 'প্রতিটি পাঠানো মেসেজ লগে সংরক্ষিত হয়। আগে ক্রেডেনশিয়াল সেভ করুন।')) ?>
    </p>
</section>

<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var value = btn.getAttribute('data-copy') || '';
        if (navigator.clipboard) {
            navigator.clipboard.writeText(value).then(function () {
                var icon = btn.querySelector('.material-symbols-outlined');
                if (icon) { icon.textContent = 'check'; }
            });
        }
    });
});
</script>
</div>

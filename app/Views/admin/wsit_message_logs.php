<?php
// Message log — every outbound API call and inbound webhook event.
$logs    = (array)($logs ?? []);
$total   = (int)($total ?? 0);
$page    = max(1, (int)($page ?? 1));
$perPage = max(1, (int)($perPage ?? 30));
$pages   = max(1, (int)ceil($total / $perPage));
$filters = (array)($filters ?? []);
$stats   = (array)($stats ?? []);
$baseUrl = locale_url('/mf-dashboard/message-logs');

$L = static function (string $en, string $bn): string {
    return app_locale() === 'bn' ? $bn : $en;
};

$query = array_filter([
    'channel'   => (string)($filters['channel'] ?? ''),
    'direction' => (string)($filters['direction'] ?? ''),
    'status'    => (string)($filters['status'] ?? ''),
    'q'         => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '');

$pageUrl = static function (int $n) use ($baseUrl, $query): string {
    $qs = $query;
    if ($n > 1) {
        $qs['page'] = $n;
    }
    return $baseUrl . ($qs ? '?' . http_build_query($qs) : '');
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'sent', 'delivered', 'read' => 'bg-primary-fixed/40 text-primary',
        'failed'                    => 'bg-error-container/60 text-on-error-container',
        'received'                  => 'bg-secondary-fixed/50 text-secondary',
        default                     => 'bg-surface-container text-on-surface-variant',
    };
};
?>
<div class="flex flex-col gap-space-lg">

<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
            <span class="material-symbols-outlined text-[26px]">forum</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_message_logs')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e($L('Every WhatsApp and Messenger message, inbound and outbound.', 'প্রতিটি WhatsApp ও Messenger মেসেজ — ইনবাউন্ড ও আউটবাউন্ড।')) ?></p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold">
            <?= e(format_number($total)) ?> <?= e($L('entries', 'এন্ট্রি')) ?>
        </span>
        <a href="<?= e(locale_url('/mf-dashboard/conversations')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-surface-container-low text-on-surface font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[18px]">support_agent</span><?= e(trans('admin_conversations')) ?>
        </a>
        <a href="<?= e(locale_url('/mf-dashboard/messaging')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-bold hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-[18px]">settings</span><?= e(trans('admin_messaging')) ?>
        </a>
    </div>
</section>

<section class="grid grid-cols-2 lg:grid-cols-4 gap-space-md">
    <?php
    $cards = [
        ['send', $L('Sent today', 'আজ পাঠানো'), (int)($stats['outbound'] ?? 0)],
        ['inbox', $L('Received today', 'আজ প্রাপ্ত'), (int)($stats['inbound'] ?? 0)],
        ['mark_chat_read', $L('Delivered / read', 'ডেলিভারড / পড়া'), (int)($stats['delivered'] ?? 0)],
        ['error', $L('Failed', 'ব্যর্থ'), (int)($stats['failed'] ?? 0)],
    ];
    foreach ($cards as [$icon, $label, $count]): ?>
    <div class="flex items-center gap-space-md p-space-md rounded-2xl bg-surface-container-lowest shadow-sm">
        <span class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-on-surface-variant shrink-0">
            <span class="material-symbols-outlined text-[20px]"><?= e($icon) ?></span>
        </span>
        <div class="min-w-0">
            <p class="font-headline-sm text-headline-sm text-on-surface font-bold leading-tight"><?= e(format_number($count)) ?></p>
            <p class="font-body-sm text-body-sm text-on-surface-variant truncate"><?= e($label) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
    <form method="get" action="<?= e($baseUrl) ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        <label class="flex flex-col gap-1 sm:col-span-4">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($L('Search', 'খুঁজুন')) ?></span>
            <input type="search" name="q" value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="<?= e($L('Recipient, template or order number', 'প্রাপক, টেমপ্লেট বা অর্ডার নম্বর')) ?>"
                   class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface placeholder-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/40">
        </label>
        <label class="flex flex-col gap-1 sm:col-span-2">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($L('Channel', 'চ্যানেল')) ?></span>
            <select name="channel" class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
                <option value=""><?= e($L('All', 'সব')) ?></option>
                <option value="whatsapp" <?= ($filters['channel'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                <option value="messenger" <?= ($filters['channel'] ?? '') === 'messenger' ? 'selected' : '' ?>>Messenger</option>
            </select>
        </label>
        <label class="flex flex-col gap-1 sm:col-span-2">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($L('Direction', 'দিক')) ?></span>
            <select name="direction" class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
                <option value=""><?= e($L('All', 'সব')) ?></option>
                <option value="outbound" <?= ($filters['direction'] ?? '') === 'outbound' ? 'selected' : '' ?>><?= e($L('Outbound', 'আউটবাউন্ড')) ?></option>
                <option value="inbound" <?= ($filters['direction'] ?? '') === 'inbound' ? 'selected' : '' ?>><?= e($L('Inbound', 'ইনবাউন্ড')) ?></option>
            </select>
        </label>
        <label class="flex flex-col gap-1 sm:col-span-2">
            <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e($L('Status', 'স্ট্যাটাস')) ?></span>
            <select name="status" class="rounded-xl border border-surface-container-high bg-surface px-3 py-2.5 font-body-sm text-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
                <option value=""><?= e($L('All', 'সব')) ?></option>
                <?php foreach (['sent', 'delivered', 'read', 'received', 'failed'] as $st): ?>
                <option value="<?= e($st) ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="sm:col-span-2 flex gap-2">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-surface-container-high text-on-surface font-label-md text-label-md font-bold hover:bg-surface-container-highest transition-colors">
                <span class="material-symbols-outlined text-[18px]">search</span><?= e(trans('admin_search')) ?>
            </button>
            <?php if ($query): ?>
            <a href="<?= e($baseUrl) ?>" class="inline-flex items-center justify-center px-3 py-2.5 rounded-xl bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high transition-colors" title="<?= e(trans('admin_clear')) ?>">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </a>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if ($logs === []): ?>
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <span class="material-symbols-outlined text-[44px] text-on-surface-variant">inbox</span>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= e($L('No messages logged yet.', 'এখনো কোনো মেসেজ লগ হয়নি।')) ?></p>
</section>
<?php else: ?>

<section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="hidden lg:grid grid-cols-12 gap-3 px-space-lg py-3 border-b border-surface-container font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
        <span class="col-span-1"></span>
        <span class="col-span-3"><?= e($L('Recipient', 'প্রাপক')) ?></span>
        <span class="col-span-2"><?= e($L('Template', 'টেমপ্লেট')) ?></span>
        <span class="col-span-2"><?= e($L('Status', 'স্ট্যাটাস')) ?></span>
        <span class="col-span-3"><?= e($L('Date', 'তারিখ')) ?></span>
        <span class="col-span-1 text-right"></span>
    </div>
    <div class="divide-y divide-surface-container">
        <?php foreach ($logs as $log):
            $channel   = (string)($log['channel'] ?? '');
            $direction = (string)($log['direction'] ?? '');
            $status    = (string)($log['status'] ?? '');
            $payload   = (string)($log['payload'] ?? '');
            $pretty    = '';
            $decoded   = json_decode($payload, true);
            if (is_array($decoded)) {
                $pretty = (string)json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $isInbound = $direction === 'inbound';
        ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-2 lg:gap-3 items-center px-space-lg py-3 hover:bg-surface-container-low transition-colors">
            <div class="col-span-1 flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg <?= $isInbound ? 'bg-secondary-fixed/50 text-secondary' : 'bg-primary-fixed/30 text-primary' ?> flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[18px]"><?= $isInbound ? 'call_received' : 'call_made' ?></span>
                </span>
                <span class="lg:hidden font-label-sm text-label-sm text-on-surface-variant font-semibold"><?= e(ucfirst($channel)) ?></span>
            </div>
            <div class="col-span-3 pl-10 lg:pl-0 min-w-0">
                <span class="block font-label-md text-label-md text-on-surface font-semibold truncate"><?= e((string)($log['recipient'] ?? '')) ?></span>
                <span class="block font-body-sm text-body-sm text-on-surface-variant truncate">
                    <?= e(ucfirst($channel)) ?> · <?= e($isInbound ? $L('inbound', 'ইনবাউন্ড') : $L('outbound', 'আউটবাউন্ড')) ?>
                </span>
            </div>
            <div class="col-span-2 pl-10 lg:pl-0">
                <?php if ((string)($log['template'] ?? '') !== ''): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold"><?= e((string)$log['template']) ?></span>
                <?php else: ?>
                <span class="font-body-sm text-body-sm text-on-surface-variant">—</span>
                <?php endif; ?>
            </div>
            <div class="col-span-2 pl-10 lg:pl-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full <?= e($statusClass($status)) ?> font-label-sm text-label-sm font-semibold"><?= e($status !== '' ? $status : '—') ?></span>
            </div>
            <div class="col-span-3 pl-10 lg:pl-0">
                <span class="font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap"><?= e(format_date((string)($log['created_at'] ?? ''), 'd M Y H:i')) ?></span>
                <?php if ((string)($log['error'] ?? '') !== ''): ?>
                <span class="block font-body-sm text-body-sm text-error truncate" title="<?= e((string)$log['error']) ?>"><?= e((string)$log['error']) ?></span>
                <?php endif; ?>
            </div>
            <div class="col-span-1 pl-10 lg:pl-0 flex justify-end">
                <?php if ($pretty !== ''): ?>
                <details class="relative">
                    <summary class="list-none cursor-pointer inline-flex items-center gap-1 px-2 py-1.5 rounded-lg bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm font-semibold hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[16px]">code</span>
                    </summary>
                    <pre class="absolute right-0 z-20 mt-2 w-[min(80vw,520px)] max-h-72 overflow-auto rounded-xl bg-inverse-surface text-inverse-on-surface p-3 text-[11px] leading-relaxed shadow-xl whitespace-pre-wrap break-all"><?= e($pretty) ?></pre>
                </details>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($pages > 1): ?>
<nav class="flex items-center justify-between gap-space-md" aria-label="pagination">
    <?php if ($page > 1): ?>
    <a href="<?= e($pageUrl($page - 1)) ?>" class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors">
        <span class="material-symbols-outlined text-[18px]">chevron_left</span><?= e(trans('admin_prev')) ?>
    </a>
    <?php else: ?><span></span><?php endif; ?>
    <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e(format_number($page)) ?> / <?= e(format_number($pages)) ?></span>
    <?php if ($page < $pages): ?>
    <a href="<?= e($pageUrl($page + 1)) ?>" class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors">
        <?= e(trans('admin_next')) ?><span class="material-symbols-outlined text-[18px]">chevron_right</span>
    </a>
    <?php else: ?><span></span><?php endif; ?>
</nav>
<?php endif; ?>

<?php endif; ?>

<!-- Retention -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-md rounded-2xl bg-surface-container-low">
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        <?= e($L('Logs older than 30 days can be deleted to keep the table small.', 'টেবিল ছোট রাখতে ৩০ দিনের পুরনো লগ মুছে ফেলতে পারেন।')) ?>
    </p>
    <form method="post" action="<?= e(locale_url('/mf-dashboard/message-logs/clear')) ?>" class="flex items-center gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="older">
        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-high text-on-surface font-label-sm text-label-sm font-semibold hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[16px]">delete_sweep</span><?= e($L('Clear old logs', 'পুরনো লগ মুছুন')) ?>
        </button>
    </form>
</section>
</div>

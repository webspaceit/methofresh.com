<?php
// Messaging conversations — chatbot threads and human handovers.
$conversations = (array)($conversations ?? []);
$total         = (int)($total ?? 0);
$page          = max(1, (int)($page ?? 1));
$perPage       = max(1, (int)($perPage ?? 30));
$pages         = max(1, (int)ceil($total / $perPage));
$statusFilter  = (string)($statusFilter ?? '');
$counts        = (array)($counts ?? []);
$baseUrl       = locale_url('/mf-dashboard/conversations');

$L = static function (string $en, string $bn): string {
    return app_locale() === 'bn' ? $bn : $en;
};

$pageUrl = static function (int $n) use ($baseUrl, $statusFilter): string {
    $qs = [];
    if ($n > 1) { $qs['page'] = $n; }
    if ($statusFilter !== '') { $qs['status'] = $statusFilter; }
    return $baseUrl . ($qs ? '?' . http_build_query($qs) : '');
};

$filterUrl = static function (string $status) use ($baseUrl): string {
    return $baseUrl . ($status !== '' ? '?status=' . rawurlencode($status) : '');
};

$statusMeta = [
    'open'   => ['label' => $L('Bot active', 'বট সক্রিয়'), 'class' => 'bg-primary-fixed/40 text-primary', 'icon' => 'smart_toy'],
    'human'  => ['label' => $L('Human agent', 'হিউম্যান এজেন্ট'), 'class' => 'bg-secondary-fixed/60 text-secondary', 'icon' => 'support_agent'],
    'closed' => ['label' => $L('Closed', 'বন্ধ'), 'class' => 'bg-surface-container text-on-surface-variant', 'icon' => 'done_all'],
];

$tabs = [
    ''       => $L('All', 'সব') . ' (' . (int)($counts['total'] ?? 0) . ')',
    'open'   => $L('Bot active', 'বট সক্রিয়') . ' (' . (int)($counts['open'] ?? 0) . ')',
    'human'  => $L('Human agent', 'হিউম্যান এজেন্ট') . ' (' . (int)($counts['human'] ?? 0) . ')',
    'closed' => $L('Closed', 'বন্ধ') . ' (' . (int)($counts['closed'] ?? 0) . ')',
];
?>
<div class="flex flex-col gap-space-lg">

<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
            <span class="material-symbols-outlined text-[26px]">support_agent</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_conversations')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e($L('WhatsApp and Messenger threads handled by the bot or an agent.', 'বট বা এজেন্ট যে WhatsApp ও Messenger থ্রেডগুলো সামলাচ্ছে।')) ?></p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= e(locale_url('/mf-dashboard/message-logs')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-surface-container-low text-on-surface font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[18px]">forum</span><?= e(trans('admin_message_logs')) ?>
        </a>
        <a href="<?= e(locale_url('/mf-dashboard/messaging')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-bold hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-[18px]">settings</span><?= e(trans('admin_messaging')) ?>
        </a>
    </div>
</section>

<section class="flex flex-wrap gap-2">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="<?= e($filterUrl((string)$key)) ?>"
       class="inline-flex items-center px-3.5 py-2 rounded-xl font-label-md text-label-md font-semibold transition-colors <?= $statusFilter === (string)$key ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high' ?>">
        <?= e($label) ?>
    </a>
    <?php endforeach; ?>
</section>

<?php if ($conversations === []): ?>
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <span class="material-symbols-outlined text-[44px] text-on-surface-variant">mark_chat_unread</span>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= e($L('No conversations yet. They appear when a customer messages your page or WhatsApp number.', 'এখনো কোনো কনভার্সেশন নেই। গ্রাহক আপনার পেজ বা WhatsApp নম্বরে মেসেজ করলে এখানে দেখা যাবে।')) ?></p>
</section>
<?php else: ?>

<section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="hidden sm:grid grid-cols-12 gap-3 px-space-lg py-3 border-b border-surface-container font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
        <span class="col-span-5"><?= e($L('Customer', 'গ্রাহক')) ?></span>
        <span class="col-span-2"><?= e($L('Channel', 'চ্যানেল')) ?></span>
        <span class="col-span-2"><?= e($L('Last activity', 'সর্বশেষ কার্যকলাপ')) ?></span>
        <span class="col-span-3 text-right"><?= e($L('Status / action', 'স্ট্যাটাস / অ্যাকশন')) ?></span>
    </div>
    <div class="divide-y divide-surface-container">
        <?php foreach ($conversations as $c):
            $id       = (int)($c['id'] ?? 0);
            $channel  = (string)($c['channel'] ?? 'whatsapp');
            $name     = (string)($c['display_name'] ?? '');
            $phone    = (string)($c['phone'] ?? '');
            $psid     = (string)($c['psid'] ?? '');
            $status   = (string)($c['status'] ?? 'open');
            $meta     = $statusMeta[$status] ?? $statusMeta['open'];
            $identifier = $channel === 'whatsapp' ? $phone : $psid;
            $last     = (string)($c['last_message_at'] ?? $c['created_at'] ?? '');
            $initial  = mb_strtoupper(mb_substr($name !== '' ? $name : ($identifier !== '' ? $identifier : '?'), 0, 1));
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center px-space-lg py-3.5 hover:bg-surface-container-low transition-colors">
            <div class="col-span-5 flex items-center gap-3 min-w-0">
                <span class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant font-bold text-sm shrink-0"><?= e($initial) ?></span>
                <div class="min-w-0">
                    <span class="block font-label-md text-label-md text-on-surface font-semibold truncate"><?= e($name !== '' ? $name : ($identifier !== '' ? $identifier : '—')) ?></span>
                    <span class="block font-body-sm text-body-sm text-on-surface-variant truncate"><?= e($identifier) ?></span>
                </div>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold">
                    <span class="material-symbols-outlined text-[14px]"><?= $channel === 'whatsapp' ? 'chat' : 'forum' ?></span>
                    <?= e(ucfirst($channel)) ?>
                </span>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0">
                <span class="font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap"><?= e($last !== '' ? format_date($last, 'd M Y H:i') : '—') ?></span>
            </div>
            <div class="col-span-3 pl-12 sm:pl-0 flex flex-wrap items-center justify-end gap-1.5">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full <?= e($meta['class']) ?> font-label-sm text-label-sm font-semibold">
                    <span class="material-symbols-outlined text-[14px]"><?= e($meta['icon']) ?></span><?= e($meta['label']) ?>
                </span>
                <form method="post" action="<?= e(locale_url('/mf-dashboard/conversations/' . $id . '/status')) ?>" class="flex items-center gap-1">
                    <?= csrf_field() ?>
                    <?php foreach (['open', 'human', 'closed'] as $next): if ($next === $status) { continue; } ?>
                    <button type="submit" name="status" value="<?= e($next) ?>"
                            class="inline-flex items-center px-2 py-1.5 rounded-lg bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm font-semibold hover:bg-surface-container-high transition-colors">
                        <?= e($statusMeta[$next]['label']) ?>
                    </button>
                    <?php endforeach; ?>
                </form>
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
</div>

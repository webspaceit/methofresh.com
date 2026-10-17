<?php
// Contact Messages — Material admin (locale-aware via trans()).
$messages = (array)($messages ?? []);
$total    = (int)($total ?? 0);
$unread   = (int)($unread ?? 0);
$page     = max(1, (int)($page ?? 1));
$perPage  = max(1, (int)($perPage ?? 30));
$pages    = max(1, (int)ceil($total / $perPage));
$baseUrl  = locale_url('/mf-dashboard/messages');

$pageUrl = static function (int $n) use ($baseUrl): string {
    return $baseUrl . ($n > 1 ? '?page=' . $n : '');
};
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-primary-fixed/40 flex items-center justify-center text-primary shrink-0">
            <span class="material-symbols-outlined text-[26px]">mark_email_unread</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_messages')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_messages_sub')) ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <?php if ($unread > 0): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-secondary-container text-on-secondary-container font-label-md text-label-md font-bold">
            <span class="material-symbols-outlined text-[18px]">fiber_new</span>
            <?= e(format_number($unread)) ?> <?= e(trans('admin_messages_unread')) ?>
        </span>
        <?php endif; ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold">
            <span class="material-symbols-outlined text-[18px]">inbox</span>
            <?= e(format_number($total)) ?>
        </span>
    </div>
</section>

<?php if (empty($messages)): ?>
<!-- Empty state -->
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <span class="material-symbols-outlined text-[44px] text-on-surface-variant">mark_email_read</span>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_messages_empty')) ?></p>
</section>
<?php else: ?>

<section class="flex flex-col divide-y divide-surface-container rounded-2xl bg-surface-container-lowest shadow-sm overflow-hidden">
    <?php foreach ($messages as $m):
        $isRead  = (int)($m['is_read'] ?? 0) === 1;
        $name    = (string)($m['name'] ?? '');
        $email   = (string)($m['email'] ?? '');
        $phone   = (string)($m['phone'] ?? '');
        $subject = trim((string)($m['subject'] ?? ''));
        $body    = (string)($m['message'] ?? '');
        $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';
    ?>
    <article class="flex flex-col gap-3 p-space-lg <?= $isRead ? '' : 'bg-primary-fixed/10' ?>">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-full bg-surface-container flex items-center justify-center text-primary shrink-0 font-label-lg text-label-lg font-bold">
                <?= e($initial) ?>
            </div>
            <div class="flex flex-col min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-label-lg text-label-lg text-on-surface font-bold truncate"><?= e($name) ?></span>
                    <?php if ($isRead): ?>
                    <span class="px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold"><?= e(trans('admin_message_read')) ?></span>
                    <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-label-sm text-label-sm font-semibold"><?= e(trans('admin_message_unread')) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 font-body-sm text-body-sm text-on-surface-variant mt-0.5">
                    <?php if ($email !== ''): ?>
                    <a href="mailto:<?= e($email) ?>" class="inline-flex items-center gap-1 hover:text-primary hover:underline">
                        <span class="material-symbols-outlined text-[16px]">mail</span><?= e($email) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($phone !== ''): ?>
                    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" class="inline-flex items-center gap-1 hover:text-primary hover:underline">
                        <span class="material-symbols-outlined text-[16px]">call</span><?= e($phone) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap"><?= e(format_date((string)($m['created_at'] ?? ''), 'd M Y, H:i')) ?></span>
                <?php if (!$isRead): ?>
                <form method="post" action="<?= e(locale_url('/mf-dashboard/messages/' . (int)$m['id'] . '/read')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-semibold hover:bg-primary-container transition-colors">
                        <span class="material-symbols-outlined text-[16px]">done_all</span><?= e(trans('admin_message_mark_read')) ?>
                    </button>
                </form>
                <?php endif; ?>
                <form method="post" action="<?= e(locale_url('/mf-dashboard/messages/' . (int)$m['id'] . '/delete')) ?>" onsubmit="return confirm('<?= e(trans('admin_delete_confirm')) ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold hover:bg-error-container hover:text-on-error-container transition-colors">
                        <span class="material-symbols-outlined text-[16px]">delete</span><?= e(trans('admin_delete')) ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="pl-14 flex flex-col gap-2">
            <span class="font-label-md text-label-md text-on-surface font-semibold">
                <?= e($subject !== '' ? $subject : trans('admin_message_no_subject')) ?>
            </span>
            <div class="rounded-xl bg-surface-container-low border border-surface-container px-4 py-3 font-body-md text-body-md text-on-surface-variant whitespace-pre-wrap break-words"><?= e($body) ?></div>
        </div>
    </article>
    <?php endforeach; ?>
</section>

<?php if ($pages > 1): ?>
<nav class="flex items-center justify-between gap-space-md" aria-label="pagination">
    <?php if ($page > 1): ?>
    <a href="<?= e($pageUrl($page - 1)) ?>" class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors">
        <span class="material-symbols-outlined text-[18px]">chevron_left</span><?= e(trans('admin_prev')) ?>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
    <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e(format_number($page)) ?> / <?= e(format_number($pages)) ?></span>
    <?php if ($page < $pages): ?>
    <a href="<?= e($pageUrl($page + 1)) ?>" class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors">
        <?= e(trans('admin_next')) ?><span class="material-symbols-outlined text-[18px]">chevron_right</span>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php endif; ?>
</div>

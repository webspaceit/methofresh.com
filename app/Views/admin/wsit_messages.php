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
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[26px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M160-80q-33 0-56.5-23.5T80-160v-640q0-33 23.5-56.5T160-880h640q20 0 38 8.5t29 23.5q-41 3-77 20.5T720-784H160v640h324q-4 18-1 36t9 34H160Zm160-560h320q17 0 28.5 11.5T680-600q0 17-11.5 28.5T640-560H320q-17 0-28.5-11.5T280-600q0-17 11.5-28.5T320-640Zm0 160h320q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400H320q-17 0-28.5-11.5T280-440q0-17 11.5-28.5T320-480Zm0 160h200q0-17 11.5-28.5T560-360q17 0 28.5 11.5T600-320q0 17-11.5 28.5T560-280H320q-17 0-28.5-11.5T280-320q0-17 11.5-28.5T320-360v-120q0-17 11.5-28.5T320-520Z"/></svg>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_messages')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_messages_sub')) ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <?php if ($unread > 0): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-secondary-container text-on-secondary-container font-label-md text-label-md font-bold">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M480-80q-122 0-206-84-84-84-84-206 0-122 84-206 84-84 206-84 122 0 206 84 84 84 84 206 0 122-84 206-84 84-206 84Zm0-80q109 0 190.5-63.5T790-380H170q47 93 128.5 156.5T480-160ZM170-580h620q-47-93-128.5-156.5T480-800q-109 0-190.5 63.5T170-580Z"/></svg>
            <?= e(format_number($unread)) ?> <?= e(trans('admin_messages_unread')) ?>
        </span>
        <?php endif; ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T940-720v480q0 33-23.5 56.5T860-160H160Zm320-280 320-200v-80L480-520 160-720v80Z"/></svg>
            <?= e(format_number($total)) ?>
        </span>
    </div>
</section>

<?php if (empty($messages)): ?>
<!-- Empty state -->
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[44px] text-on-surface-variant shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h320q17 0 28.5 11.5T520-760q0 17-11.5 28.5T480-720H160v480h480v-120q0-17 11.5-28.5T680-320q17 0 28.5 11.5T720-280v120q0 33-23.5 56.5T640-80H160Zm320-560q17 0 28.5-11.5T520-680q0-17-11.5-28.5T480-720q-17 0-28.5 11.5T440-680q0 17 11.5 28.5T480-640Zm-320 160h320q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400H160q-17 0-28.5-11.5T120-440q0-17 11.5-28.5T160-480Zm0 160h200q17 0 28.5 11.5T400-280q0 17-11.5 28.5T360-240H160q-17 0-28.5-11.5T120-280q0-17 11.5-28.5T160-320Z"/></svg>
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
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[16px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T940-720v480q0 33-23.5 56.5T860-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200Z"/></svg><?= e($email) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($phone !== ''): ?>
                    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>" class="inline-flex items-center gap-1 hover:text-primary hover:underline">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[16px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12Z"/></svg><?= e($phone) ?>
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
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[16px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M268-240 42-466l57-56 170 170 56 56-57 56Zm226 0L268-466l56-57 170 170 368-368 56 57-424 424Zm0-226-57-56 198-198 57 56-198 198Z"/></svg><?= e(trans('admin_message_mark_read')) ?>
                    </button>
                </form>
                <?php endif; ?>
                <form method="post" action="<?= e(locale_url('/mf-dashboard/messages/' . (int)$m['id'] . '/delete')) ?>" onsubmit="return confirm('<?= e(trans('admin_delete_confirm')) ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold hover:bg-error-container hover:text-on-error-container transition-colors">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[16px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M280-120q-33 0-56.5-23.5T200-200v-520q0-17-11.5-28.5T160-760v-40q0-17 11.5-28.5T160-840h280q17 0 28.5 11.5T480-800h160q17 0 28.5 11.5T680-760q0 17-11.5 28.5T640-720v40q0 17-11.5 28.5T600-640H280v520h440q17 0 28.5 11.5T760-80v40q0 17-11.5 28.5T720-40H280Z"/></svg><?= e(trans('admin_delete')) ?>
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
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M560-240 280-520l280-280 56 56-224 224 224 224-56 56Z"/></svg><?= e(trans('admin_prev')) ?>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
    <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e(format_number($page)) ?> / <?= e(format_number($pages)) ?></span>
    <?php if ($page < $pages): ?>
    <a href="<?= e($pageUrl($page + 1)) ?>" class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors">
        <?= e(trans('admin_next')) ?><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M320-240 640-520 320-800l-56 56 224 224-224 224 56 56Z"/></svg>
    </a>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php endif; ?>
</div>

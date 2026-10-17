<?php
// Newsletter Subscribers — Material admin (locale-aware via trans()).
$subscribers = (array)($subscribers ?? []);
$total       = (int)($total ?? 0);
$active      = (int)($active ?? 0);
$page        = max(1, (int)($page ?? 1));
$perPage     = max(1, (int)($perPage ?? 30));
$pages       = max(1, (int)ceil($total / $perPage));
$baseUrl     = locale_url('/mf-dashboard/subscribers');

$pageUrl = static function (int $n) use ($baseUrl): string {
    return $baseUrl . ($n > 1 ? '?page=' . $n : '');
};
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[26px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T940-720v480q0 33-23.5 56.5T860-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200Z"/></svg>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_subscribers')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_subscribers_sub')) ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary-fixed/40 text-primary font-label-md text-label-md font-bold">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="m424-312 288-288-56-56-232 232-114-114-56 56 170 170Zm56 232q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>
            <?= e(format_number($active)) ?> <?= e(trans('admin_subscribers_active')) ?>
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM160-160v-112q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q66 0 130 15.5T736-378q29 15 46.5 43.5T800-272v112H160Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T400-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T400-560Z"/></svg>
            <?= e(format_number($total)) ?>
        </span>
    </div>
</section>

<?php if (empty($subscribers)): ?>
<!-- Empty state -->
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[44px] text-on-surface-variant shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M200-160q-33 0-56.5-23.5T120-240v-560q0-33 23.5-56.5T200-880h560q33 0 56.5 23.5T880-800v280h-80v-280H200v560h280v80H200Zm280-280 320-200v-80L480-520 160-720v80l320 200Z"/></svg>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_subscribers_empty')) ?></p>
</section>
<?php else: ?>

<section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="hidden sm:grid grid-cols-12 gap-3 px-space-lg py-3 border-b border-surface-container font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
        <span class="col-span-5"><?= e(trans('admin_subscriber_email')) ?></span>
        <span class="col-span-2"><?= e(trans('admin_subscriber_locale')) ?></span>
        <span class="col-span-2"><?= e(trans('admin_subscriber_status')) ?></span>
        <span class="col-span-2"><?= e(trans('admin_subscriber_joined')) ?></span>
        <span class="col-span-1 text-right"></span>
    </div>
    <div class="divide-y divide-surface-container">
        <?php foreach ($subscribers as $s):
            $email  = (string)($s['email'] ?? '');
            $locale = (string)($s['locale'] ?? 'en');
            $isActive = (int)($s['is_active'] ?? 1) === 1;
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center px-space-lg py-3.5 hover:bg-surface-container-low transition-colors">
            <div class="col-span-5 flex items-center gap-3 min-w-0">
                <span class="w-9 h-9 rounded-full bg-surface-container flex items-center justify-center text-primary shrink-0">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[18px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T940-720v480q0 33-23.5 56.5T860-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200Z"/></svg>
                </span>
                <a href="mailto:<?= e($email) ?>" class="font-label-md text-label-md text-on-surface font-semibold truncate hover:text-primary hover:underline"><?= e($email) ?></a>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold uppercase"><?= e($locale) ?></span>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0">
                <?php if ($isActive): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed/50 text-primary font-label-sm text-label-sm font-semibold">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[14px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg><?= e(trans('admin_subscribers_active')) ?>
                </span>
                <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold"><?= e(trans('admin_subscribers_inactive')) ?></span>
                <?php endif; ?>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0 sm:text-right">
                <span class="font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap"><?= e(format_date((string)($s['created_at'] ?? ''), 'd M Y, H:i')) ?></span>
            </div>
            <div class="col-span-1 pl-12 sm:pl-0 text-right">
                <form method="post" action="<?= e(locale_url('/mf-dashboard/subscribers/' . (int)$s['id'] . '/delete')) ?>" onsubmit="return confirm('<?= e(trans('admin_delete_confirm')) ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm font-semibold hover:bg-error-container hover:text-on-error-container transition-colors">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="text-[16px] shrink-0" style="width:1em;height:1em;display:inline-block;vertical-align:-0.125em;"><path d="M280-120q-33 0-56.5-23.5T200-200v-520q0-17-11.5-28.5T160-760v-40q0-17 11.5-28.5T160-840h280q17 0 28.5 11.5T480-800h160q17 0 28.5 11.5T680-760q0 17-11.5 28.5T640-720v40q0 17-11.5 28.5T600-640H280v520h440q17 0 28.5 11.5T760-80v40q0 17-11.5 28.5T720-40H280Z"/></svg><?= e(trans('admin_delete')) ?>
                    </button>
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

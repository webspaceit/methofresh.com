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
            <span class="material-symbols-outlined text-[26px]">mail</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_subscribers')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_subscribers_sub')) ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary-fixed/40 text-primary font-label-md text-label-md font-bold">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <?= e(format_number($active)) ?> <?= e(trans('admin_subscribers_active')) ?>
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold">
            <span class="material-symbols-outlined text-[18px]">groups</span>
            <?= e(format_number($total)) ?>
        </span>
    </div>
</section>

<?php if (empty($subscribers)): ?>
<!-- Empty state -->
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <span class="material-symbols-outlined text-[44px] text-on-surface-variant">forward_to_inbox</span>
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
                    <span class="material-symbols-outlined text-[18px]">mail</span>
                </span>
                <a href="mailto:<?= e($email) ?>" class="font-label-md text-label-md text-on-surface font-semibold truncate hover:text-primary hover:underline"><?= e($email) ?></a>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold uppercase"><?= e($locale) ?></span>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0">
                <?php if ($isActive): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed/50 text-primary font-label-sm text-label-sm font-semibold">
                    <span class="material-symbols-outlined text-[14px]">check</span><?= e(trans('admin_subscribers_active')) ?>
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
                        <span class="material-symbols-outlined text-[16px]">delete</span><?= e(trans('admin_delete')) ?>
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

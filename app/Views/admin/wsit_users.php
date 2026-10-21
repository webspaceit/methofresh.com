<?php
// Registered users — Material admin (locale-aware via trans()).
$users     = (array)($users ?? []);
$total     = (int)($total ?? 0);
$customers = (int)($customers ?? 0);
$admins    = (int)($admins ?? 0);
$page      = max(1, (int)($page ?? 1));
$perPage   = max(1, (int)($perPage ?? 20));
$pages     = max(1, (int)ceil($total / $perPage));
$search    = (string)($search ?? '');
$guestOrders = (array)($guestOrders ?? []);
$baseUrl   = locale_url('/mf-dashboard/users');

$pageUrl = static function (int $n) use ($baseUrl, $search): string {
    $qs = [];
    if ($n > 1)      { $qs[] = 'page=' . $n; }
    if ($search !== '') { $qs[] = 'q=' . rawurlencode($search); }
    return $baseUrl . ($qs ? '?' . implode('&', $qs) : '');
};
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
            <span class="material-symbols-outlined text-[26px]">group</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_users')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_users_sub')) ?></p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary-fixed/40 text-primary font-label-md text-label-md font-bold">
            <span class="material-symbols-outlined text-[18px]">shield_person</span>
            <?= e(format_number($admins)) ?> <?= e(trans('admin_users_admins')) ?>
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold">
            <span class="material-symbols-outlined text-[18px]">groups</span>
            <?= e(format_number($customers)) ?> <?= e(trans('admin_users_customers')) ?>
        </span>
        <a href="<?= e(locale_url('/mf-dashboard/users/create')) ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-bold hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-[18px]">person_add</span><?= e(trans('admin_user_create')) ?>
        </a>
    </div>
</section>

<?php if (!empty($guestOrders)): ?>
<!-- Orders from before guest checkout created accounts: they have no
     customer behind them yet. One click fixes that. -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-[22px] text-amber-700 shrink-0">person_alert</span>
        <div>
            <p class="text-sm font-semibold text-amber-900"><?= e(trans('admin_users_guest_orders_title')) ?></p>
            <p class="text-xs text-amber-800 mt-0.5"><?= e(trans('admin_users_guest_orders_notice', [
                'emails' => count($guestOrders),
                'orders' => array_sum(array_map(static fn($g) => (int)($g['orders'] ?? 0), $guestOrders)),
            ])) ?></p>
        </div>
    </div>
    <form method="post" action="<?= e(locale_url('/mf-dashboard/users/guest-accounts')) ?>"
          onsubmit="return confirm('<?= e(trans('admin_users_guest_orders_confirm')) ?>')">
        <?= csrf_field() ?>
        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-label-md font-label-md font-bold transition-colors">
            <span class="material-symbols-outlined text-[18px]">person_add</span><?= e(trans('admin_users_guest_orders_btn')) ?>
        </button>
    </form>
</section>
<?php endif; ?>

<!-- Search -->
<section class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
    <form method="get" action="<?= e($baseUrl) ?>" class="flex flex-col sm:flex-row gap-3">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(trans('admin_user_search_placeholder')) ?>"
               class="flex-1 rounded-xl border border-surface-container-high bg-surface px-4 py-2.5 font-body-md text-body-md text-on-surface placeholder-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/40">
        <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-surface-container-high text-on-surface font-label-md text-label-md font-bold hover:bg-surface-container-highest transition-colors">
            <span class="material-symbols-outlined text-[18px]">search</span><?= e(trans('admin_search')) ?>
        </button>
        <?php if ($search !== ''): ?>
        <a href="<?= e($baseUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors">
            <span class="material-symbols-outlined text-[18px]">close</span><?= e(trans('admin_clear')) ?>
        </a>
        <?php endif; ?>
    </form>
</section>

<?php if (empty($users)): ?>
<!-- Empty state -->
<section class="flex flex-col items-center justify-center gap-2 p-space-2xl rounded-2xl bg-surface-container-lowest shadow-sm text-center">
    <span class="material-symbols-outlined text-[44px] text-on-surface-variant">person_search</span>
    <p class="font-body-md text-body-md text-on-surface-variant"><?= e($search !== '' ? trans('admin_user_no_results') : trans('admin_users_empty')) ?></p>
</section>
<?php else: ?>

<section class="overflow-hidden rounded-2xl bg-surface-container-lowest shadow-sm">
    <div class="hidden sm:grid grid-cols-12 gap-3 px-space-lg py-3 border-b border-surface-container font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">
        <span class="col-span-4"><?= e(trans('admin_user_name')) ?></span>
        <span class="col-span-3"><?= e(trans('admin_user_contact')) ?></span>
        <span class="col-span-2"><?= e(trans('admin_user_role')) ?></span>
        <span class="col-span-1"><?= e(trans('admin_user_joined')) ?></span>
        <span class="col-span-2 text-right"><?= e(trans('admin_actions')) ?></span>
    </div>
    <div class="divide-y divide-surface-container">
        <?php foreach ($users as $u):
            $uId     = (int)($u['id'] ?? 0);
            $uName   = (string)($u['name'] ?? '');
            $uEmail  = (string)($u['email'] ?? '');
            $uPhone  = (string)($u['phone'] ?? '');
            $uCity   = (string)($u['city'] ?? '');
            $uAdmin  = (int)($u['is_admin'] ?? 0) === 1;
            $uAuto   = (int)($u['auto_registered'] ?? 0) === 1;
            $initial = mb_strtoupper(mb_substr($uName !== '' ? $uName : $uEmail, 0, 1));
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center px-space-lg py-3.5 hover:bg-surface-container-low transition-colors">
            <div class="col-span-4 flex items-center gap-3 min-w-0">
                <span class="w-9 h-9 rounded-full <?= $uAdmin ? 'bg-primary-fixed/60 text-primary' : 'bg-surface-container flex items-center justify-center text-on-surface-variant' ?> flex items-center justify-center font-bold text-sm shrink-0">
                    <?= $uAdmin ? '<span class="material-symbols-outlined text-[18px]">shield_person</span>' : e($initial) ?>
                </span>
                <div class="min-w-0">
                    <span class="block font-label-md text-label-md text-on-surface font-semibold truncate"><?= e($uName !== '' ? $uName : '—') ?></span>
                    <span class="block font-body-sm text-body-sm text-on-surface-variant truncate"><?= e($uCity !== '' ? $uCity : '') ?></span>
                </div>
            </div>
            <div class="col-span-3 pl-12 sm:pl-0 min-w-0">
                <a href="mailto:<?= e($uEmail) ?>" class="block font-body-sm text-body-sm text-on-surface-variant truncate hover:text-primary hover:underline"><?= e($uEmail) ?></a>
                <?php if ($uPhone !== ''): ?>
                <a href="tel:<?= e($uPhone) ?>" class="block font-body-sm text-body-sm text-on-surface-variant truncate hover:text-primary hover:underline"><?= e($uPhone) ?></a>
                <?php endif; ?>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0 flex flex-wrap items-center gap-1.5">
                <?php if ($uAdmin): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed/50 text-primary font-label-sm text-label-sm font-semibold">
                    <span class="material-symbols-outlined text-[14px]">shield</span><?= e(trans('admin_users_admin')) ?>
                </span>
                <?php else: ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-label-sm text-label-sm font-semibold"><?= e(trans('admin_users_customer')) ?></span>
                <?php endif; ?>
                <?php if ($uAuto): ?>
                <!-- Created automatically at checkout; the customer has not
                     chosen a password yet. -->
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 font-label-sm text-label-sm font-semibold"
                      title="<?= e(trans('admin_users_auto_hint')) ?>">
                    <span class="material-symbols-outlined text-[14px]">key</span><?= e(trans('admin_users_auto')) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="col-span-1 pl-12 sm:pl-0">
                <span class="font-body-sm text-body-sm text-on-surface-variant whitespace-nowrap"><?= e(format_date((string)($u['created_at'] ?? ''), 'd M Y')) ?></span>
            </div>
            <div class="col-span-2 pl-12 sm:pl-0 flex items-center justify-end gap-1.5">
                <a href="<?= e(locale_url('/mf-dashboard/users/' . $uId . '/edit')) ?>"
                   class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm font-semibold hover:bg-primary-fixed/40 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[16px]">edit</span><?= e(trans('admin_edit')) ?>
                </a>
                <form method="post" action="<?= e(locale_url('/mf-dashboard/users/' . $uId . '/delete')) ?>" onsubmit="return confirm('<?= e(trans('admin_user_delete_confirm')) ?>')">
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

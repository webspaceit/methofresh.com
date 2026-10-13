<?php
// Customer Analytics — Material admin (locale-aware via trans()).
$m = (array)($metrics ?? []);
$totalCustomers    = (int)($m['total'] ?? 0);
$newThisMonth      = (int)($m['new_this_month'] ?? 0);
$repeatCustomers   = (int)($m['repeat'] ?? 0);
$totalOrders       = (int)($m['orders'] ?? 0);
$totalRevenue      = (float)($m['revenue'] ?? 0);
$avgSpend          = (float)($m['avg_spend'] ?? 0);
$newPct            = $totalCustomers > 0 ? (int)round(($newThisMonth / $totalCustomers) * 100) : 0;
$repeatPct         = $totalCustomers > 0 ? (int)round(($repeatCustomers / $totalCustomers) * 100) : 0;

$topCustomers = (array)($topCustomers ?? []);
$cities       = (array)($cities ?? []);
$maxCityCustomers = 0;
foreach ($cities as $c) {
    $maxCityCustomers = max($maxCityCustomers, (int)($c['customers'] ?? 0));
}
$cityBarPct = static fn(int $n) => $maxCityCustomers > 0 ? (int)round(($n / $maxCityCustomers) * 100) : 0;
?>
<div class="flex flex-col gap-space-lg">
<!-- Page header -->
<section class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="flex items-center gap-space-md">
        <div class="w-12 h-12 rounded-2xl bg-primary-fixed/40 flex items-center justify-center text-primary shrink-0">
            <span class="material-symbols-outlined text-[26px]">group</span>
        </div>
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e(trans('admin_customer_analytics')) ?></h1>
            <p class="font-body-md text-body-md text-on-surface-variant"><?= e(trans('admin_customer_analytics_sub')) ?></p>
        </div>
    </div>
    <a class="inline-flex items-center gap-2 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors" href="<?= e(locale_url('/mf-dashboard/orders')) ?>">
        <span class="material-symbols-outlined text-[18px]">local_mall</span>
        <span><?= e(trans('admin_orders')) ?></span>
    </a>
</section>

<!-- KPI row -->
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_total_customers')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number($totalCustomers)) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-primary-fixed/40 flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[22px]">groups</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="text-on-surface-variant"><?= e(trans('admin_orders_placed')) ?></span>
            <span class="text-on-surface font-semibold"><?= e(format_number($totalOrders)) ?></span>
        </div>
    </div>
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_new_customers')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number($newThisMonth)) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-secondary-fixed/50 flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[22px]">person_add</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-semibold">
                <span class="material-symbols-outlined text-[14px]">trending_up</span>+<?= e(format_number($newPct)) ?>%
            </span>
            <span class="text-on-surface-variant"><?= e(format_number($totalCustomers)) ?> <?= e(trans('admin_total_customers')) ?></span>
        </div>
    </div>
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_repeat_customers')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(format_number($repeatCustomers)) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-tertiary-fixed/40 flex items-center justify-center text-tertiary-container">
                <span class="material-symbols-outlined text-[22px]">repeat</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="text-on-surface-variant"><?= e(format_number($repeatPct)) ?>%</span>
            <span class="text-on-surface font-semibold"><?= e(trans('admin_orders_placed')) ?> &gt; 1</span>
        </div>
    </div>
    <div class="flex flex-col justify-between p-space-md rounded-2xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider"><?= e(trans('admin_avg_customer_spend')) ?></span>
                <span class="font-metric-stat text-metric-stat text-on-surface font-extrabold tracking-tight mt-1"><?= e(admin_price($avgSpend)) ?></span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center text-primary-container">
                <span class="material-symbols-outlined text-[22px]">savings</span>
            </div>
        </div>
        <div class="mt-4 pt-3 flex items-center justify-between border-t border-surface-container font-body-sm text-body-sm">
            <span class="text-on-surface-variant"><?= e(trans('admin_total_revenue')) ?></span>
            <span class="text-on-surface font-semibold"><?= e(admin_price($totalRevenue)) ?></span>
        </div>
    </div>
</section>

<!-- Top customers + city breakdown -->
<section class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg">
    <div class="lg:col-span-7 flex flex-col p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[22px]">workspace_premium</span>
                <div>
                    <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_top_customers')) ?></h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_customer_table_empty')) ?></p>
                </div>
            </div>
        </div>
        <?php if (empty($topCustomers)): ?>
        <p class="py-10 text-center font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_no_customers')) ?></p>
        <?php else: ?>
        <div class="w-full overflow-x-auto mt-space-sm">
            <table class="w-full text-left border-collapse font-body-md text-body-md">
                <thead>
                    <tr class="border-b border-surface-container text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                        <th class="py-3 px-4 font-semibold"><?= e(trans('admin_customer')) ?></th>
                        <th class="py-3 px-4 font-semibold"><?= e(trans('admin_city')) ?></th>
                        <th class="py-3 px-4 font-semibold"><?= e(trans('admin_orders_placed')) ?></th>
                        <th class="py-3 px-4 font-semibold text-right"><?= e(trans('admin_spend')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-container">
                    <?php foreach ($topCustomers as $i => $c):
                        $cName = customer_display_name((string)($c['name'] ?? ''), $c['email'] ?? null);
                    ?>
                    <tr class="hover:bg-surface-container-low/40 transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-<?= $i % 2 === 0 ? 'primary-fixed' : 'secondary-fixed/50' ?> flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-[18px] text-<?= $i % 2 === 0 ? 'primary' : 'secondary' ?>">person</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-semibold text-on-surface"><?= e($cName) ?></span>
                                    <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e((string)($c['phone'] ?? $c['email'] ?? '')) ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 text-on-surface-variant"><?= e((string)($c['city'] ?? '—')) ?></td>
                        <td class="py-3.5 px-4 font-label-md text-label-md font-bold text-on-surface"><?= e(format_number((int)($c['orders'] ?? 0))) ?></td>
                        <td class="py-3.5 px-4 font-label-lg text-label-lg font-bold text-on-surface text-right"><?= e(admin_price((float)($c['revenue'] ?? 0))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="lg:col-span-5 flex flex-col justify-between p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm">
        <div class="flex items-center justify-between pb-space-sm border-b border-surface-container">
            <div>
                <h2 class="font-headline-sm text-headline-sm text-on-surface font-bold"><?= e(trans('admin_city_breakdown')) ?></h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_customer_analytics_sub')) ?></p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-primary-fixed/40 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px]">location_city</span>
            </div>
        </div>
        <?php if (empty($cities)): ?>
        <p class="py-10 text-center font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_no_customers')) ?></p>
        <?php else: ?>
        <div class="flex flex-col gap-4 my-2">
            <?php foreach ($cities as $c):
                $city = (string)($c['city'] ?? '—');
                $cCustomers = (int)($c['customers'] ?? 0);
                $cOrders = (int)($c['orders'] ?? 0);
                $cRevenue = (float)($c['revenue'] ?? 0);
                $barWidth = $cityBarPct($cCustomers);
            ?>
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between font-label-md text-label-md">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-primary-container"></span>
                        <span class="text-on-surface font-semibold"><?= e($city) ?></span>
                    </div>
                    <div class="flex items-center gap-space-md">
                        <span class="text-on-surface-variant font-body-sm"><?= e(format_number($cCustomers)) ?> <?= e(trans('admin_total_customers')) ?> • <?= e(format_number($cOrders)) ?> <?= e(trans('admin_orders_placed')) ?></span>
                        <span class="text-primary font-bold font-label-lg text-label-lg"><?= e(admin_price($cRevenue)) ?></span>
                    </div>
                </div>
                <div class="w-full h-2.5 rounded-full bg-surface-container overflow-hidden">
                    <div class="h-full bg-primary-container rounded-full" style="width: <?= e((string)$barWidth) ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
</div>
<?php if (isset($pages) && $pages > 1): ?>
<?php
$currentQuery = $_GET;
unset($currentQuery['page']);
$queryString = http_build_query($currentQuery);
$params = $queryString !== '' ? '&' . $queryString : '';
?>
<nav class="mt-8 flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination" id="pagination-nav">
    <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?><?= e($params) ?>" class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl border border-stone-200 bg-white text-sm font-medium text-stone-700 hover:border-brand-600 hover:text-brand-700 shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        <span><?= e(trans('previous')) ?></span>
    </a>
    <?php endif; ?>

    <?php
    $start = max(1, $page - 2);
    $end = min($pages, $page + 2);
    if ($start > 1): ?>
        <a href="?page=1<?= e($params) ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-stone-200 bg-white text-sm font-semibold text-stone-700 hover:border-brand-600 hover:text-brand-700 shadow-sm transition"><?= e(format_number(1)) ?></a>
        <?php if ($start > 2): ?>
        <span class="w-8 h-10 inline-flex items-center justify-center text-stone-400 font-bold">...</span>
        <?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $page): ?>
        <span class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-brand-600 text-white text-sm font-bold shadow-md shadow-brand-600/20 ring-2 ring-brand-600 ring-offset-1"><?= e(format_number($i)) ?></span>
        <?php else: ?>
        <a href="?page=<?= $i ?><?= e($params) ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-stone-200 bg-white text-sm font-semibold text-stone-700 hover:border-brand-600 hover:text-brand-700 shadow-sm transition"><?= e(format_number($i)) ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $pages): ?>
        <?php if ($end < $pages - 1): ?>
        <span class="w-8 h-10 inline-flex items-center justify-center text-stone-400 font-bold">...</span>
        <?php endif; ?>
        <a href="?page=<?= $pages ?><?= e($params) ?>" class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-stone-200 bg-white text-sm font-semibold text-stone-700 hover:border-brand-600 hover:text-brand-700 shadow-sm transition"><?= e(format_number($pages)) ?></a>
    <?php endif; ?>

    <?php if ($page < $pages): ?>
    <a href="?page=<?= $page + 1 ?><?= e($params) ?>" class="inline-flex items-center gap-1 px-3.5 py-2 rounded-xl border border-stone-200 bg-white text-sm font-medium text-stone-700 hover:border-brand-600 hover:text-brand-700 shadow-sm transition">
        <span><?= e(trans('next')) ?></span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </a>
    <?php endif; ?>
</nav>
<?php endif; ?>
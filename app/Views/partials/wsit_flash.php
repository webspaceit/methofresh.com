<?php
// Session-based flash messages. Keys: success | error | info
$flashes = [];
foreach (['success', 'error', 'info'] as $type) {
    $message = \App\Core\wsit_Session::getFlash($type);
    if ($message !== null) {
        $flashes[$type] = $message;
    }
}
$flashTitle = [
    'success' => $locale === 'bn' ? 'সফল!' : 'Success!',
    'error'   => $locale === 'bn' ? 'দুঃখিত!' : 'Oops!',
    'info'    => $locale === 'bn' ? 'তথ্য' : 'Notice',
];
?>
<style>[x-cloak]{display:none!important}</style>
<?php if (!empty($flashes)): ?>
<div class="fixed inset-0 z-[100] flex flex-col gap-3 items-center justify-center bg-slate-950/55 p-4"
     x-data="{ open: true }" x-show="open" x-cloak
     @keydown.escape.window="open = false"
     role="presentation">
    <?php foreach ($flashes as $type => $message): ?>
    <section class="w-full max-w-md rounded-lg bg-white px-6 py-7 text-center shadow-2xl sm:px-8"
             role="alertdialog" aria-modal="true" aria-live="assertive"
             aria-labelledby="flash-title-<?= e($type) ?>" aria-describedby="flash-message-<?= e($type) ?>">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border-[3px] text-3xl font-light
                    <?= $type === 'success' ? 'border-emerald-300 text-emerald-500' : ($type === 'error' ? 'border-red-300 text-red-500' : 'border-sky-300 text-sky-500') ?>">
            <?= $type === 'success' ? '✓' : ($type === 'error' ? '×' : 'i') ?>
        </div>
        <h2 id="flash-title-<?= e($type) ?>" class="mb-2 text-xl font-semibold text-slate-700"><?= e($flashTitle[$type]) ?></h2>
        <p id="flash-message-<?= e($type) ?>" class="text-sm leading-6 text-slate-600"><?= e($message) ?></p>
        <button type="button" @click="open = false"
                class="mt-6 min-w-20 rounded bg-sky-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2">
            <?= $locale === 'bn' ? 'ঠিক আছে' : 'OK' ?>
        </button>
    </section>
    <?php endforeach; ?>
</div>
<?php endif; ?>

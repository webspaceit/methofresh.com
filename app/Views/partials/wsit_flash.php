<?php
// Session-based flash messages. Keys: success | error | info
$flashes = [];
foreach (['success', 'error', 'info'] as $type) {
    $message = \App\Core\wsit_Session::getFlash($type);
    if ($message !== null) {
        $flashes[$type] = $message;
    }
}
?>
<?php if (!empty($flashes)): ?>
<div class="fixed top-20 right-4 z-[60] space-y-2 w-[calc(100%-2rem)] max-w-sm">
    <?php foreach ($flashes as $type => $message): ?>
    <div x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 4000)"
         x-transition.opacity.duration.300ms
         class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium
                <?= $type === 'success' ? 'bg-green-600' : ($type === 'error' ? 'bg-red-600' : 'bg-sky-600') ?>">
        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs">
            <?= $type === 'success' ? '✓' : ($type === 'error' ? '!' : 'ℹ') ?>
        </span>
        <span><?= e($message) ?></span>
        <button type="button" @click="show = false" class="ml-auto text-white/80 hover:text-white">✕</button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
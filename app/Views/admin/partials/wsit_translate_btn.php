<?php
/**
 * Translate button for a Bengali field. Usage (with $source / $target CSS selectors):
 *   <?php $source = '[name=title_en]'; $target = '[name=title_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
 */
?>
<button type="button"
        onclick="bnTranslate(this, <?= e(json_encode($source)) ?>, <?= e(json_encode($target)) ?>)"
        class="ml-2 inline-flex items-center gap-1 rounded-lg border border-brand-200 bg-brand-50 px-2.5 py-1 text-[11px] font-bold text-brand-700 hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500"
        title="<?= e(trans('admin_translate_tip')) ?>">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
    </svg>
    <?= e(trans('admin_translate')) ?>
</button>
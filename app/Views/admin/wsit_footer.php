<?php
$footer = [
    'about_en'   => site_setting('footer_about_en'),
    'about_bn'   => site_setting('footer_about_bn'),
    'email'      => site_setting('footer_email'),
    'phone'      => site_setting('footer_phone'),
    'address_en' => site_setting('footer_address_en'),
    'address_bn' => site_setting('footer_address_bn'),
    'copyright_en' => site_setting('copyright_en'),
    'copyright_bn' => site_setting('copyright_bn'),
];
?>
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-stone-900"><?= e(trans('admin_footer')) ?></h1>
            <p class="text-sm text-stone-500 mt-1"><?= e(trans('admin_footer_subtitle')) ?></p>
        </div>
        <a href="<?= e(locale_url('/')) ?>" target="_blank" class="text-sm font-semibold text-brand-700 hover:underline"><?= e(trans('admin_view_website')) ?> →</a>
    </div>

    <form method="post" action="<?= e(locale_url('/mf-dashboard/footer')) ?>" class="bg-white rounded-2xl border border-stone-200 p-6 space-y-5">
        <?= csrf_field() ?>

        <div>
            <h2 class="text-sm font-bold text-stone-800 mb-3"><?= e(trans('admin_footer_about')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_en')) ?></label>
                    <textarea name="footer_about_en" rows="3" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($footer['about_en']) ?></textarea>
                </div>
                <div>
                    <div class="flex items-center">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                        <?php $source = '[name=footer_about_en]'; $target = '[name=footer_about_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <textarea name="footer_about_bn" rows="3" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($footer['about_bn']) ?></textarea>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-sm font-bold text-stone-800 mb-3"><?= e(trans('admin_footer_contact')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_email')) ?></label>
                    <input type="text" name="footer_email" value="<?= e($footer['email']) ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_footer_phone')) ?></label>
                    <input type="text" name="footer_phone" value="<?= e($footer['phone']) ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_en')) ?></label>
                    <textarea name="footer_address_en" rows="2" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($footer['address_en']) ?></textarea>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                        <?php $source = '[name=footer_address_en]'; $target = '[name=footer_address_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                    </div>
                    <textarea name="footer_address_bn" rows="2" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"><?= e($footer['address_bn']) ?></textarea>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-sm font-bold text-stone-800 mb-3"><?= e(trans('admin_copyright')) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_en')) ?></label>
                    <input type="text" name="copyright_en" value="<?= e($footer['copyright_en']) ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_lang_bn')) ?></label>
                    <input type="text" name="copyright_bn" value="<?= e($footer['copyright_bn']) ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
            </div>
            <p class="text-xs text-stone-400 mt-2"><?= e(trans('admin_copyright_hint')) ?></p>
        </div>

        <div>
            <h2 class="text-sm font-bold text-stone-800 mb-1"><?= e(trans('admin_footer_quick_links')) ?></h2>
            <p class="text-xs text-stone-400 mb-3"><?= e(trans('admin_footer_quick_links_desc')) ?></p>
            <div class="space-y-4" id="quick-links">
                <?php $existingQuickLinks = footer_quick_links(); ?>
                <?php foreach ($existingQuickLinks as $qlKey => $ql): $qlNum = (int)$qlKey + 1; ?>
                <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
                        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_ql_link')) ?> <?= $qlNum ?></span>
                        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
                            <input type="checkbox" name="quick_links[<?= $qlKey ?>][enabled]" value="1"
                                   class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500" <?= !empty($ql['enabled']) ? 'checked' : '' ?>>
                            <?= e(trans('admin_ql_enabled')) ?>
                        </label>
                        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
                            <input type="checkbox" name="quick_links_remove[<?= $qlKey ?>]" value="1"
                                   class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
                            <?= e(trans('admin_ql_remove')) ?>
                        </label>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (EN)</label>
                            <input type="text" name="quick_links[<?= $qlKey ?>][label_en]" value="<?= e($ql['label_en'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_ql_label_en_placeholder')) ?>" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (BN)</label>
                            <input type="text" name="quick_links[<?= $qlKey ?>][label_bn]" value="<?= e($ql['label_bn'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_ql_label_bn_placeholder')) ?>" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_url')) ?></label>
                            <input type="text" name="quick_links[<?= $qlKey ?>][url]" value="<?= e($ql['url'] ?? '') ?>"
                                   placeholder="<?= e(trans('admin_ql_url_hint')) ?>" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-quick-link"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-500 hover:border-brand-500 hover:text-brand-600 transition">
                <span class="text-lg leading-none">+</span> <?= e(trans('admin_footer_add_link')) ?>
            </button>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
        </div>
    </form>
</div>

<?php ob_start(); ?>
<div class="rounded-xl border border-stone-200 bg-stone-50/50 p-4">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3">
        <span class="text-xs font-bold text-stone-500 uppercase tracking-wide"><?= e(trans('admin_ql_link')) ?> {{KEYNUM}}</span>
        <label class="flex items-center gap-2 text-xs font-medium text-stone-600">
            <input type="checkbox" name="quick_links[{{KEY}}][enabled]" value="1" checked class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
            <?= e(trans('admin_ql_enabled')) ?>
        </label>
        <label class="flex items-center gap-2 text-xs font-medium text-red-500 ml-auto">
            <input type="checkbox" name="quick_links_remove[{{KEY}}]" value="1" class="h-4 w-4 rounded border-red-300 text-red-500 focus:ring-red-400">
            <?= e(trans('admin_ql_remove')) ?>
        </label>
    </div>
    <div class="grid sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (EN)</label>
            <input type="text" name="quick_links[{{KEY}}][label_en]" value="" placeholder="<?= e(trans('admin_ql_label_en_placeholder')) ?>" class="ql-label-en w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_label')) ?> (BN)</label>
            <input type="text" name="quick_links[{{KEY}}][label_bn]" value="" placeholder="<?= e(trans('admin_ql_label_bn_placeholder')) ?>" class="ql-label-bn w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-stone-500 mb-1"><?= e(trans('admin_ql_url')) ?></label>
            <input type="text" name="quick_links[{{KEY}}][url]" value="" placeholder="<?= e(trans('admin_ql_url_hint')) ?>" class="w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
        </div>
    </div>
</div>
<?php $quickLinkTemplate = ob_get_clean(); ?>

<script>
(function () {
    const container = document.getElementById('quick-links');
    const addBtn = document.getElementById('add-quick-link');
    const tpl = <?= json_encode($quickLinkTemplate) ?>;

    function addRow() {
        const key = container.children.length;
        const html = tpl.replaceAll('{{KEY}}', String(key)).replaceAll('{{KEYNUM}}', String(key + 1));
        container.insertAdjacentHTML('beforeend', html);
    }

    if (addBtn) {
        addBtn.addEventListener('click', addRow);
    }
})();
</script>

<?php
$isEdit = $page !== null;
$p = $page ?? [];
$formAction = $isEdit
    ? locale_url('/mf-dashboard/pages/' . $p['id'] . '/update')
    : locale_url('/mf-dashboard/pages');
?>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow { border-color: #e7e5e4; border-radius: .9rem .9rem 0 0; }
    .ql-container.ql-snow { border-color: #e7e5e4; border-radius: 0 0 .9rem .9rem; font-size: .9rem; }
    .ql-editor { min-height: 170px; }
    .ql-snow .ql-editor ul { list-style: disc; padding-left: 1.5em; }
    .ql-snow .ql-editor ol { list-style: decimal; padding-left: 1.5em; }
</style>
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-extrabold text-stone-900"><?= $isEdit ? e(trans('admin_edit_page')) : e(trans('admin_add_page')) ?></h1>
        <a href="<?= e(locale_url('/mf-dashboard/pages')) ?>" class="text-sm font-semibold text-brand-700 hover:underline">← <?= e(trans('admin_back')) ?></a>
    </div>

    <?php if ($isEdit): ?>
    <p class="mb-4 text-sm text-stone-500">
        URL: <a href="<?= e(locale_url('/pages/' . $p['slug'])) ?>" target="_blank" class="font-mono text-brand-700 hover:underline"><?= e(locale_url('/pages/' . $p['slug'])) ?></a>
    </p>
    <?php endif; ?>

    <form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" id="pageForm" class="bg-white rounded-2xl border border-stone-200 p-6 space-y-4">
        <?= csrf_field() ?>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_title_en')) ?> *</label>
                </div>
                <input type="text" name="title_en" required value="<?= e($p['title_en'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_title_bn')) ?> *</label>
                    <?php $source = '[name=title_en]'; $target = '[name=title_bn]'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <input type="text" name="title_bn" required value="<?= e($p['title_bn'] ?? '') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_slug')) ?></label>
                <input type="text" name="slug" value="<?= e($p['slug'] ?? '') ?>" placeholder="auto" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_menu_order')) ?> <span class="font-normal text-stone-400"><?= e(trans('admin_menu_order_hint')) ?></span></label>
                <input type="number" name="menu_order" min="0" value="<?= e($p['menu_order'] ?? '0') ?>" class="mt-1 w-full rounded-xl border border-stone-200 px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_content_en')) ?></label>
                <textarea name="content_en" id="contentEnInput" class="hidden"><?= e($p['content_en'] ?? '') ?></textarea>
                <div id="editor-en" class="mt-1"></div>
            </div>
            <div>
                <div class="flex items-center">
                    <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_content_bn')) ?></label>
                    <?php $source = '#editor-en'; $target = '#editor-bn'; include views_path('admin/partials/wsit_translate_btn.php'); ?>
                </div>
                <textarea name="content_bn" id="contentBnInput" class="hidden"><?= e($p['content_bn'] ?? '') ?></textarea>
                <div id="editor-bn" class="mt-1"></div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-6">
            <label class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <input type="checkbox" name="active" value="1" class="accent-brand-600" <?= $isEdit && (int)$p['active'] === 0 ? '' : 'checked' ?>>
                <?= e(trans('admin_active')) ?>
            </label>
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-stone-500"><?= e(trans('admin_page_image')) ?></label>
                <input type="file" name="image" accept="image/*" class="text-xs">
                <?php if ($isEdit && !empty($p['image'])): ?>
                <img src="<?= e(image_url($p['image'])) ?>" alt="" class="h-10 w-16 rounded-lg object-cover border border-stone-200">
                <?php endif; ?>
            </div>
        </div>
        <p class="text-xs text-stone-400 -mt-2"><?= e(trans('admin_page_image_hint')) ?></p>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 text-white px-6 py-3 text-sm font-bold"><?= e(trans('admin_save')) ?></button>
            <a href="<?= e(locale_url('/mf-dashboard/pages')) ?>" class="rounded-xl border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-600 hover:border-stone-400"><?= e(trans('admin_cancel')) ?></a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.js"></script>
<script>
    (function () {
        const enInput = document.getElementById('contentEnInput');
        const bnInput = document.getElementById('contentBnInput');
        const toolbar = [
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'header': [1, 2, 3, false] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['blockquote', 'code-block', 'link'],
            ['clean']
        ];
        const qEn = new Quill('#editor-en', { theme: 'snow', modules: { toolbar: toolbar } });
        const qBn = new Quill('#editor-bn', { theme: 'snow', modules: { toolbar: toolbar } });
        if (enInput.value) qEn.clipboard.dangerouslyPasteHTML(enInput.value);
        if (bnInput.value) qBn.clipboard.dangerouslyPasteHTML(bnInput.value);

        document.getElementById('pageForm').addEventListener('submit', function () {
            enInput.value = qEn.root.innerHTML;
            bnInput.value = qBn.root.innerHTML;
        });
    })();
</script>
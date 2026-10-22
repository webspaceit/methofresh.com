<?php
// User create/edit form — Material admin (locale-aware via trans()).
$user    = $user ?? null;
$isEdit  = $user !== null;
$action  = $isEdit
    ? locale_url('/mf-dashboard/users/' . (int)$user['id'] . '/edit')
    : locale_url('/mf-dashboard/users/create');

$fName   = (string)($user['name'] ?? '');
$fEmail  = (string)($user['email'] ?? '');
$fPhone  = (string)($user['phone'] ?? '');
$fCity   = (string)($user['city'] ?? '');
$fAdmin  = (int)($user['is_admin'] ?? 0) === 1;
$fAuto   = (int)($user['auto_registered'] ?? 0) === 1;
?>
<div class="flex flex-col gap-space-lg max-w-2xl">
<!-- Page header -->
<section class="flex items-center gap-space-md p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm border-b-4 border-primary-container">
    <div class="w-12 h-12 rounded-2xl bg-secondary-fixed/50 flex items-center justify-center text-secondary shrink-0">
        <span class="material-symbols-outlined text-[26px]"><?= $isEdit ? 'badge' : 'person_add' ?></span>
    </div>
    <div class="flex-1">
        <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold tracking-tight"><?= e($isEdit ? trans('admin_user_edit') : trans('admin_user_create')) ?></h1>
        <p class="font-body-md text-body-md text-on-surface-variant"><?= e($isEdit ? trans('admin_user_edit_sub') : trans('admin_user_create_sub')) ?></p>
    </div>
    <a href="<?= e(locale_url('/mf-dashboard/users')) ?>"
       class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span><?= e(trans('admin_back')) ?>
    </a>
</section>

<form method="post" action="<?= e($action) ?>" class="p-space-lg rounded-2xl bg-surface-container-lowest shadow-sm flex flex-col gap-space-md">
    <?= csrf_field() ?>

    <?php if ($fAuto): ?>
    <!-- The account was created automatically when this customer ordered, so
         it has no password of their own yet. -->
    <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
        <span class="material-symbols-outlined text-[20px] text-amber-700 shrink-0">key</span>
        <p class="text-sm text-amber-900 leading-relaxed"><?= e(trans('admin_user_auto_password_hint')) ?></p>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-space-md">
        <label class="flex flex-col gap-1.5">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e(trans('admin_user_name')) ?> *</span>
            <input type="text" name="name" value="<?= e($fName) ?>" required
                   class="rounded-xl border border-surface-container-high bg-surface px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
        </label>
        <label class="flex flex-col gap-1.5">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e(trans('admin_user_email')) ?> *</span>
            <input type="email" name="email" value="<?= e($fEmail) ?>" required
                   class="rounded-xl border border-surface-container-high bg-surface px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
        </label>
        <label class="flex flex-col gap-1.5">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e(trans('admin_user_phone')) ?></span>
            <input type="text" name="phone" value="<?= e($fPhone) ?>"
                   class="rounded-xl border border-surface-container-high bg-surface px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
        </label>
        <label class="flex flex-col gap-1.5">
            <span class="font-label-md text-label-md text-on-surface font-semibold"><?= e(trans('admin_user_city')) ?></span>
            <input type="text" name="city" value="<?= e($fCity) ?>"
                   class="rounded-xl border border-surface-container-high bg-surface px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
        </label>
    </div>

    <label class="flex flex-col gap-1.5">
        <span class="font-label-md text-label-md text-on-surface font-semibold">
            <?= e(trans('admin_user_password')) ?> <?= $isEdit ? '' : '*' ?>
        </span>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password"
               placeholder="<?= e($isEdit ? trans('admin_user_password_keep') : trans('admin_user_password_min')) ?>"
               minlength="6"
               class="rounded-xl border border-surface-container-high bg-surface px-4 py-2.5 font-body-md text-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40">
        <?php if (!$isEdit): ?>
        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_user_password_min')) ?></span>
        <?php endif; ?>
    </label>

    <label class="flex items-start gap-3 p-space-md rounded-xl bg-surface-container-low border border-surface-container-high cursor-pointer">
        <input type="checkbox" name="is_admin" value="1" <?= $fAdmin ? 'checked' : '' ?>
               class="mt-0.5 w-5 h-5 rounded accent-primary shrink-0">
        <span>
            <span class="block font-label-md text-label-md text-on-surface font-semibold"><?= e(trans('admin_user_admin_label')) ?></span>
            <span class="block font-body-sm text-body-sm text-on-surface-variant"><?= e(trans('admin_user_admin_hint')) ?></span>
        </span>
    </label>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-label-md font-bold hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-[18px]">save</span><?= e($isEdit ? trans('admin_save') : trans('admin_create')) ?>
        </button>
        <a href="<?= e(locale_url('/mf-dashboard/users')) ?>"
           class="inline-flex items-center gap-1.5 px-space-md py-2.5 rounded-xl bg-surface-container-low text-on-surface-variant font-label-md text-label-md font-semibold hover:bg-surface-container-high transition-colors">
            <?= e(trans('admin_cancel')) ?>
        </a>
    </div>
</form>
</div>

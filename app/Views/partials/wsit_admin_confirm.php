<div id="admin-confirm-modal" data-default-confirm="<?= e(trans('admin_delete_confirm')) ?>"
     class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/55 p-4"
     role="presentation" aria-hidden="true">
    <section class="w-full max-w-md rounded-lg bg-white px-6 py-7 text-center shadow-2xl sm:px-8"
             role="alertdialog" aria-modal="true" aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-message">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full border-[3px] border-amber-300 text-3xl font-light text-amber-500">!</div>
        <h2 id="admin-confirm-title" class="mb-2 text-xl font-semibold text-slate-700"><?= $locale === 'bn' ? 'আপনি কি নিশ্চিত?' : 'Are you sure?' ?></h2>
        <p id="admin-confirm-message" class="text-sm leading-6 text-slate-600"></p>
        <div class="mt-6 flex justify-center gap-3">
            <button id="admin-confirm-cancel" type="button" class="rounded-full bg-slate-100 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">
                <?= $locale === 'bn' ? 'বাতিল' : 'Cancel' ?>
            </button>
            <button id="admin-confirm-ok" type="button" class="rounded-full bg-red-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                <?= $locale === 'bn' ? 'হ্যাঁ, করুন' : 'Continue' ?>
            </button>
        </div>
    </section>
</div>
<script>
(function () {
    var modal = document.getElementById('admin-confirm-modal');
    var message = document.getElementById('admin-confirm-message');
    var cancel = document.getElementById('admin-confirm-cancel');
    var confirmButton = document.getElementById('admin-confirm-ok');
    var pendingForm = null;
    var pendingSubmitter = null;
    var pendingAction = null;

    function closeDialog() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        pendingForm = null;
        pendingSubmitter = null;
        pendingAction = null;
    }

    function openDialog(prompt) {
        message.textContent = prompt || modal.getAttribute('data-default-confirm') || '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        confirmButton.focus();
    }

    document.addEventListener('click', function (event) {
        var action = event.target.closest('[data-confirm-click]');
        if (!action || action.dataset.confirmed === 'true') return;
        event.preventDefault();
        event.stopImmediatePropagation();
        pendingAction = function () {
            action.dataset.confirmed = 'true';
            action.click();
        };
        openDialog(action.getAttribute('data-confirm-click'));
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.confirmed === 'true') return;
        var prompt = event.submitter && event.submitter.getAttribute('data-confirm');
        prompt = prompt || form.getAttribute('data-confirm');
        if (!prompt) {
            var checkedDestructiveOption = form.querySelector('input[type="checkbox"]:checked[name*="remove"], input[type="checkbox"]:checked[name*="delete"]');
            if (checkedDestructiveOption) prompt = modal.getAttribute('data-default-confirm');
        }
        if (!prompt) return;
        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter || null;
        openDialog(prompt);
    }, true);

    cancel.addEventListener('click', closeDialog);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeDialog();
    });
    confirmButton.addEventListener('click', function () {
        if (pendingAction) {
            var action = pendingAction;
            closeDialog();
            action();
            return;
        }
        if (!pendingForm) return;
        var form = pendingForm;
        var submitter = pendingSubmitter;
        form.dataset.confirmed = 'true';
        closeDialog();
        if (form.requestSubmit) form.requestSubmit(submitter || undefined);
        else form.submit();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeDialog();
    });
})();
</script>

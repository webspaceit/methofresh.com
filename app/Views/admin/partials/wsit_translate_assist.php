<?php
/**
 * Reusable EN→BN "Translate" helper for admin bilingual forms.
 * Buttons call window.bnTranslate(this, sourceSelector, targetSelector).
 * targetSelector may be a regular field or a Quill editor container.
 */
?>
<script>
    window.bnTranslate = async function (btn, srcSel, tgtSel) {
        const srcEl = document.querySelector(srcSel);
        const tgtEl = document.querySelector(tgtSel);
        if (!srcEl || !tgtEl) return;

        const qSrc = window.Quill && Quill.find(srcEl);
        const text = String(qSrc ? qSrc.root.innerText : (srcEl.value || '')).trim();
        if (!text) {
            alert(<?= json_encode(trans('admin_translate_empty')) ?>);
            return;
        }

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '…';

        try {
            const resp = await fetch(<?= json_encode(locale_url('/mf-dashboard/translate')) ?>, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: '_token=' + encodeURIComponent(<?= json_encode(csrf_token()) ?>) +
                      '&target=bn&text=' + encodeURIComponent(text)
            });
            const data = await resp.json();
            if (!data.ok) {
                alert(data.error || <?= json_encode(trans('admin_translate_failed')) ?>);
                return;
            }

            const qTgt = window.Quill && Quill.find(tgtEl);
            if (qTgt) {
                const paras = data.translated
                    .split(/\n+/)
                    .map(function (s) { return s.trim(); })
                    .filter(Boolean)
                    .map(function (s) {
                        return '<p>' + s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p>';
                    })
                    .join('');
                qTgt.clipboard.dangerouslyPasteHTML(paras || '<p></p>');
            } else {
                tgtEl.value = data.translated;
            }
        } catch (err) {
            alert(<?= json_encode(trans('admin_translate_failed')) ?>);
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    };
</script>
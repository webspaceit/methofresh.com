<?php
// Material Symbols -> inline SVG engine.
//
// The implementation is ~26 KB and used to be shipped inline on every page.
// It now lives in a static, cacheable file: public/assets/js/icons.js
// (regenerate with `npm run vendor`). This partial just references it so both
// the storefront and dashboard layouts keep working unchanged.
?>
<script src="<?= e(asset_url('js/icons.js')) ?>" defer></script>

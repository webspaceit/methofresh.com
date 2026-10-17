<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(trans('tagline')) ?>">
<?php $favicon = site_favicon_url(); ?>
<?php include views_path('partials/wsit_pixel.php'); ?>
<?php if ($favicon !== ''): ?>
<link rel="icon" href="<?= e($favicon) ?>">
<?php else: ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍯</text></svg>">
<?php endif; ?>
<?php
// Compiled storefront stylesheet: Tailwind utilities + self-hosted fonts
// (Hind Siliguri / Noto Sans Bengali). Replaces the Tailwind Play CDN.
// Rebuild with `npm run build:css` after changing storefront markup.
?>
<link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
<?php
// Tiny per-request config for the static assets/js/app.js bundle. Everything
// else on the page is now cacheable.
?>
<script>window.MF_CONFIG = <?= json_encode([
    'csrf'          => csrf_token(),
    'cartAdd'       => locale_url('/cart/add'),
    'cartItems'     => locale_url('/cart/items'),
    'cartUpdate'    => locale_url('/cart/update'),
    'cartRemove'    => locale_url('/cart/remove'),
    'cartClear'     => locale_url('/cart/clear'),
    'searchSuggest' => locale_url('/search/suggest'),
    'searchResults' => locale_url('/products'),
    'searchLog'     => locale_url('/search/log'),
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>

<?php
$pageTitle = $pageTitle ?? app_name();
$currentUser = current_user();
$cartCount = guest_cart_count();
$locale = app_locale();
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<?php include views_path('partials/wsit_head.php'); ?>
<body class="flex flex-col min-h-screen bg-cream text-brand-900 font-sans" x-data="store({ cartCount: <?= (int)$cartCount ?>, itemsLabel: '<?= e(trans('cart_items_count')) ?>', message: '' })">

    <?php include views_path('partials/wsit_header.php'); ?>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <?php include views_path('partials/wsit_footer.php'); ?>
    <?php include views_path('partials/wsit_cart-drawer.php'); ?>
    <?php include views_path('partials/wsit_flash.php'); ?>

    <?php // app.js must be requested BEFORE alpine.min.js (both `defer`, so they
          // execute in document order): it registers the `alpine:init` listener
          // that defines the store/liveSearch Alpine components. ?>
    <script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
    <script src="<?= e(asset_url('js/alpine.min.js')) ?>" defer></script>
    <?php include views_path('partials/wsit_icons.php'); ?>
</body>
</html>
<?php
$pageTitle = $pageTitle ?? app_name();
$currentUser = current_user();
$cartCount = guest_cart_count();
$locale = app_locale();
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<?php include views_path('partials/wsit_head.php'); ?>
<body class="flex flex-col min-h-screen bg-stone-50 text-stone-900 font-sans" x-data="store({ cartCount: <?= (int)$cartCount ?>, message: '' })">

    <?php include views_path('partials/wsit_header.php'); ?>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <?php include views_path('partials/wsit_footer.php'); ?>
    <?php include views_path('partials/wsit_flash.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
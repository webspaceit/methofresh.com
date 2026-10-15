<?php
require __DIR__ . '/app/wsit_bootstrap.php';

$db = \App\Core\wsit_Database::connect(db_config());
$setting = new \App\Models\wsit_Setting();

// Check current payment_methods value
$current = $setting->get('payment_methods', '');
echo "Current payment_methods value:\n";
echo $current ?: "(empty/not set)\n\n";

if ($current) {
    $decoded = json_decode($current, true);
    echo "Decoded value:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Clear the setting to use defaults
    $setting->set('payment_methods', '');
    echo "Cleared payment_methods setting. Will now use defaults with SVG icons.\n";
} else {
    echo "No custom payment_methods setting found. Using defaults.\n";
}

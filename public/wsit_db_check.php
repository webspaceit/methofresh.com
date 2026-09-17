<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/wsit_helpers.php';
use App\Core\wsit_Database;
$config = require __DIR__ . '/../app/config/wsit_config.php';
$db = wsit_Database::connect($config['database']);

$out = "=== PRODUCTS ===\n";
$rows = $db->fetchAll('SELECT id, name_en, image FROM ' . table('products') . ' ORDER BY id');
foreach ($rows as $r) {
    $out .= $r['id'] . ' | ' . $r['name_en'] . ' | ' . $r['image'] . "\n";
}
$out .= "Total products: " . count($rows) . "\n";
$out .= "\n=== CATEGORIES ===\n";
$rows = $db->fetchAll('SELECT id, name_en, image FROM ' . table('categories') . ' ORDER BY id');
foreach ($rows as $r) {
    $out .= $r['id'] . ' | ' . $r['name_en'] . ' | ' . $r['image'] . "\n";
}
$out .= "Total categories: " . count($rows) . "\n";
file_put_contents('C:/Users/sizub/AppData/Local/Temp/kilo/db_check.txt', $out);

<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/wsit_helpers.php';

use App\Core\wsit_Database;

$config = require __DIR__ . '/../app/config/wsit_config.php';
$db = wsit_Database::connect($config['database']);

$products = $db->fetchAll('SELECT id, name_en, image FROM ' . table('products') . ' ORDER BY id');
foreach ($products as $p) {
    echo $p['id'] . ' | ' . $p['name_en'] . ' | ' . $p['image'] . "\n";
}
echo "Total: " . count($products) . "\n";
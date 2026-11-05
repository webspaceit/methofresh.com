<?php
// Probe EOF region of the helpers file after checkout: total lines, the
// inserted anchor's index, and whether helpers the front renderer needs are
// defined. Also confirm the exact line of:
//   function hero_carousel_height_clamped (pristine original next function)
$path = 'E:\\wamp64\\www\\methofresh.com\\app\\wsit_helpers.php';
$src  = file_get_contents($path);
$lines = explode("\n", $src);
$total = count($lines nothing);
$anchorIdx = null;
$heightIdx = null;
foreach ($lines as $i => $l) {
    $t = ltrim($l);
    if ($anchorIdx === null && str_starts_with($t, '// __MF_SECTION_STORE_ANCHOR__')) {
        $anchorIdx = $i;
    }
    if (str_starts_with($t, 'function hero_carousel_height_clamped(')) {
        $heightIdx = $i;
    }
}
echo "total   = $total\n";
echo "anchor  = " . ($anchorIdx === null ? 'NOT FOUND' : (string)($anchorIdx + 1)) . "\n";
echo "height  = " . ($heightIdx === null ? 'NOT FOUND' : (string)($heightIdx + 1)) . "\n";

// What marker products? Search name of the register/route bootstrap include.
foreach (['site_setting', 'site_setting_raw', 'trans_locale', 'app_locale', 'home_blocks'] as $fn) {
    $present = false;
    foreach ($lines as $l) {
        if (str_starts_with(ltrim($l), 'function ' . $fn . '(')) {
            $present = true;
            break;
        }
    }
    echo sprintf("%-16s %s\n", $fn, $present ? 'yes' : 'NO');
}

<?php
// STEP 1 — place a unique, verifiable anchor at EOF of the pristine helpers
// file so the final block has a guaranteed, order-independent insertion point.
$path = 'E:\\wamp64\\www\\methofresh.com\\app\\wsit_helpers.php';
$src  = file_get_contents($path);
if ($src === false) {
    fwrite(STDERR, "cannot read $path\n");
    exit(1);
}
if (str_contains($src, '// __MF_SECTION_STORE_ANCHOR__')) {
    echo "anchor already present (idempotent), skipping\n";
    exit(0);
}
$src .= "\n\n// __MF_SECTION_STORE_ANCHOR__\n";
file_put_contents($path, $src . PHP_EOL);
echo "anchor placed.\n";

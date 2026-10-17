/**
 * Vendors the storefront's third-party / extracted JS into public/assets/js/.
 *
 *   node resources/build/vendor-assets.mjs
 *
 * 1. Extracts the inline icon engine (app/Views/partials/wsit_icons.php) into a
 *    static, cacheable file — it used to ship inline on every page (~26 KB).
 * 2. Downloads a PINNED Alpine.js build (was an unpinned "@3.x.x" jsdelivr URL,
 *    which cannot be long-cached and can change without warning).
 *
 * Outputs are committed, so this only needs re-running when the version changes.
 */
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const ALPINE_VERSION = '3.17.4';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const jsDir = path.join(root, 'public', 'assets', 'js');
await mkdir(jsDir, { recursive: true });

// 1) Icon engine -----------------------------------------------------------
const iconsSrc = await readFile(path.join(root, 'app', 'Views', 'partials', 'wsit_icons.php'), 'utf8');
if (!/^\s*<script>/i.test(iconsSrc)) {
    console.error('wsit_icons.php no longer starts with <script> — aborting.');
    process.exit(1);
}
const iconsJs = iconsSrc.replace(/^\s*<script>\s*/i, '').replace(/\s*<\/script>\s*$/i, '') + '\n';
await writeFile(path.join(jsDir, 'icons.js'), iconsJs);

// 2) Alpine.js -------------------------------------------------------------
const url = `https://cdn.jsdelivr.net/npm/alpinejs@${ALPINE_VERSION}/dist/cdn.min.js`;
const res = await fetch(url);
if (!res.ok) {
    console.error(`Failed to download Alpine.js (${res.status} ${url})`);
    process.exit(1);
}
const alpine = await res.text();
await writeFile(path.join(jsDir, 'alpine.min.js'), alpine);

console.log(`icons.js      ${iconsJs.length.toLocaleString()} bytes`);
console.log(`alpine.min.js ${alpine.length.toLocaleString()} bytes (v${ALPINE_VERSION})`);

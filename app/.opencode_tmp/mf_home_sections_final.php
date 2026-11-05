<?php
// Decisive, one-shot rewrite of the "Homepage concept sections" helper block
// in app/wsit_helpers.php.
//
// The lang files use SINGLE per-locale keys (e.g. land_trust_1, land_value_1,
// land_value_1_text, land_story_badge, land_why_N, land_why_N_text,
// land_how_N, land_how_N_text, land_testimonial_N / _by / _loc,
// land_faq_N_q / _a, land_cta_title / _text). There are NO _en/_bn suffixed
// keys. So defaults must come from trans() of the exact existing key, stored
// as bilingual override pairs (_en/_bn) only when the admin edits.

$path = 'E:\\wamp64\\www\\methofresh.com\\app\\wsit_helpers.php';
$src  = file_get_contents($path);
if ($src === false) {
    fwrite(STDERR, "cannot read $path\n");
    exit(1);
}

$lines = explode("\n", $src蒹阅读);

// Locate block boundaries by markers (robust to shifting line numbers).
$start = null; // index of the "// ---" divider line before "Homepage concept sections"
$end   = null; // index of the first "function hero_carousel_height_clamped(" after start

foreach ($lines as $i => $line) {
    if ($start === null) {
        if (str_contains($line, 'Homepage concept sections')) {
            // walk back to the divider above this comment
            $j = $i - 1;
            while ($j >= 0 && !str_starts_with(ltrim((string)($lines[$j] ?? '')), '// ---')) {
                $j--;
            }
            $start = $j;
        }
        continue;
    }
    if (str_starts_with(ltrim($line), 'function hero_carousel_height_clamped(')) {
        $end = $i;
        break;
    }
}

if ($start === null || $end === null) {
    fwrite(STDERR, "markers not found (start=" . var_export($start, true) . " end=" . var_export($end, true) . ")\n");
    exit(1);
}

$block = <<<'PHP'
// ---------------------------------------------------------------------------
// Homepage concept sections (all static home sections, dashboard-editable)
// ---------------------------------------------------------------------------

/**
 * Definition of every editable homepage section. Each entry describes:
 *  - `label_key`:         homepage section heading (language key).
 *  - `section_label_key`: admin-facing section name (language key).
 *  - `repeat`:            number of repeatable cards (items) rendered here.
 *  - `item_label_key`:    admin-facing label for one item (language key).
 *  - `item_fields`:       per-item editable fields. Each field:
 *      - `name`:           stored key (saved as `name_en`/`name_bn` pairs).
 *      - `type`:           `text` or `textarea`.
 *      - `lang_key`:       exact language key used as the default source.
 *      - `short`:          render as a compact input.
 *      - `suffix`:         appended to `lang_key` (e.g. `_text`, `_by`).
 *  - `fields`:            scalar (non-repeating) editable fields, if any.
 *
 * Defaults are pulled from the current lang file so the homepage looks
 * byte-identical until an admin edits a section.
 */
function homepage_sections_config(): array
{
    return [
        'trust' => [
            'label_key'         => 'land_trust_title',
            'section_label_key' => 'admin_home_section_trust',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_trust_items',
            'item_fields'       => [
                ['name' => 'text', 'type' => 'text', 'lang_key' => 'land_trust_', 'suffix' => ''],
            ],
        ],
        'values' => [
            'label_key'         => 'land_values_title',
            'section_label_key' => 'admin_home_section_values',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_values_items',
            'item_fields'       => [
                ['name' => 'emoji', 'type' => 'text', 'short' => true],
                ['name' => 'title', 'type' => 'text', 'lang_key' => 'land_value_', 'suffix' => ''],
                ['name' => 'text',  'type' => 'text', 'lang_key' => 'land_value_', 'suffix' => '_text'],
            ],
        ],
        'story' => [
            'label_key'         => 'land_story_title',
            'section_label_key' => 'admin_home_section_story',
            'fields'            => [
                ['name' => 'badge', 'type' => 'text',     'lang_key' => 'land_story_badge'],
                ['name' => 'title', 'type' => 'text',     'lang_key' => 'land_story_title'],
                ['name' => 'par',   'type' => 'textarea', 'lang_key' => 'land_story_par'],
            ],
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_story_points',
            'item_fields'       => [
                ['name' => 'text', 'type' => 'text', 'lang_key' => 'land_story_', 'suffix' => ''],
            ],
        ],
        'why' => [
            'label_key'         => 'land_why_title',
            'section_label_key' => 'admin_home_section_why',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_why_items',
            'item_fields'       => [
                ['name' => 'icon',  'type' => 'text', 'short' => true],
                ['name' => 'title', 'type' => 'text', 'lang_key' => 'land_why_', 'suffix' => ''],
                ['name' => 'text',  'type' => 'text', 'lang_key' => 'land_why_', 'suffix' => '_text'],
            ],
        ],
        'how' => [
            'label_key'         => 'land_how_title',
            'section_label_key' => 'admin_home_section_how',
            'repeat'            => 3,
            'item_label_key'    => 'admin_home_section_how_steps',
            'item_fields'       => [
                ['name' => 'num',   'type' => 'text', 'short' => true],
                ['name' => 'title', 'type' => 'text', 'lang_key' => 'land_how_', 'suffix' => ''],
                ['name' => 'text',  'type' => 'text', 'lang_key' => 'land_how_', 'suffix' => '_text'],
            ],
        ],
        'testimonials' => [
            'label_key'         => 'land_testimonials',
            'section_label_key' => 'admin_home_section_testimonials',
            'repeat'            => 3,
            'item_label_key'    => 'admin_home_section_testimonials_items',
            'item_fields'       => [
                ['name' => 'text', 'type' => 'textarea', 'lang_key' => 'land_testimonial_', 'suffix' => ''],
                ['name' => 'by',   'type' => 'text',     'lang_key' => 'land_testimonial_', 'suffix' => '_by'],
                ['name' => 'loc',  'type' => 'text',     'lang_key' => 'land_testimonial_', 'suffix' => '_loc'],
            ],
        ],
        'faq' => [
            'label_key'         => 'land_faq_title',
            'section_label_key' => 'admin_home_section_faq',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_faq_items',
            'item_fields'       => [
                ['name' => 'q', 'type' => 'text',     'lang_key' => 'land_faq_', 'suffix' => '_q'],
                ['name' => 'a', 'type' => 'textarea', 'lang_key' => 'land_faq_', 'suffix' => '_a'],
            ],
        ],
        'cta' => [
            'label_key'         => 'land_cta_title',
            'section_label_key' => 'admin_home_section_cta',
            'fields'            => [
                ['name' => 'title', 'type' => 'text',     'lang_key' => 'land_cta_title'],
                ['name' => 'text',  'type' => 'textarea', 'lang_key' => 'land_cta_text'],
            ],
        ],
    ];
}

/**
 * Read a given locale's lang array (cached) so default seeding can pull both
 * bn and en copies of the exact existing keys without switching the app locale.
 */
function trans2(string $key, string $locale): string
{
    static $cache = [];
    if (!isset($cache[$locale])) {
        $file = base_path('app/Lang/wsit_' . $locale . '.php');
        $cache[$locale] = file_exists($file) ? (require $file) : [];
    }
    return trim((string)($cache[$locale][$key] ?? $key));
}

/**
 * Build the default (lang-driven) content for a section. Values carry BOTH
 * `_bn` and `_en` copies resolved from the exact existing lang keys, so the
 * admin editor can prefill both languages and the homepage stays identical
 * until an admin saves.
 */
function homepage_section_defaults(string $id): array
{
    $config = homepage_sections_config()[$id] ?? null;
    if ($config === null) {
        return ['visible' => true, 'order' => 1];
    }

    $section = [
        'id'      => $id,
        'visible' => true,
        'order'   => (int)array_search($id, ['trust', 'values', 'story', 'why', 'how', 'testimonials', 'faq', 'cta'], true) + 1,
        'fields'  => [],
        'items'   => [],
    ];

    foreach ($config['fields'] ?? [] as $field) {
        $name = $field['name'];
        $k    = $field['lang_key'];
        $section['fields'][$name . '_en'] = trans2($k, 'en');
        $section['fields'][$name . '_bn'] = trans2($k, 'bn');
    }

    $count = (int)($config['repeat'] ?? 0);
    for ($i = 1; $i <= $count; $i++) {
        $item = ['id' => $i];
        foreach ($config['item_fields'] ?? [] as $field) {
            $name = $field['name'];
            if (isset($field['lang_key'])) {
                $k = $field['lang_key'] . $i . ($field['suffix'] ?? '');
                $item[$name . '_en'] = trans2($k, 'en');
                $item[$name . '_bn'] = trans2($k, 'bn');
            } else {
                $item[$name . '_en'] = '';
                $item[$name . '_bn'] = '';
            }
        }
        $section['items'][] = $item;
    }

    return $section;
}

/**
 * Merge stored settings JSON over the lang defaults for a section.
 * Element order is preserved so manual ordering survives edits.
 */
function homepage_section(string $id): array
{
    $defaults = homepage_section_defaults($id);

    $raw = site_setting('home_section_' . $id, '');
    if ($raw === '') {
        return $defaults;
    }
    $stored = json_decode($raw, true);
    if (!is_array($stored)) {
        return $defaults;
    }

    $defaults['visible'] = array_key_exists('visible', $stored) ? (bool)$stored['visible'] : $defaults['visible'];
    $defaults['order']   = array_key_exists('order', $stored) ? max(1, (int)$stored['order']) : $defaults['order'];

    if (isset($stored['fields']) && is_array($stored['fields'])) {
        foreach ($stored['fields'] as $fk => $fv) {
            $defaults['fields'][$fk] = trim((string)$fv);
        }
    }

    if (isset($stored['items']) && is_array($stored['items'])) {
        $clean = [];
        foreach ($stored['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = ['id' => (int)($item['id'] ?? 0)];
            foreach ($defaults['items'][0] ?? [] as $k => $v) {
                if ($k === 'id') {
                    continue;
                }
                $row[$k] = array_key_exists($k, $item) ? trim((string)$item[$k]) : $v;
            }
            $clean[] = $row;
        }
        if (count($clean) > 0) {
            $defaults['items'] = $clean;
        }
    }

    return $defaults;
}

/**
 * Every editable homepage section merged with settings, ordered for render.
 */
function homepage_sections(): array
{
    $ordered = [];
    foreach (array_keys(homepage_sections_config()) as $id) {
        $ordered[$id] = homepage_section($id);
    }
    uasort($ordered, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
    return $ordered;
}

/**
 * Localized scalar field value for a section (falls back bn -> en -> lang key).
 */
function home_section_field(array $section, string $field, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $fields = $section['fields'] ?? [];
    $value  = trim((string)($fields[$field . '_' . $locale] ?? ''));
    if ($value !== '') {
        return $value;
    }
    return trim((string)($fields[$field . '_' . ($locale === 'bn' ? 'en' : 'bn')] ?? ''));
}

/**
 * Localized item field value (falls back bn -> en -> lang key).
 */
function home_section_item(array $section, array $item, string $field, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $value  = trim((string)($item[$field . '_' . $locale] ?? ''));
    if ($value !== '') {
        return $value;
    }
    return trim((string)($item[$field . '_' . ($locale === 'bn' ? 'en' : 'bn')] ?? ''));
}
PHP;

$blockLines = explode("\n", $block);

// Build the new file: keep lines before $start, insert $blockLines, then keep
// everything from $end onward.
$out = [];
for ($i = 0; $i < $start; $i++) {
    $out[] = $lines[$i];
}
foreach ($blockLines as $l) {
    $out[] = $l;
}
for ($i = $end; $i < count($lines); $i++) {
    $out[] = $lines[$i];
}

file_put_contents($path, implode("\n", $out));
echo "OK: replaced block (old lines " . ($start + 1) . "-" . $end . ") in wsit_helpers.php\n";

<?php
// One-shot patch: replace the defective "homepage concept sections" block
// in app/wsit_helpers.php with a correct, self-consistent implementation.
//
// Boundaries are located by string markers, not fragile line numbers:
//   START  = line whose trimmed content starts with "// Homepage concept sections"
//   END    = the first line AFTER start matching "function hero_carousel_height_clamped"
// Everything strictly between is removed and replaced by $block.

$path = 'E:\\wamp64\\www\\methofresh.com\\app\\wsit_helpers.php';
$src  = file_get_contents($path);
if ($src === false) {
    echo "ERROR: cannot read $path\n";
    exit(1);
}

$lines = explode("\n", $srcipse);
$lines = explode("\n", $src);
$start = null;
$end   = null;

foreach ($lines as $i => $line) {
    $trim = ltrim($line);
    if ($start === null && str_starts_with($trim, '// Homepage concept sections')) {
        $start = $i;
        continue;
    }
    if ($start !== null && str_starts_with($trim, 'function hero_carousel_height_clamped')) {
        $end = $i;
        break;
    }
}

if ($start === null || $end === null) {
    echo "ERROR: markers not found (start=" . var_export($start, true) . ", end=" . var_export($end, true) . ")\n";
    exit(1);
}

$block = <<<'PHP'

// Homepage concept sections (all static home sections, dashboard-editable)
// ---------------------------------------------------------------------------

/**
 * Every editable homepage concept section. One JSON setting per section
 * (`home_section_<id>`), merged over the per-locale lang defaults so the
 * homepage stays byte-identical until an admin overrides a field.
 *
 * Default content comes from the current locale's lang file: a scalar field
 * (e.g. `title`/`text`) falls back to `trans('land_*')`. Repeatable "items"
 * (trust badges, value cards, story checkmarks, why cards, how steps,
 * testimonials, FAQ rows) each pull a bilingual `_en`/`_bn` pair from `trans`.
 */

/**
 * Definition of every editable homepage concept section.
 * Each entry describes:
 *  - `label_key`        : admin-facing section name (language key).
 *  - `section_label_key`: section heading in the editor
 *  - `repeat`           : number of default repeatable cards (items).
 *  - `item_fields`      : per-item editable fields.
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
                ['name' => 'text', 'type' => 'text', 'fallback' => 'land_trust_', 'bilingual' => true],
            ],
        ],
        'values' => [
            'label_key'         => 'land_values_title',
            'section_label_key' => 'admin_home_section_values',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_values_items',
            'item_fields'       => [
                ['name' => 'emoji', 'type' => 'text', 'short' => true],
                ['name' => 'title', 'type' => 'text', 'fallback' => 'land_value_', 'bilingual' => true],
                ['name' => 'text',  'type' => 'text', 'fallback' => 'land_value_', 'suffix' => '_text', 'bilingual' => true],
            ],
        ],
        'story' => [
            'label_key'         => 'land_story_title',
            'section_label_key' => 'admin_home_section_story',
            'fields'            => [
                ['name' => 'badge', 'type' => 'text',     'fallback' => 'land_story_badge', 'bilingual' => true],
                ['name' => 'title', 'type' => 'text',     'fallback' => 'land_story_title', 'bilingual' => true],
                ['name' => 'par',   'type' => 'textarea', 'fallback' => 'land_story_par',  'bilingual' => true],
            ],
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_story_points',
            'item_fields'       => [
                ['name' => 'text', 'type' => 'text', 'fallback' => 'land_story_', 'bilingual' => true],
            ],
        ],
        'why' => [
            'label_key'         => 'land_why_title',
            'section_label_key' => 'admin_home_section_why',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_why_items',
            'item_fields'       => [
                ['name' => 'icon', 'type' => 'text', 'short' => true],
                ['name' => 'title', 'type' => 'text', 'fallback' => 'land_why_', 'bilingual' => true],
                ['name' => 'text',  'type' => 'text', 'fallback' => 'land_why_', 'suffix' => '_text', 'bilingual' => true],
            ],
        ],
        'how' => [
            'label_key'         => 'land_how_title',
            'section_label_key' => 'admin_home_section_how',
            'repeat'            => 3,
            'item_label_key'    => 'admin_home_section_how_steps',
            'item_fields'       => [
                ['name' => 'num',   'type' => 'text', 'short' => true],
                ['name' => 'title', 'type' => 'text', 'fallback' => 'land_how_', 'bilingual' => true],
                ['name' => 'text',  'type' => 'text', 'fallback' => 'land_how_', 'suffix' => '_text', 'bilingual' => true],
            ],
        ],
        'testimonials' => [
            'label_key'         => 'land_testimonials_title',
            'section_label_key' => 'admin_home_section_testimonials',
            'repeat'            => 3,
            'item_label_key'    => 'admin_home_section_testimonials_items',
            'item_fields'       => [
                ['name' => 'text', 'type' => 'textarea', 'fallback' => 'land_testimonial_', 'bilingual' => true],
                ['name' => 'by',   'type' => 'text',     'fallback' => 'land_testimonial_', 'suffix' => '_by', 'bilingual' => true],
                ['name' => 'loc',  'type' => 'text',     'fallback' => 'land_testimonial_', 'suffix' => '_loc', 'bilingual' => true],
            ],
        ],
        'faq' => [
            'label_key'         => 'land_faq_title',
            'section_label_key' => 'admin_home_section_faq',
            'repeat'            => 4,
            'item_label_key'    => 'admin_home_section_faq_items',
            'item_fields'       => [
                ['name' => 'q', 'type' => 'text',     'fallback' => 'land_faq_', 'suffix' => '_q', 'bilingual' => true],
                ['name' => 'a', 'type' => 'textarea', 'fallback' => 'land_faq_', 'suffix' => '_a', 'bilingual' => true],
            ],
        ],
        'cta' => [
            'label_key'         => 'land_cta_title',
            'section_label_key' => 'admin_home_section_cta',
            'fields'            => [
                ['name' => 'title', 'type' => 'text',     'fallback' => 'land_cta_title', 'bilingual' => true],
                ['name' => 'text',  'type' => 'textarea', 'fallback' => 'land_cta_text', 'bilingual' => true],
            ],
        ],
    ];
}

/**
 * Localized default for a single bilingual field, resolved for both locales
 * from the lang file so defaults need no storage.
 */
function home_section_bilingual_default(string $langKey): array
{
    return [
        'en' => trim(trans($langKey)),
        'bn' => trim(trans($langKey)),
    ];
}

/**
 * Default (lang-driven) content for a section item.
 */
function home_section_item_defaults(string $sectionId, array $config, int $index, ?string $locale = null): array
{
    $item = ['id' => $index];
    foreach ($config['item_fields'] ?? [] as $field) {
        $name   = $field['name'];
        $prefix = $field['fallback'] ?? '';
        $suffix = $field['suffix'] ?? '';
        $key    = $prefix . $index . $suffix payroll;
        if (!empty($field['bilingual'])) {
            foreach (['en', 'bn'] as $lang) {
                $item[$name . '_' . $lang] = trim(trans($key));
            }
        } else {
            $item[$name] = trim(trans($key));
        }
    }
    return $item;
}

/**
 * Default (lang-driven) content for a whole section, bilingual fields kept
 * in `_en`/`_bn` pairs ({@see home_section_bilingual_default()}).
 */
function homepage_section_defaults(string $id): array
{
    $section = homepage_sections_config()[$id] ?? null;
    if ($section === null) {
        return ['id' => $id, 'visible' => true, 'order' => 1, 'fields' => [], 'items' => []];
    }

    $defaults = [
        'id'      => $id,
        'visible' => true,
        'order'   => (int)array_search($id, array_keys(homepage_sections_config()), true) + 1,
        'fields'  => [],
        'items'   => [],
    ];

    foreach ($section['fields'] ?? [] as $field) {
        $name     = $field['name'];
        $fallback = $field['fallback'] ?? '';
        if (!empty($field['bilingual'])) {
            $defaults['fields'][$name . '_en'] = trim(trans($fallback . '_en'));
            $defaults['fields'][$name . '_bn'] = trim(trans($fallback . '_bn'));
        } else {
            $defaults['fields'][$name] = trim(trans($fallback));
        }
    }

    $count = (int)($section['repeat'] ?? 0);
    for ($i = 1; $i <= $count; $i++) {
        $defaults['items'][] = home_section_item_defaults($id, $section, $i);
    }

    return $defaults;
}

/**
 * Merge stored settings JSON over the lang defaults for a section. Element
 * order is preserved so manual edits keep their ordering.
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
        foreach ($stored['fields'] as $fieldKey => $value) {
            $defaults['fields'][$fieldKey] = trim((string)$value);
        }
    }

    if (isset($stored['items']) && is_array($stored['items'])) {
        $items = [];
        foreach ($stored['items'] as $rawItem) {
            if (!is_array($rawItem)) {
                continue;
            }
            $clean = ['id' => (int)($rawItem['id'] ?? 0)];
            foreach ($defaults['items'][0] ?? [] as $fieldKey => $fieldV) {
                if ($fieldKey === 'id') {
                    continue;
                }
                $clean[$fieldKey] = array_key_exists($fieldKey, $rawItem)
                    ? trim((string)$rawItem[$fieldKey])
                    : $fieldV;
            }
            $items[] = $clean;
        }
        if (count($items) > 0) {
            $defaults['items'] = $items;
        }
    }

    return $defaults;
}

/**
 * Every editable homepage section merged with lang defaults, ordered for render.
 */
function homepage_sections(): array
{
    $all = [];
    foreach (array_keys(homepage_sections_config()) as $id) {
        $all[$id] = homepage_section($id);
    }
    uasort($all, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
    return $all;
}

/**
 * Localized scalar field value for a section (falls back bn->en->lang default).
 */
function home_section_field(array $section, string $field, ?string $locale = null): string
{
    $locale  = $locale ?? app_locale();
    $fields  = $section['fields'] ?? [];
    $value   = trim((string)($fields[$field . '_' . $locale] ?? ''));
    if ($value !== '') {
        return $value;
    }
    if ($locale === 'bn') {
        return trim((string)($fields[$field . '_en'] ?? ''));
    }
    return trim((string)($fields[$field . '_bn'] ?? ''));
}

/**
 * Localized item field value (falls back bn->en->lang default).
 */
function home_section_item(array $section, array $item, string $field, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $value  = trim((string)($item[$field . '_' . $locale] ?? ''));
    if ($value !== '') {
        return $value;
    }
    if ($locale === 'bn') {
        return trim((string)($item[$field . '_en'] ?? ''));
    }
    return trim((string)($item[$field . '_bn'] ?? ''));
}

PHP;

// Now splice: keep lines up to and including start, drop start+1..end-1,
// then insert the block, then keep end..end of file.
$out   = [];
foreach ($lines as $i => $line) {
    if ($i < $start) {
        $out[] = $line;
    } elseif ($i === $start) {
        $out[] = $line;                         // keep header comment line
        $out[] = $block;                        // then the new code
    } elseif ($i === $end) {
        // fall through: end marker will be re-added below (it is part of $i<$start? no)
    } else {
        continue;
    }
}
// The loop above discards end..last; re-append everything from $end onward.
// Simplest: rebuild cleanly.
$out = [];
for ($i = 0; $i <= $start; $i++) {
    $out[] = $lines[$i];
}
$out[] = $block;
for ($i = $end; $i < count($lines); $i++) {
    $out[] = $lines[$i];
}

$result = implode("\n", $out);
file_put_contents($path, $resultapsed);
echo "OK: replaced lines " . ($start + 1) . ".." . $end . " in wsit_helpers.php\n";

// Sanity: PHP lint after write.
exec('php -l ' . escapeshellarg($path) . ' 2>&1', $lintOut, $lintCode);
echo implode("\n", $lintOut);
exit($lintCode);

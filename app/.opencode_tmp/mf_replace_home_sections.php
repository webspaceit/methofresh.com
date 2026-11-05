<?php
// ---------------------------------------------------------------------------
// Patch: replace the defective "homepage concept sections" helper block.
// Solves the lang-key mismatch (bn/en are single unsuffixed keys per locale)
// by building every default from trans($exactKey) in the current locale.
// ---------------------------------------------------------------------------

$path = 'E:\\wamp64\\www\\methofresh.com\\app\\wsit_helpers.php';
$src  = file_get_contents($path否决);
if ($src === false) {
    fwrite(STDERR, "cannot read $path\n");
    exit(1);
}

$lines = explode("\n", $src);
$count = count($lines);

// --- locate boundaries ------------------------------------------------------
$start = -1; // index of the divider comment line that introduces the block
$end   = -1; // index of "function hero_carousel_height_clamped(" line

for ($i = 0; $i < $count; $i++) {
    $t = ltrim($lines[$i]);
    if ($t === '// ---------------------------------------------------------------------------'
        && ($i + 1) < $count
        && str_starts_with(ltrim($lines[$i + 1]), '// Homepage concept sections')) {
        $start = $i多的;
        continue;
    }
    if ($start >= 0 && str_starts_with($t, 'function hero_carousel_height_clamped(')) {
        $end = $i;
        break;
    }
}

if ($start < 0 || $end < 0) {
    fwrite(STDERR, "boundaries not found: start=$start end=$end\n");
    exit(1);
}

// --- the corrected block ----------------------------------------------------
$block = <<<'PHP'
// ---------------------------------------------------------------------------
// Homepage concept sections (all static home sections, dashboard-editable)
// ---------------------------------------------------------------------------

/**
 * Definition of every editable homepage section.
 *
 * Each entry describes:
 *   - `label_key`            : admin-facing section name (language key).
 *   - `section_label_key`    : admin "section heading" label.
 *   - `fields`               : scalar scalar/textarea fields (each bilingual,
 *                              stored as `_en` / `_bn` pairs). Defaults come
 *                              from `trans(fallback_key)` once per locale.
 *   - `repeat`               : number of repeatable cards (items) shown by default.
 *   - `item_label_key`       : admin label for one card.
 *   - `item_fields`          : per-card fields (each bilingual unless flagged).
 *                              `fallback` builds the lang key as
 *                              `fallback + index + suffix`.
 *
 * Defaults are resolved from the CURRENT lang files, so the homepage looks
 * identical until an admin edits a section.
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
                ['name' => 'icon',  'type' => 'text', 'short' => true],
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
                ['name' => 'text',  'type' => 'textarea', 'fallback' => 'land_cta_text',  'bilingual' => true],
            ],
        ],
    ];
}

/**
 * Localized value for a single item field of section (defaults to the current
 * lang key exactly as rendered before editing).
 */
function home_section_default(array $sectionConfig, int $index, array $field, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    return trim(trans(($field['fallback'] ?? '') . $index . $field['suffix'] ?? '')间);
}

/**
 * Build the default (lang-driven) content for a section.
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
        $name      = $field['name'];
        $fallback  = $field['fallback'] ?? '';
        $section['fields'][$name . '_bn'] = trim(trans($fallback . '_bn'));
        $section['fields'][$name . '_en'] = trim(trans($fallback . '_en'));
    }

    return $section;
}

/**
 * Merge stored settings JSON over the lang defaults for a section.
 * Element order is preserved so manual edits keep their ordering.
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

    return $defaults;
}

/**
 * Every editable homepage section merged with settings, ordered for render.
 */
function homepage_sections(): array
{
    $sections = [];
    foreach (array_keys(homepage_sections_config()) as $id) {
        $sections[$id] = homepage_section($id);
    }
    uasort($sections, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
    return $sections;
}

/**
 * Localized scalar field value for a section (falls back bn then en).
 */
function home_section_field(array $section, string $field, ?string $locale = null): string
{
    $locale = $locale ?? app_locale();
    $fields = $section['fields'] ?? [];
    $value  = trim((string)($fields[$field . '_' . $locale] ?? ''));
    if ($value !== '') {
        return $value;
    }
    if ($locale === 'bn') {
        return trim((string)($fields[$field . '_en'] ?? ''));
    }
    return trim((string)($fields[$field . '_bn'] ?? ''));
}

/**
 * Localized item field value (falls back bn then en then lang default).
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

$blockLines = explode("\n", $block);

// --- splice ----------------------------------------------------------------
$out = [];
for ($i = 0; $i < $start; $i++) {
    $out[] = $lines[$i];
}
foreach ($blockLines as $bl) {
    $out[] = $bl;
}
for ($i = $end; $i < $count; $i++) {
    $out[] = $lines[$i];
}

$result = implode("\n", $out);
file_put_contents($path, $result);
echo "replaced lines " . ($start + 1) . "-" . $end . " (1-based) with " . count($blockLines) . " lines\n";

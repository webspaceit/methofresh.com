<?php
// SEO meta tags: canonical, Open Graph, Twitter Card, hreflang, JSON-LD.
// Auto-generates values from page context; admin can override per-product
// and set site-wide defaults in Dashboard → SEO.

$seoTitle       = $pageTitle ?? app_name();
$seoDescription = $pageDescription ?? trans('tagline');
$seoCanonical   = $canonicalUrl ?? seo_canonical_url();
$seoOgImage     = $ogImage ?? seo_default_og_image();
$seoType        = $ogType ?? 'website';

// hreflang: build alternate URLs for each locale
$seoHreflang    = seo_hreflang_links($seoCanonical);

// JSON-LD structured data
$seoJsonLd       = $jsonLd ?? [];
if (empty($seoJsonLd)) {
    $seoJsonLd = seo_default_jsonld($seoTitle, $seoDescription, $seoCanonical, $seoOgImage);
}
?>
<link rel="canonical" href="<?= e($seoCanonical) ?>">
<?php foreach ($seoHreflang as $hreflang): ?>
<link rel="alternate" hreflang="<?= e($hreflang['lang']) ?>" href="<?= e($hreflang['url']) ?>">
<?php endforeach; ?>
<meta property="og:site_name" content="<?= e(app_name()) ?>">
<meta property="og:locale" content="<?= e(app_locale() === 'bn' ? 'bn_BD' : 'en_US') ?>">
<?php foreach (locales() as $loc): ?>
<?php if ($loc !== app_locale()): ?><meta property="og:locale:alternate" content="<?= e($loc === 'bn' ? 'bn_BD' : 'en_US') ?>"><?php endif; ?>
<?php endforeach; ?>
<meta property="og:title" content="<?= e($seoTitle) ?>">
<meta property="og:description" content="<?= e($seoDescription) ?>">
<meta property="og:url" content="<?= e($seoCanonical) ?>">
<?php if ($seoOgImage !== ''): ?>
<meta property="og:image" content="<?= e($seoOgImage) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<?php endif; ?>
<meta property="og:type" content="<?= e($seoType) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seoTitle) ?>">
<meta name="twitter:description" content="<?= e($seoDescription) ?>">
<?php if ($seoOgImage !== ''): ?>
<meta name="twitter:image" content="<?= e($seoOgImage) ?>">
<?php endif; ?>
<?php foreach ($seoJsonLd as $schema): ?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endforeach; ?>

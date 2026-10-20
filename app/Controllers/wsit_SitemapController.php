<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Models\wsit_Category;
use App\Models\wsit_Page;
use App\Models\wsit_Product;

/**
 * Generates a dynamic XML sitemap (sitemap.xml) for search engines.
 *
 * The sitemap is built on every request from the live database, so it
 * always reflects the current set of pages, categories and products —
 * including both locales (en/bn) when the app is configured with them.
 */
class wsit_SitemapController extends wsit_Controller
{
    public function index(): void
    {
        $productModel  = new wsit_Product();
        $categoryModel = new wsit_Category();
        $pageModel     = new wsit_Page();

        $locales = locales();
        $baseUrl = rtrim(canonical_base_url(), '/');

        $urls = [];

        // Root entry (fallback / x-default target)
        $urls[] = [
            'loc'        => $baseUrl . '/',
            'changefreq' => 'daily',
            'priority'   => '1.0',
        ];

        // Homepage — each locale
        foreach ($locales as $loc) {
            $urls[] = [
                'loc'        => $baseUrl . '/' . $loc,
                'changefreq' => 'daily',
                'priority'   => '1.0',
            ];
        }

        // Products catalog listing — each locale
        foreach ($locales as $loc) {
            $urls[] = [
                'loc'        => $baseUrl . '/' . $loc . '/products',
                'changefreq' => 'daily',
                'priority'   => '0.9',
            ];
        }

        // Categories index listing — each locale
        foreach ($locales as $loc) {
            $urls[] = [
                'loc'        => $baseUrl . '/' . $loc . '/categories',
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        // Products — one entry per locale per product with Google Image extensions
        foreach ($locales as $loc) {
            $products = $productModel->localized(
                $productModel->allActive(),
                $loc
            );
            foreach ($products as $p) {
                $lastmod = !empty($p['updated_at'])
                    ? date('c', strtotime($p['updated_at']))
                    : date('c', strtotime($p['created_at']));

                $item = [
                    'loc'        => $baseUrl . '/' . $loc . '/products/' . $p['slug'],
                    'lastmod'    => $lastmod,
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];

                if (!empty($p['image'])) {
                    $item['image'] = [
                        'loc'   => absolute_url(image_url($p['image'], $p['name'] ?? '', 1200)),
                        'title' => $p['name'] ?? ($p['name_en'] ?? ''),
                    ];
                }

                $urls[] = $item;
            }
        }

        // Categories — one entry per locale per category
        foreach ($locales as $loc) {
            $categories = $categoryModel->localized(
                $categoryModel->allWithCounts(true),
                $loc
            );
            foreach ($categories as $c) {
                $urls[] = [
                    'loc'        => $baseUrl . '/' . $loc . '/categories/' . $c['slug'],
                    'changefreq' => 'weekly',
                    'priority'   => '0.7',
                ];
            }
        }

        // Static pages — exclude 'contact' to avoid duplicating the standalone /contact route
        foreach ($locales as $loc) {
            $pages = $pageModel->localized(
                $pageModel->allActive(),
                $loc
            );
            foreach ($pages as $pg) {
                if (($pg['slug'] ?? '') === 'contact') {
                    continue;
                }
                $urls[] = [
                    'loc'        => $baseUrl . '/' . $loc . '/pages/' . $pg['slug'],
                    'changefreq' => 'monthly',
                    'priority'   => '0.5',
                ];
            }
        }

        // Contact page — always indexable
        foreach ($locales as $loc) {
            $urls[] = [
                'loc'        => $baseUrl . '/' . $loc . '/contact',
                'changefreq' => 'monthly',
                'priority'   => '0.5',
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        foreach ($urls as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . e($entry['loc']) . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                echo '    <lastmod>' . e($entry['lastmod']) . "</lastmod>\n";
            }
            echo '    <changefreq>' . e($entry['changefreq']) . "</changefreq>\n";
            echo '    <priority>' . e($entry['priority']) . "</priority>\n";
            if (!empty($entry['image']['loc'])) {
                echo "    <image:image>\n";
                echo '      <image:loc>' . e($entry['image']['loc']) . "</image:loc>\n";
                if (!empty($entry['image']['title'])) {
                    echo '      <image:title>' . e($entry['image']['title']) . "</image:title>\n";
                }
                echo "    </image:image>\n";
            }
            echo "  </url>\n";
        }
        echo "</urlset>\n";
        exit;
    }
}
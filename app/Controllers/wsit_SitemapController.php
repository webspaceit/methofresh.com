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
        $baseUrl = rtrim(base_url_path(), '/');

        $urls = [];

        // Homepage — every locale gets its own entry.
        foreach ($locales as $loc) {
            $urls[] = [
                'loc'      => $baseUrl . '/' . $loc,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];
        }

        // Products — one entry per locale per product.
        foreach ($locales as $loc) {
            $products = $productModel->localized(
                $productModel->allActive(),
                $loc
            );
            foreach ($products as $p) {
                $lastmod = !empty($p['updated_at'])
                    ? date('c', strtotime($p['updated_at']))
                    : date('c', strtotime($p['created_at']));
                $urls[] = [
                    'loc'      => $baseUrl . '/' . $loc . '/products/' . $p['slug'],
                    'lastmod'  => $lastmod,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }
        }

        // Categories — one entry per locale per category.
        foreach ($locales as $loc) {
            $categories = $categoryModel->localized(
                $categoryModel->allWithCounts(true),
                $loc
            );
            foreach ($categories as $c) {
                $urls[] = [
                    'loc'      => $baseUrl . '/' . $loc . '/categories/' . $c['slug'],
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        }

        // Static pages — one entry per locale per active page.
        foreach ($locales as $loc) {
            $pages = $pageModel->localized(
                $pageModel->allActive(),
                $loc
            );
            foreach ($pages as $pg) {
                $urls[] = [
                    'loc'      => $baseUrl . '/' . $loc . '/pages/' . $pg['slug'],
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ];
            }
        }

        // Contact page — always indexable.
        foreach ($locales as $loc) {
            $urls[] = [
                'loc'      => $baseUrl . '/' . $loc . '/contact',
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . e($entry['loc']) . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                echo '    <lastmod>' . e($entry['lastmod']) . "</lastmod>\n";
            }
            echo '    <changefreq>' . e($entry['changefreq']) . "</changefreq>\n";
            echo '    <priority>' . e($entry['priority']) . "</priority>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
        exit;
    }
}
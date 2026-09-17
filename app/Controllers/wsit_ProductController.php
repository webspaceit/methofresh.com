<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Category;
use App\Models\wsit_Product;

class wsit_ProductController extends wsit_Controller
{
    private wsit_Product $productModel;
    private wsit_Category $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new wsit_Product();
        $this->categoryModel = new wsit_Category();
    }

    public function index(wsit_Request $request): string
    {
        $perPage = max(4, min(48, (int)$request->query('per_page', 12)));
        $page = max(1, (int)$request->query('page', 1));
        $categoryId = (int)$request->query('category', 0);
        $search = trim((string)$request->query('q', ''));
        $sort = $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'price_asc', 'price_desc', 'popular'], true)) {
            $sort = 'newest';
        }

        $minPrice = $request->query('min_price') !== null && $request->query('min_price') !== '' ? (float)$request->query('min_price') : null;
        $maxPrice = $request->query('max_price') !== null && $request->query('max_price') !== '' ? (float)$request->query('max_price') : null;
        $inStock = (bool)$request->query('in_stock', false);
        $saleOnly = (bool)$request->query('sale_only', false);

        // Category slug lookup
        if ($categoryId === 0) {
            $categorySlug = trim((string)$request->query('cat', ''));
            if ($categorySlug !== '') {
                $cat = $this->categoryModel->findBySlug($categorySlug);
                if ($cat) {
                    $categoryId = (int)$cat['id'];
                }
            }
        }

        $total = $this->productModel->count($categoryId, $search, $minPrice, $maxPrice, $inStock, $saleOnly);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $products = $this->productModel->localized(
            $this->productModel->all($categoryId, $search, $sort, $perPage, $offset, $minPrice, $maxPrice, $inStock, $saleOnly),
            app_locale()
        );

        $categories = $this->categoryModel->localized(
            $this->categoryModel->allWithCounts(true),
            app_locale()
        );

        $activeCategory = null;
        if ($categoryId > 0) {
            $raw = $this->categoryModel->find($categoryId);
            if ($raw) {
                $activeCategory = $this->categoryModel->localized([$raw], app_locale())[0];
            }
        }

        $totalAllProducts = $this->productModel->count();

        return $this->view('products/wsit_index', [
            'pageTitle'         => trans('products_title'),
            'products'          => $products,
            'categories'        => $categories,
            'activeCategory'    => $activeCategory,
            'categoryId'        => $categoryId,
            'search'            => $search,
            'sort'              => $sort,
            'page'              => $page,
            'pages'             => $pages,
            'total'             => $total,
            'perPage'           => $perPage,
            'minPrice'          => $minPrice,
            'maxPrice'          => $maxPrice,
            'inStock'           => $inStock,
            'saleOnly'          => $saleOnly,
            'totalAllProducts'  => $totalAllProducts,
        ]);
    }

    public function suggest(wsit_Request $request): void
    {
        $q = trim((string)$request->query('q', ''));
        $results = [];

        if (mb_strlen($q) >= 2) {
            $products = $this->productModel->localized(
                $this->productModel->suggest($q, 8),
                app_locale()
            );
            foreach ($products as $p) {
                $d = price_display($p);
                $results[] = [
                    'id'    => (int)$p['id'],
                    'name'  => $p['name'],
                    'slug'  => $p['slug'],
                    'image' => image_url($p['image'] ?? null, $p['name'], 96),
                    'price' => format_price($d['current']),
                    'url'   => locale_url('/products/' . $p['slug']),
                ];
            }
        }

        $this->json(['query' => $q, 'results' => $results]);
    }

    public function loadMore(wsit_Request $request): void
    {
        $perPage = max(4, min(48, (int)$request->query('per_page', 12)));
        $page = max(1, (int)$request->query('page', 1));
        $categoryId = (int)$request->query('category', 0);
        $search = trim((string)$request->query('q', ''));
        $sort = $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'price_asc', 'price_desc', 'popular'], true)) {
            $sort = 'newest';
        }

        $minPrice = $request->query('min_price') !== null && $request->query('min_price') !== '' ? (float)$request->query('min_price') : null;
        $maxPrice = $request->query('max_price') !== null && $request->query('max_price') !== '' ? (float)$request->query('max_price') : null;
        $inStock = (bool)$request->query('in_stock', false);
        $saleOnly = (bool)$request->query('sale_only', false);

        if ($categoryId === 0) {
            $categorySlug = trim((string)$request->query('cat', ''));
            if ($categorySlug !== '') {
                $cat = $this->categoryModel->findBySlug($categorySlug);
                if ($cat) {
                    $categoryId = (int)$cat['id'];
                }
            }
        }

        $total = $this->productModel->count($categoryId, $search, $minPrice, $maxPrice, $inStock, $saleOnly);
        $pages = max(1, (int)ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $products = $this->productModel->localized(
            $this->productModel->all($categoryId, $search, $sort, $perPage, $offset, $minPrice, $maxPrice, $inStock, $saleOnly),
            app_locale()
        );

        $html = '';
        foreach ($products as $product) {
            ob_start();
            include views_path('partials/wsit_product-card.php');
            $html .= ob_get_clean();
        }

        $loadedCount = min($total, $offset + count($products));

        $this->json([
            'html'         => $html,
            'page'         => $page,
            'pages'        => $pages,
            'total'        => $total,
            'loaded'       => $loadedCount,
            'per_page'     => $perPage,
            'has_more'     => $page < $pages,
        ]);
    }
    public function show(string $slug): string
    {
        $product = $this->productModel->findBySlug($slug);

        if ($product === null) {
            return $this->notFound();
        }

        $product = $this->productModel->localized([$product], app_locale())[0];
        $related = $this->productModel->localized(
            $this->productModel->related((int)$product['id'], (int)$product['category_id'], 4),
            app_locale()
        );

        return $this->view('products/wsit_show', [
            'pageTitle' => $product['name'],
            'product'   => $product,
            'related'   => $related,
        ]);
    }

    private function notFound(): string
    {
        return (new wsit_ErrorController())->notFound();
    }
}
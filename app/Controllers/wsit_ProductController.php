<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Category;
use App\Models\wsit_Product;
use App\Models\wsit_SearchLog;

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

        // Record real storefront searches for the admin dashboard's
        // "Most searched" widget. Logging must never break the search.
        if ($search !== '') {
            try {
                $now = time();
                $model = new wsit_SearchLog();
                $ep = $this->searchEpisode();
                if ($ep['t'] > 0 && ($now - $ep['t']) > 60) {
                    $ep = ['items' => [], 't' => 0];
                }

                // Collapse this submit's typing fragments into one row with
                // the full submitted term (covers Enter and "See all" too).
                $merge = [];
                foreach ($ep['items'] as $id => $typed) {
                    if (self::searchTermsRelated($typed, $search)) {
                        $merge[] = $id;
                    }
                }
                if ($merge !== []) {
                    $keepId = array_shift($merge);
                    $model->updateQuery($keepId, $search, (int)$total);
                    if ($merge !== []) {
                        $model->deleteByIds($merge);
                        foreach ($merge as $id) {
                            unset($ep['items'][$id]);
                        }
                    }
                    unset($ep['items'][$keepId]);
                    $ep['items'][$keepId] = $search;
                } else {
                    $id = $model->log($search, app_locale(), (int)$total);
                    if ($id > 0) {
                        $ep['items'][$id] = $search;
                    }
                }
                $ep['t'] = $now;
                $this->saveSearchEpisode($ep);
            } catch (\Throwable $e) {
                // ignore — logging failures are non-fatal
            }
        }

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
            // The suggestion dropdown is where real searches happen (visitors
            // type here, then click a suggestion straight to its product page).
            $this->logSearchIntent($q);
        }

        $this->json(['query' => $q, 'results' => $results]);
    }

    /**
     * Are two search terms part of the same typing effort?
     * (identical, or one extends the other)
     */
    private static function searchTermsRelated(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' || $b === '') {
            return false;
        }
        return $a === $b || str_starts_with($a, $b) || str_starts_with($b, $a);
    }

    /**
     * The current search episode: rows this visitor typed but that have
     * not yet been resolved by a submit or a suggestion click.
     * Shape: ['items' => [rowId => typedQuery, ...], 't' => lastActivityTs]
     */
    private function searchEpisode(): array
    {
        $ep = $_SESSION['search_log'] ?? null;
        if (!is_array($ep) || !isset($ep['items'], $ep['t']) || !is_array($ep['items'])) {
            return ['items' => [], 't' => 0];
        }
        $items = [];
        foreach ($ep['items'] as $id => $q) {
            if ((int)$id > 0 && is_string($q) && $q !== '') {
                $items[(int)$id] = $q;
            }
        }
        return ['items' => $items, 't' => (int)$ep['t']];
    }

    private function saveSearchEpisode(array $ep): void
    {
        $_SESSION['search_log'] = $ep;
    }

    /**
     * Record a search term for the admin "Most searched" widget.
     *
     * The live dropdown fires one request per keystroke, so consecutive
     * requests that continue the same term (prefix-related, within 8s)
     * are merged into the previous row instead of inserting partials.
     * Slower fragments stay separate rows but remain tracked in the
     * episode, so the visitor's eventual suggestion click can collapse
     * them all into the exact product name. URLs pasted into the search
     * box are ignored. Session-keyed per visitor.
     */
    private function logSearchIntent(string $q, ?int $hits = null): void
    {
        $q = trim($q);
        if ($q === '' || preg_match('#^https?://#i', $q)) {
            return;
        }
        try {
            if ($hits === null) {
                $hits = $this->productModel->count(0, $q);
            }
            $model = new wsit_SearchLog();
            $now = time();
            $ep = $this->searchEpisode();

            // Fast typing continues the previous row in place.
            $lastId = $ep['items'] !== [] ? (int)array_key_last($ep['items']) : 0;
            $lastQ = $lastId > 0 ? (string)$ep['items'][$lastId] : '';
            if ($lastId > 0 && ($now - $ep['t']) <= 8 && self::searchTermsRelated($q, $lastQ)) {
                $model->updateQuery($lastId, $q, $hits);
                $ep['items'][$lastId] = $q;
                $ep['t'] = $now;
                $this->saveSearchEpisode($ep);
                return;
            }

            // Over a minute of silence means a new search attempt.
            if ($ep['t'] > 0 && ($now - $ep['t']) > 60) {
                $ep = ['items' => [], 't' => 0];
            }

            $id = $model->log($q, app_locale(), $hits);
            if ($id > 0) {
                $ep['items'][$id] = $q;
                $ep['t'] = $now;
                $this->saveSearchEpisode($ep);
            }
        } catch (\Throwable $e) {
            // ignore — logging failures are non-fatal
        }
    }

    /**
     * POST /search/log — fired by the header search just before the
     * visitor navigates to a suggestion they clicked. Resolves the
     * episode's typed fragments into ONE row carrying the exact,
     * database-stored product name, so the dashboard shows what the
     * visitor actually opened instead of "gur", "te", "ghan" fragments.
     *
     * A click with no typed episode (or a stale one) is not a search
     * and is ignored — nothing can be fabricated from here.
     */
    public function logClick(wsit_Request $request): void
    {
        try {
            $productId = (int)$request->input('product_id', 0);
            $raw = $productId > 0 ? $this->productModel->find($productId) : null;
            if (!$raw) {
                $this->json(['ok' => false, 'reason' => 'no-product']);
                return;
            }

            $ep = $this->searchEpisode();
            $now = time();
            if ($ep['items'] === [] || ($now - $ep['t']) > 60) {
                $this->json(['ok' => false, 'reason' => 'no-search']);
                return;
            }

            $lastQ = (string)$ep['items'][array_key_last($ep['items'])];
            $merge = [];
            $keep = [];
            foreach ($ep['items'] as $id => $typed) {
                if (self::searchTermsRelated($typed, $lastQ)) {
                    $merge[] = $id;
                } else {
                    $keep[$id] = $typed; // unrelated earlier search — left untouched
                }
            }
            if ($merge === []) {
                $this->json(['ok' => false, 'reason' => 'nothing-to-merge']);
                return;
            }

            $localized = $this->productModel->localized([$raw], app_locale());
            $name = trim((string)($localized[0]['name'] ?? ''));
            if ($name === '') {
                $this->json(['ok' => false, 'reason' => 'unnamed']);
                return;
            }

            $model = new wsit_SearchLog();
            $hits = $this->productModel->count(0, $lastQ);
            $keepId = array_shift($merge);
            $model->updateQuery($keepId, $name, $hits);
            if ($merge !== []) {
                $model->deleteByIds($merge);
            }

            $keep[$keepId] = $name;
            $ep['items'] = $keep;
            $ep['t'] = $now;
            $this->saveSearchEpisode($ep);

            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false]);
        }
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
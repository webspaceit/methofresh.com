<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_Product extends wsit_Model
{
    protected string $table = 'products';
    private string $categories;

    public function __construct()
    {
        parent::__construct();
        $this->categories = table('categories');
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetch(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.slug = ? AND p.active = 1",
            [$slug]
        );
    }

    public function slugExists(string $slug, int|null $ignoreId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        if ($ignoreId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        return $this->fetch($sql, $params) !== null;
    }

    public function all(
        int $categoryId = 0,
        string $search = '',
        string $sort = 'newest',
        int $perPage = 12,
        int $offset = 0,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        bool $inStock = false,
        bool $saleOnly = false
    ): array {
        $sql = "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
                FROM {$this->table} p
                LEFT JOIN {$this->categories} c ON c.id = p.category_id
                WHERE p.active = 1";
        $params = [];

        if ($categoryId > 0) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $like = "%{$search}%";
            $conds = ['p.name_en LIKE ?', 'p.name_bn LIKE ?', 'p.description_en LIKE ?', 'p.description_bn LIKE ?'];
            array_push($params, $like, $like, $like, $like);
            // Romanized-Bengali matching: the normalized query also hits the
            // precomputed Latin search key ("shorisha" → সরিষা).
            $norm = search_normalize($search);
            if ($norm !== '') {
                $conds[] = 'p.search_key LIKE ?';
                $params[] = "%{$norm}%";
            }
            $sql .= ' AND (' . implode(' OR ', $conds) . ')';
        }
        if ($minPrice !== null && $minPrice > 0) {
            $sql .= ' AND COALESCE(p.sale_price, p.price) >= ?';
            $params[] = $minPrice;
        }
        if ($maxPrice !== null && $maxPrice > 0) {
            $sql .= ' AND COALESCE(p.sale_price, p.price) <= ?';
            $params[] = $maxPrice;
        }
        if ($inStock) {
            $sql .= ' AND p.stock > 0';
        }
        if ($saleOnly) {
            $sql .= ' AND p.sale_price IS NOT NULL AND p.sale_price < p.price';
        }

        $sql .= $this->orderBySql($sort);
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = $offset;

        return $this->fetchAll($sql, $params);
    }

    public function count(
        int $categoryId = 0,
        string $search = '',
        ?float $minPrice = null,
        ?float $maxPrice = null,
        bool $inStock = false,
        bool $saleOnly = false
    ): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} p WHERE p.active = 1";
        $params = [];
        if ($categoryId > 0) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($search !== '') {
            $like = "%{$search}%";
            $conds = ['p.name_en LIKE ?', 'p.name_bn LIKE ?', 'p.description_en LIKE ?', 'p.description_bn LIKE ?'];
            array_push($params, $like, $like, $like, $like);
            $norm = search_normalize($search);
            if ($norm !== '') {
                $conds[] = 'p.search_key LIKE ?';
                $params[] = "%{$norm}%";
            }
            $sql .= ' AND (' . implode(' OR ', $conds) . ')';
        }
        if ($minPrice !== null && $minPrice > 0) {
            $sql .= ' AND COALESCE(p.sale_price, p.price) >= ?';
            $params[] = $minPrice;
        }
        if ($maxPrice !== null && $maxPrice > 0) {
            $sql .= ' AND COALESCE(p.sale_price, p.price) <= ?';
            $params[] = $maxPrice;
        }
        if ($inStock) {
            $sql .= ' AND p.stock > 0';
        }
        if ($saleOnly) {
            $sql .= ' AND p.sale_price IS NOT NULL AND p.sale_price < p.price';
        }

        return (int)$this->fetchColumn($sql, $params);
    }

    public function countLowStock(int $threshold = 15): int
    {
        return (int)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} p WHERE p.active = 1 AND p.stock <= ?",
            [$threshold]
        );
    }

    private function orderBySql(string $sort): string
    {
        return match ($sort) {
            'price_asc'  => " ORDER BY COALESCE(sale_price, price) ASC",
            'price_desc' => " ORDER BY COALESCE(sale_price, price) DESC",
            'popular'    => " ORDER BY sold DESC",
            default      => " ORDER BY p.created_at DESC",
        };
    }

    public function featured(int $limit = 8): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1 AND p.featured = 1
             ORDER BY RAND() LIMIT {$limit}"
        );
    }

    public function latest(int $limit = 8): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1
             ORDER BY p.created_at DESC LIMIT {$limit}"
        );
    }

    public function newArrivals(int $limit = 8): array
    {
        $flagged = $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1 AND p.new_arrival = 1
             ORDER BY p.created_at DESC LIMIT {$limit}"
        );
        if (empty($flagged)) {
            return $this->latest($limit);
        }
        return $flagged;
    }

    public function related(int $productId, int $categoryId, int $limit = 4): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1 AND p.category_id = ? AND p.id != ?
             ORDER BY p.sold DESC, p.created_at DESC LIMIT {$limit}",
            [$categoryId, $productId]
        );
    }

    public function byCategory(int $categoryId, int $limit = 12, int $offset = 0): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1 AND p.category_id = ?
             ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
            [$categoryId, $limit, $offset]
        );
    }

    public function countByCategory(int $categoryId): int
    {
        return (int)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE active = 1 AND category_id = ?",
            [$categoryId]
        );
    }

    public function countAll(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table}");
    }

    public function topSellers(int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1 AND p.sold > 0
             ORDER BY p.sold DESC, p.id ASC LIMIT {$limit}"
        );
    }

    /**
     * Top category by units sold over the last $days, with growth vs the
     * preceding $days. Returns an empty-name row when there is no data.
     */
    public function topCategoryGrowth(int $days = 30): array
    {
        $orders = table('orders');
        $items  = table('order_items');
        $from      = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        $prevFrom  = date('Y-m-d 00:00:00', strtotime('-' . ($days * 2) . ' days'));

        $current = $this->fetch(
            "SELECT c.id AS category_id, c.slug, c.name_en, c.name_bn,
                    SUM(oi.quantity) AS qty,
                    COALESCE(SUM(oi.total), 0) AS revenue
             FROM {$items} oi
             INNER JOIN {$orders} o ON o.id = oi.order_id
             INNER JOIN {$this->table} p ON p.id = oi.product_id
             INNER JOIN {$this->categories} c ON c.id = p.category_id
             WHERE o.status <> 'cancelled' AND o.created_at >= ?
             GROUP BY c.id
             ORDER BY qty DESC, revenue DESC
             LIMIT 1",
            [$from]
        );

        if ($current === null || (int)($current['qty'] ?? 0) === 0) {
            return ['slug' => '', 'name_en' => '', 'name_bn' => '', 'qty' => 0, 'revenue' => 0.0, 'growth' => 0.0];
        }

        $previous = $this->fetch(
            "SELECT SUM(oi.quantity) AS qty, COALESCE(SUM(oi.total), 0) AS revenue
             FROM {$items} oi
             INNER JOIN {$orders} o ON o.id = oi.order_id
             INNER JOIN {$this->table} p ON p.id = oi.product_id
             WHERE o.status <> 'cancelled'
               AND o.created_at >= ? AND o.created_at < ?
               AND p.category_id = ?",
            [$prevFrom, $from, (int)$current['category_id']]
        );

        $prevQty = (int)($previous['qty'] ?? 0);
        $curQty  = (int)$current['qty'];
        $growth  = $prevQty > 0 ? round((($curQty - $prevQty) / $prevQty) * 100, 1) : ($curQty > 0 ? 100.0 : 0.0);

        return [
            'slug'    => (string)$current['slug'],
            'name_en' => (string)$current['name_en'],
            'name_bn' => (string)$current['name_bn'],
            'qty'     => $curQty,
            'revenue' => (float)$current['revenue'],
            'growth'  => $growth,
        ];
    }

    public function search(string $q = ''): array
    {
        $sql = "SELECT p.*, c.name_en AS category_name_en
                FROM {$this->table} p
                LEFT JOIN {$this->categories} c ON c.id = p.category_id";
        $params = [];
        if ($q !== '') {
            $sql .= ' WHERE p.name_en LIKE ? OR p.name_bn LIKE ? OR p.sku LIKE ?';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY p.created_at DESC';
        return $this->fetchAll($sql, $params);
    }

    public function suggest(string $q = '', int $limit = 8): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE active = 1";
        $params = [];
        $conditions = [];
        if ($q !== '') {
            $conditions[] = 'name_en LIKE ?';
            $params[] = "%{$q}%";
            $conditions[] = 'name_bn LIKE ?';
            $params[] = "%{$q}%";
            $conditions[] = 'sku LIKE ?';
            $params[] = "%{$q}%";
            $norm = search_normalize($q);
            if ($norm !== '') {
                $conditions[] = 'search_key LIKE ?';
                $params[] = "%{$norm}%";
            }
            $sql .= ' AND ((' . implode(') OR (', $conditions) . '))';
        }
        if ($q !== '') {
            // Name-prefix matches rank highest, then best-sellers, then newest.
            $sql .= ' ORDER BY (name_en LIKE ?) DESC, sold DESC, created_at DESC';
            $params[] = "{$q}%";
        } else {
            $sql .= ' ORDER BY sold DESC, created_at DESC';
        }
        $sql .= " LIMIT {$limit}";
        return $this->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        $data['search_key'] = product_search_key($data);
        return (int)$this->insertRecord($this->table, $data);
    }

    public function update(int $id, array $data): void
    {
        // Keep the romanized search key in sync whenever searchable fields
        // change; flag toggles (active/featured/…) leave it untouched.
        foreach (['name_en', 'name_bn', 'slug', 'sku', 'description_en', 'description_bn'] as $field) {
            if (array_key_exists($field, $data)) {
                $current = $this->find($id);
                $data['search_key'] = product_search_key(array_merge($current ?? [], $data));
                break;
            }
        }
        $this->updateRecord($this->table, $data, ['id' => $id]);
    }

    /**
     * Rows still missing a search key (fresh installs seeded from
     * schema.sql, legacy repair scripts). The admin product list heals
     * these on view.
     */
    public function staleSearchKeys(): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table} WHERE search_key IS NULL OR search_key = ''"
        );
    }

    public function delete(int $id): void
    {
        $this->deleteRecord($this->table, ['id' => $id]);
    }

    public function decrementStock(int $id, int $qty): void
    {
        $this->query("UPDATE {$this->table} SET stock = stock - ? WHERE id = ?", [$qty, $id]);
    }

    public function incrementSold(int $id, int $qty): void
    {
        $this->query("UPDATE {$this->table} SET sold = sold + ? WHERE id = ?", [$qty, $id]);
    }

    public function name(array $product, string|null $locale = null): string
    {
        $locale = $locale ?? app_locale();
        $col = $locale === 'bn' ? 'name_bn' : 'name_en';
        $value = trim((string)($product[$col] ?? ''));
        return $value !== '' ? $value : (string)($product['name_en'] ?? '');
    }

    public function description(array $product, string|null $locale = null): string
    {
        $locale = $locale ?? app_locale();
        $col = $locale === 'bn' ? 'description_bn' : 'description_en';
        $value = trim((string)($product[$col] ?? ''));
        return $value !== '' ? $value : (string)($product['description_en'] ?? '');
    }

    public function localized(array $products, string|null $locale = null): array
    {
        $locale = $locale ?? app_locale();
        foreach ($products as &$product) {
            $product['name'] = $this->name($product, $locale);
            $product['description'] = $this->description($product, $locale);
            $product['category_name'] = $locale === 'bn'
                ? ($product['category_name_bn'] ?? $product['category_name_en'] ?? '')
                : ($product['category_name_en'] ?? '');
        }
        return $products;
    }

    /**
     * All active products with category columns joined (used by the sitemap).
     */
    public function allActive(): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.slug AS category_slug, c.name_en AS category_name_en, c.name_bn AS category_name_bn
             FROM {$this->table} p
             LEFT JOIN {$this->categories} c ON c.id = p.category_id
             WHERE p.active = 1
             ORDER BY p.id ASC"
        );
    }
}
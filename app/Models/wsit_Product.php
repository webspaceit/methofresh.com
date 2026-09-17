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
            $sql .= ' AND (p.name_en LIKE ? OR p.name_bn LIKE ? OR p.description_en LIKE ? OR p.description_bn LIKE ?)';
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
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
            $sql .= ' AND (p.name_en LIKE ? OR p.name_bn LIKE ? OR p.description_en LIKE ? OR p.description_bn LIKE ?)';
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
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
        return (int)$this->insertRecord($this->table, $data);
    }

    public function update(int $id, array $data): void
    {
        $this->updateRecord($this->table, $data, ['id' => $id]);
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
}
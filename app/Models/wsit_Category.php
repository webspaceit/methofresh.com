<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_Category extends wsit_Model
{
    protected string $table = 'categories';
    private string $products;

    public function __construct()
    {
        parent::__construct();
        $this->products = table('products');
    }

    public function all(bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($onlyActive) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY name_en ASC';
        return $this->fetchAll($sql);
    }

    public function allWithCounts(bool $onlyActive = true): array
    {
        $sql = "SELECT c.*, COUNT(p.id) AS product_count
                FROM {$this->table} c
                LEFT JOIN {$this->products} p ON p.category_id = c.id AND p.active = 1";
        if ($onlyActive) {
            $sql .= ' WHERE c.active = 1';
        }
        $sql .= ' GROUP BY c.id ORDER BY c.name_en ASC';
        return $this->fetchAll($sql);
    }

    public static function allStatic(): array
    {
        try {
            $instance = new self();
            return $instance->localized($instance->all(true), app_locale());
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE slug = ? AND active = 1", [$slug]);
    }

    public function countProducts(int $id): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->products} WHERE category_id = ?", [$id]);
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

    public function name(array $category, string|null $locale = null): string
    {
        $locale = $locale ?? app_locale();
        $col = $locale === 'bn' ? 'name_bn' : 'name_en';
        $value = trim((string)($category[$col] ?? ''));
        return $value !== '' ? $value : (string)($category['name_en'] ?? '');
    }

    public function localized(array $categories, string|null $locale = null): array
    {
        $locale = $locale ?? app_locale();
        foreach ($categories as &$category) {
            $category['name'] = $this->name($category, $locale);
            $category['description'] = $locale === 'bn'
                ? (trim((string)($category['description_bn'] ?? '')) ?: $category['description_en'] ?? '')
                : ($category['description_en'] ?? '');
        }
        return $categories;
    }
}
<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_Page extends wsit_Model
{
    protected string $table = 'pages';

    public function all(bool $onlyActive = false): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($onlyActive) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY menu_order ASC, id ASC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE slug = ? AND active = 1", [$slug]);
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

    public function title(array $page, string|null $locale = null): string
    {
        $locale = $locale ?? app_locale();
        $col = $locale === 'bn' ? 'title_bn' : 'title_en';
        $value = trim((string)($page[$col] ?? ''));
        return $value !== '' ? $value : (string)($page['title_en'] ?? '');
    }

    public function content(array $page, string|null $locale = null): string
    {
        $locale = $locale ?? app_locale();
        $col = $locale === 'bn' ? 'content_bn' : 'content_en';
        $value = trim((string)($page[$col] ?? ''));
        return $value !== '' ? $value : (string)($page['content_en'] ?? '');
    }

    public function localized(array $pages, string|null $locale = null): array
    {
        $locale = $locale ?? app_locale();
        foreach ($pages as &$page) {
            $page['title'] = $this->title($page, $locale);
            $page['content'] = $this->content($page, $locale);
        }
        return $pages;
    }

    public static function activeMenu(): array
    {
        try {
            $instance = new self();
            return $instance->localized($instance->all(true), app_locale());
        } catch (\Throwable $e) {
            return [];
        }
    }
}
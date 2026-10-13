<?php

namespace App\Models;

use App\Core\wsit_Model;

/**
 * Storefront search log. One row per search performed on the catalog
 * results page (/products?q=...). Powers the admin dashboard's
 * "Most searched" widget.
 */
class wsit_SearchLog extends wsit_Model
{
    protected string $table = 'search_logs';

    /**
     * Record a search. Logging must never break the search itself, so
     * callers should wrap this in try/catch when it is on the hot path.
     */
    public function log(string $query, string $locale, int $hits = 0): int
    {
        $query = trim(mb_substr($query, 0, 255));
        if ($query === '') {
            return 0;
        }
        return $this->insertRecord($this->table, [
            'query'      => $query,
            'query_norm' => search_normalize($query),
            'locale'     => (string)$locale,
            'hits'       => max(0, $hits),
        ]);
    }

    /**
     * Merge a typing burst into an existing row: the dropdown fires a
     * request per keystroke, so we rewrite the row with the latest query
     * the visitor actually settled on instead of inserting partials.
     * created_at is kept from the first request so the timeline stays real.
     */
    public function updateQuery(int $id, string $query, int $hits): bool
    {
        $query = trim(mb_substr($query, 0, 255));
        if ($query === '' || $id <= 0) {
            return false;
        }
        $stmt = $this->query(
            "UPDATE {$this->table} SET query = ?, query_norm = ?, hits = ? WHERE id = ?",
            [$query, search_normalize($query), max(0, $hits), $id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove search-log rows (used to merge typing fragments when the
     * visitor's click resolves them into an exact product).
     */
    public function deleteByIds(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn($i) => $i > 0));
        if ($ids === []) {
            return 0;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->query("DELETE FROM {$this->table} WHERE id IN ({$ph})", $ids);
        return $stmt->rowCount();
    }

    /**
     * A term only counts when searching it actually leads a visitor to at
     * least one product — mirroring the storefront's search surfaces
     * (/search/suggest matches names + SKU; /products?q= matches names +
     * descriptions; both also match the normalized Latin search key so
     * romanized queries like "shorisha" count against সরিষা). Zero-result
     * queries stay in the log for raw analytics but never surface in the
     * dashboard's "Most searched" widget or its overview totals.
     */
    private function productMatchSql(): string
    {
        return "EXISTS (
            SELECT 1 FROM wsit_products p
            WHERE p.active = 1
              AND (p.name_en LIKE CONCAT('%', l.query, '%')
                OR p.name_bn LIKE CONCAT('%', l.query, '%')
                OR p.sku LIKE CONCAT('%', l.query, '%')
                OR p.description_en LIKE CONCAT('%', l.query, '%')
                OR p.description_bn LIKE CONCAT('%', l.query, '%')
                OR (l.query_norm <> '' AND p.search_key LIKE CONCAT('%', l.query_norm, '%')))
        )";
    }

    /**
     * Most-searched terms in the last N days, ranked by how often each
     * query was performed (ties broken by the latest result count).
     * Paginated: LIMIT/OFFSET for the dashboard widget (10 per page).
     * Only terms that return at least one product are included.
     */
    public function topTerms(int $days = 30, int $limit = 10, int $offset = 0): array
    {
        return $this->fetchAll(
            "SELECT l.query, COUNT(*) AS searches, MAX(l.hits) AS last_hits
             FROM {$this->table} l
             WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND {$this->productMatchSql()}
             GROUP BY l.query
             ORDER BY searches DESC, last_hits DESC, l.query ASC
             LIMIT ? OFFSET ?",
            [$days, max(1, $limit), max(0, $offset)]
        );
    }

    /**
     * How many distinct (productive) terms exist in the window (drives pagination).
     */
    public function countTerms(int $days = 30): int
    {
        return (int)$this->fetchColumn(
            "SELECT COUNT(DISTINCT l.query) FROM {$this->table} l
             WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND {$this->productMatchSql()}",
            [$days]
        );
    }

    /**
     * Total search events in the last N days (productive searches only,
     * so the overview always agrees with the terms shown).
     */
    public function countSearches(int $days = 30): int
    {
        return (int)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} l
             WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND {$this->productMatchSql()}",
            [$days]
        );
    }

    /**
     * Total search events since a given datetime (e.g. start of today).
     * Same productive-only rule as countSearches().
     */
    public function countSince(string $from): int
    {
        return (int)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} l
             WHERE l.created_at >= ?
               AND {$this->productMatchSql()}",
            [$from]
        );
    }
}
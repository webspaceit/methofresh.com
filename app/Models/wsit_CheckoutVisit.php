<?php

namespace App\Models;

use App\Core\wsit_Model;

/**
 * Checkout page visits — powers the admin dashboard's "Checkout funnel"
 * widget (who went as far as checkout, and who actually ordered).
 *
 * One row per visitor session per 30-minute window, so page refreshes
 * don't inflate the counts. user_id NULL = unregistered guest.
 * converted = the same session later placed an order, so abandoned
 * checkouts (guests who reached checkout but left) are countable.
 */
class wsit_CheckoutVisit extends wsit_Model
{
    protected string $table = 'checkout_visits';

    /** Refreshes inside this window merge into one visit row. */
    private const DEDUPE_MINUTES = 30;

    /** A session's visits count as converted when it orders within this window. */
    private const CONVERT_WINDOW_DAYS = 7;

    /** Stable per-session identifier (no PII stored). */
    public function sessionHash(): string
    {
        $sid = (string)session_id();
        return $sid === '' ? '' : hash('sha256', $sid);
    }

    /**
     * Record that a visitor reached the checkout page, with a snapshot of
     * what was in their cart. Best-effort by design: callers wrap in
     * try/catch so analytics never break checkout itself.
     * Returns 1 when a row was written, 0 when merged into a recent visit
     * (the merge refreshes the cart snapshot — created_at is kept).
     */
    public function logVisit(?array $user, array $cart): int
    {
        $hash = $this->sessionHash();
        if ($hash === '') {
            return 0;
        }

        [$items, $value] = $this->cartSnapshot($cart);
        $encoded = $items === [] ? null : json_encode($items, JSON_UNESCAPED_UNICODE);

        $since = date('Y-m-d H:i:s', time() - self::DEDUPE_MINUTES * 60);
        $recentId = (int)$this->fetchColumn(
            "SELECT id FROM {$this->table} WHERE session_hash = ? AND created_at >= ? ORDER BY id DESC LIMIT 1",
            [$hash, $since]
        );
        if ($recentId > 0) {
            $this->query(
                "UPDATE {$this->table} SET cart_items = ?, cart_value = ? WHERE id = ?",
                [$encoded, round(max(0.0, $value), 2), $recentId]
            );
            return 0;
        }

        $this->insertRecord($this->table, [
            'user_id'      => $user !== null ? (int)$user['id'] : null,
            'session_hash' => $hash,
            'ip'           => $this->clientIp(),
            'referrer'     => $this->referrer(),
            'cart_value'   => round(max(0.0, $value), 2),
            'cart_items'   => $encoded,
        ]);
        return 1;
    }

    /**
     * Moment-in-time cart snapshot for the log row: product id + both
     * locale names + quantity (so the dashboard shows names in its own
     * language), plus the cart total. One query, capped — the log is a
     * record, not a live join.
     */
    private function cartSnapshot(array $cart): array
    {
        $qtyByProduct = [];
        $value = 0.0;
        foreach ($cart as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $qtyByProduct[$pid] = ($qtyByProduct[$pid] ?? 0) + $qty;
            $value += (float)($item['current_price'] ?? 0) * $qty;
            if (count($qtyByProduct) >= 10) {
                break;
            }
        }
        if ($qtyByProduct === []) {
            return [[], 0.0];
        }

        $placeholders = implode(',', array_fill(0, count($qtyByProduct), '?'));
        $rows = $this->fetchAll(
            "SELECT id, name_en, name_bn FROM " . table('products') . " WHERE id IN ({$placeholders})",
            array_keys($qtyByProduct)
        );
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['id']] = $row;
        }

        $items = [];
        foreach ($qtyByProduct as $pid => $qty) {
            if (!isset($byId[$pid])) {
                continue; // product deleted — skip, don't invent a name
            }
            $items[] = [
                'id'  => $pid,
                'en'  => (string)$byId[$pid]['name_en'],
                'bn'  => (string)$byId[$pid]['name_bn'],
                'qty' => $qty,
            ];
        }
        return [$items, $value];
    }

    /**
     * The session that just placed an order converted — flag its visits
     * (called after the order transaction commits). Returns rows flagged.
     */
    public function markConverted(): int
    {
        $hash = $this->sessionHash();
        if ($hash === '') {
            return 0;
        }
        $since = date('Y-m-d H:i:s', strtotime('-' . self::CONVERT_WINDOW_DAYS . ' days'));
        $stmt = $this->query(
            "UPDATE {$this->table} SET converted = 1
             WHERE session_hash = ? AND converted = 0 AND created_at >= ?",
            [$hash, $since]
        );
        return $stmt->rowCount();
    }

    /**
     * Funnel totals (all-time — tracking began with this feature):
     * reached checkout, guests vs logged-in, ordered, abandoned, rate.
     */
    public function funnel(): array
    {
        $row = $this->fetch(
            "SELECT COUNT(*) AS visits,
                    COALESCE(SUM(user_id IS NULL), 0)      AS guests,
                    COALESCE(SUM(user_id IS NOT NULL), 0)  AS members,
                    COALESCE(SUM(converted = 1), 0)        AS ordered,
                    COALESCE(SUM(converted = 1 AND user_id IS NULL), 0) AS ordered_guests
             FROM {$this->table}"
        );

        $visits        = (int)($row['visits'] ?? 0);
        $guests        = (int)($row['guests'] ?? 0);
        $ordered       = (int)($row['ordered'] ?? 0);
        $orderedGuests = (int)($row['ordered_guests'] ?? 0);

        return [
            'visits'          => $visits,
            'guests'          => $guests,
            'members'         => (int)($row['members'] ?? 0),
            'ordered'         => $ordered,
            'ordered_guests'  => $orderedGuests,
            'abandoned'       => $visits - $ordered,
            'abandoned_guests'=> $guests - $orderedGuests,
            'rate'            => $visits > 0 ? round(($ordered / $visits) * 100, 1) : 0.0,
        ];
    }

    /** Latest unregistered visits for the dashboard's recent-guests list. */
    public function recentGuests(int $limit = 6): array
    {
        return $this->fetchAll(
            "SELECT ip, cart_value, cart_items, converted, created_at, referrer
             FROM {$this->table}
             WHERE user_id IS NULL
             ORDER BY created_at DESC, id DESC
             LIMIT ?",
            [max(1, $limit)]
        );
    }

    private function clientIp(): string
    {
        return mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }

    private function referrer(): string
    {
        return mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 512);
    }
}

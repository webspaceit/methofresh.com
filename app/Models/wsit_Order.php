<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_Order extends wsit_Model
{
    protected string $table = 'orders';
    private string $itemsTable;
    private string $usersTable;

    public function __construct()
    {
        parent::__construct();
        $this->itemsTable = table('order_items');
        $this->usersTable = table('users');
    }

    public function create(array $data): int
    {
        return (int)$this->insertRecord($this->table, $data);
    }

    public function findWithItems(int $id, int|null $userId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $params = [$id];
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        $order = $this->fetch($sql, $params);
        if ($order === null) {
            return null;
        }
        $order['items'] = $this->fetchAll("SELECT * FROM {$this->itemsTable} WHERE order_id = ?", [$id]);
        return $order;
    }

    public function findByNumber(string $number): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE order_number = ?", [$number]);
    }

    /**
     * Latest order for a phone number. Stored numbers are local format
     * (01712345678) while messaging channels use E.164 (8801712345678),
     * so match on the last 10 digits.
     */
    public function latestByPhone(string $phone): ?array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $last10 = substr($digits, -10);
        if (strlen($last10) < 10) {
            return null;
        }

        return $this->fetch(
            "SELECT * FROM {$this->table} WHERE shipping_phone LIKE ? ORDER BY id DESC LIMIT 1",
            ['%' . $last10]
        );
    }

    /**
     * Find an order by its order_number and hydrate its line items.
     * Used by the order-success page so the Facebook Pixel Purchase event
     * can fire with the full contents list.
     */
    public function findWithItemsByNumber(string $number): ?array
    {
        $order = $this->findByNumber($number);
        if ($order === null) {
            return null;
        }
        $order['items'] = $this->fetchAll(
            "SELECT * FROM {$this->itemsTable} WHERE order_id = ?",
            [(int)$order['id']]
        );
        return $order;
    }

    public function history(int $userId, int $limit = 20): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}",
            [$userId]
        );
    }

    public function generateNumber(): string
    {
        return 'MF-' . date('ymd') . '-' . strtoupper(substr(uniqid('', true), -6));
    }

    public function all(string $status = '', string $search = '', int $perPage = 20, int $offset = 0): array
    {
        $sql = "SELECT o.*, u.name AS user_name FROM {$this->table} o
                LEFT JOIN {$this->usersTable} u ON u.id = o.user_id
                WHERE 1 = 1";
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $sql .= ' AND o.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $sql .= ' AND (o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ?)';
            $like = "%{$search}%";
            array_push($params, $like, $like, $like);
        }
        $sql .= ' ORDER BY o.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = $offset;
        return $this->fetchAll($sql, $params);
    }

    /**
     * Lightweight results for the admin header live-search dropdown.
     * Matches the same fields the orders page search uses, newest first.
     */
    public function searchLight(string $q, int $limit = 8): array
    {
        $q     = trim($q);
        $like  = "%{$q}%";
        $limit = max(1, (int)$limit);
        return $this->fetchAll(
            "SELECT o.id, o.order_number, o.shipping_name, o.shipping_phone,
                    o.shipping_city, o.total, o.status, o.created_at
             FROM {$this->table} o
             WHERE o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ?
             ORDER BY o.created_at DESC
             LIMIT {$limit}",
            [$like, $like, $like]
        );
    }

    public function countAll(string $status = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }
        return (int)$this->fetchColumn($sql, $params);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->updateRecord($this->table, ['status' => $status], ['id' => $id]);
    }

    /**
     * Orders awaiting delivery that have not received a delivery reminder in
     * the last 20 hours — used by the daily messaging cron.
     */
    public function deliveryReminderCandidates(int $limit = 50): array
    {
        $logs  = table('message_logs');
        $limit = max(1, $limit);

        return $this->fetchAll(
            "SELECT o.* FROM {$this->table} o
             WHERE o.status IN ('processing', 'shipped')
               AND o.created_at <= NOW()
               AND NOT EXISTS (
                   SELECT 1 FROM {$logs} l
                   WHERE l.order_id = o.id
                     AND l.template = 'delivery_reminder'
                     AND l.direction = 'outbound'
                     AND l.created_at >= DATE_SUB(NOW(), INTERVAL 20 HOUR)
               )
             ORDER BY o.created_at ASC
             LIMIT {$limit}"
        );
    }

    public function cancelPendingForUser(int $id, int $userId): ?array
    {
        $this->beginTransaction();
        try {
            $order = $this->fetch(
                "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ? AND status = 'pending' AND (payment_method = 'cod' OR transaction_id IS NULL) FOR UPDATE",
                [$id, $userId]
            );
            if ($order === null) {
                $this->rollBack();
                return null;
            }
            $items = $this->fetchAll("SELECT product_id, quantity FROM {$this->itemsTable} WHERE order_id = ?", [$id]);
            $this->updateRecord($this->table, ['status' => 'cancelled'], ['id' => $id]);
            $productsTable = table('products');
            foreach ($items as $item) {
                if (!empty($item['product_id'])) {
                    $this->query(
                        "UPDATE {$productsTable} SET stock = stock + ?, sold = GREATEST(0, sold - ?) WHERE id = ?",
                        [(int)$item['quantity'], (int)$item['quantity'], (int)$item['product_id']]
                    );
                }
            }
            $order['items'] = $this->fetchAll("SELECT * FROM {$this->itemsTable} WHERE order_id = ?", [$id]);
            $this->commit();
            return $order;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function revenue(): float
    {
        return (float)$this->fetchColumn(
            "SELECT COALESCE(SUM(total), 0) FROM {$this->table} WHERE status NOT IN ('cancelled')"
        );
    }

    public function recent(int $limit = 6): array
    {
        return $this->fetchAll(
            "SELECT o.*, u.name AS user_name FROM {$this->table} o
             LEFT JOIN {$this->usersTable} u ON u.id = o.user_id
             ORDER BY o.created_at DESC LIMIT {$limit}"
        );
    }

    /**
     * Recent orders, each carrying a ready-to-print 'items_summary' built from
     * the localized order_items rows (e.g. "খেজুরের গুড় (২টি), পাটালি (১টি)").
     */
    public function recentWithItems(int $limit = 6): array
    {
        $orders = $this->recent($limit);

        foreach ($orders as &$order) {
            $items = $this->fetchAll(
                "SELECT * FROM {$this->itemsTable} WHERE order_id = ? ORDER BY id ASC",
                [$order['id']]
            );
            $names = [];
            foreach ($items as $item) {
                $name = trim((string)($item['product_name_bn'] ?? ''));
                if ($name === '') {
                    $name = trim((string)($item['product_name'] ?? ''));
                }
                $qty = (int)($item['quantity'] ?? 1);
                $names[] = $qty > 1 ? $name . ' (' . $qty . 'টি)' : $name;
            }
            $order['items_summary'] = implode(', ', $names);
        }
        unset($order);

        return $orders;
    }

    /**
     * Sum of order totals (excluding cancelled) recorded at/after $from.
     * When $to is provided it acts as an exclusive upper bound.
     */
    public function revenueBetween(string $from, string $to = ''): float
    {
        $sql = "SELECT COALESCE(SUM(total), 0) FROM {$this->table}
                WHERE status NOT IN ('cancelled') AND created_at >= ?";
        $params = [$from];
        if ($to !== '') {
            $sql .= ' AND created_at < ?';
            $params[] = $to;
        }
        return (float)$this->fetchColumn($sql, $params);
    }

    /**
     * Number of orders created at/after $from.
     */
    public function countBetween(string $from): int
    {
        return (int)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE created_at >= ?",
            [$from]
        );
    }

    /**
     * Average order value across non-cancelled orders (0 when none exist).
     */
    public function averageOrderValue(): float
    {
        return (float)$this->fetchColumn(
            "SELECT COALESCE(AVG(total), 0) FROM {$this->table} WHERE status NOT IN ('cancelled')"
        );
    }

    /**
     * Delivery completion stats: ['delivered' => n, 'total' => n, 'rate' => 0-100].
     * 'total' counts every non-cancelled order; 'rate' is delivered / total.
     */
    public function deliveryRate(): array
    {
        $row = $this->fetch(
            "SELECT
                COALESCE(SUM(status = 'delivered'), 0) AS delivered,
                COALESCE(SUM(status <> 'cancelled'), 0) AS total
             FROM {$this->table}"
        );
        $delivered = (int)($row['delivered'] ?? 0);
        $total     = (int)($row['total'] ?? 0);
        $rate = $total > 0 ? round(($delivered / $total) * 100, 1) : 0.0;

        return ['delivered' => $delivered, 'total' => $total, 'rate' => $rate];
    }

    /**
     * Per-day revenue + order counts for the trailing $days (including today).
     * Days without orders are filled with zeroes so chart series stay aligned.
     */
    public function dailySeries(int $days = 7): array
    {
        $rows = $this->fetchAll(
            "SELECT DATE(created_at) AS d,
                    COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total END), 0) AS revenue,
                    COALESCE(SUM(status <> 'cancelled'), 0) AS orders
             FROM {$this->table}
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY d ASC",
            [$days - 1]
        );

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row['d']] = $row;
        }

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $out[] = [
                'date'    => $d,
                'revenue' => (float)($byDate[$d]['revenue'] ?? 0),
                'orders'  => (int)($byDate[$d]['orders'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Revenue & non-cancelled order counts bucketed over the last $buckets
     * units of a period (daily / weekly / monthly / yearly). Gaps are filled
     * with zeros so charts always have a full series.
     *
     * Each row: ['bucket' => 'Y-m-d' canonical bucket start, 'revenue' => float, 'orders' => int]
     */
    public function timeSeries(string $period = 'daily', int $buckets = 7): array
    {
        $buckets = max(1, $buckets);
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            $period = 'daily';
        }

        switch ($period) {
            case 'weekly':
                $expr  = 'DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY))';
                $limit = "DATE_SUB(CURDATE(), INTERVAL {$buckets} WEEK)";
                break;
            case 'monthly':
                $expr  = "DATE_FORMAT(created_at, '%Y-%m-01')";
                $limit = "DATE_SUB(CURDATE(), INTERVAL {$buckets} MONTH)";
                break;
            case 'yearly':
                $expr  = "DATE_FORMAT(created_at, '%Y-01-01')";
                $limit = "DATE_SUB(CURDATE(), INTERVAL {$buckets} YEAR)";
                break;
            default:
                $expr  = 'DATE(created_at)';
                $limit = 'DATE_SUB(CURDATE(), INTERVAL ' . ($buckets - 1) . ' DAY)';
                break;
        }

        $rows = $this->fetchAll(
            "SELECT {$expr} AS bucket,
                    COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total END), 0) AS revenue,
                    COALESCE(SUM(status <> 'cancelled'), 0) AS orders
             FROM {$this->table}
             WHERE created_at >= {$limit} AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
             GROUP BY bucket
             ORDER BY bucket ASC"
        );
        $byBucket = [];
        foreach ($rows as $row) {
            $byBucket[(string)$row['bucket']] = $row;
        }

        $out = [];
        for ($i = $buckets - 1; $i >= 0; $i--) {
            if ($period === 'weekly') {
                $bucket = date('Y-m-d', strtotime('monday this week', strtotime('-' . $i . ' weeks')));
            } elseif ($period === 'monthly') {
                $bucket = date('Y-m-01', strtotime('-' . $i . ' months'));
            } elseif ($period === 'yearly') {
                $bucket = date('Y-01-01', strtotime('-' . $i . ' years'));
            } else {
                $bucket = date('Y-m-d', strtotime('-' . $i . ' days'));
            }
            $row = $byBucket[$bucket] ?? null;
            $out[] = [
                'bucket'  => $bucket,
                'revenue' => $row !== null ? (float)$row['revenue'] : 0.0,
                'orders'  => $row !== null ? (int)$row['orders'] : 0,
            ];
        }
        return $out;
    }

    /**
     * Payment method totals across non-cancelled orders.
     * Returns rows of ['method' => string, 'count' => int, 'total' => float].
     */
    public function paymentMethodTotals(): array
    {
        $rows = $this->fetchAll(
            "SELECT payment_method AS method,
                    COUNT(*) AS cnt,
                    COALESCE(SUM(total), 0) AS total
             FROM {$this->table}
             WHERE status NOT IN ('cancelled')
             GROUP BY payment_method
             ORDER BY cnt DESC"
        );

        return array_map(static function (array $row): array {
            return [
                'method' => (string)$row['method'],
                'count'  => (int)$row['cnt'],
                'total'  => (float)$row['total'],
            ];
        }, $rows);
    }

    /**
     * Counts of orders per status (zeroes for statuses without orders).
     */
    public function statusCounts(): array
    {
        $base = ['pending' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];
        $rows = $this->fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM {$this->table} GROUP BY status"
        );
        foreach ($rows as $row) {
            $status = (string)$row['status'];
            if (array_key_exists($status, $base)) {
                $base[$status] = (int)$row['cnt'];
            }
        }
        return $base;
    }

    // -----------------------------------------------------------------------
    // Customer analytics (guest orders + registered users are both "customers",
    // identified by COALESCE(shipping_email, shipping_phone)).
    // -----------------------------------------------------------------------

    private function customerKeyExpr(): string
    {
        return "COALESCE(NULLIF(shipping_email, ''), shipping_phone)";
    }

    /**
     * Cohort summary: total customers, new this month, repeat customers,
     * order count, revenue and average spend per customer (non-cancelled).
     */
    public function customerMetrics(): array
    {
        $key = $this->customerKeyExpr();

        $row = $this->fetch(
            "SELECT
                COUNT(DISTINCT {$key}) AS customers,
                COALESCE(SUM(status <> 'cancelled'), 0) AS orders,
                COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total END), 0) AS revenue
             FROM {$this->table}"
        );

        $customers = (int)($row['customers'] ?? 0);
        $orders    = (int)($row['orders'] ?? 0);
        $revenue   = (float)($row['revenue'] ?? 0);

        $groups = $this->fetchAll(
            "SELECT {$key} AS ck, COUNT(*) AS cnt
             FROM {$this->table}
             WHERE status <> 'cancelled'
             GROUP BY {$key}"
        );
        $repeat = 0;
        foreach ($groups as $group) {
            if ((int)$group['cnt'] > 1) {
                $repeat++;
            }
        }

        $newThisMonth = (int)$this->fetchColumn(
            "SELECT COUNT(DISTINCT {$key}) FROM {$this->table}
             WHERE created_at >= ? AND status <> 'cancelled'",
            [date('Y-m-01 00:00:00')]
        );

        return [
            'total'          => $customers,
            'new_this_month' => $newThisMonth,
            'repeat'         => $repeat,
            'orders'         => $orders,
            'revenue'        => $revenue,
            'avg_spend'      => $customers > 0 ? round($revenue / $customers, 2) : 0.0,
        ];
    }

    /**
     * Highest-spending customers: name, contact, city, order count, revenue.
     */
    public function topCustomers(int $limit = 8): array
    {
        $key = $this->customerKeyExpr();
        return $this->fetchAll(
            "SELECT {$key} AS customer_key,
                    MAX(NULLIF(shipping_name, '')) AS name,
                    MAX(NULLIF(shipping_email, '')) AS email,
                    MAX(NULLIF(shipping_phone, '')) AS phone,
                    MAX(NULLIF(shipping_city, '')) AS city,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total), 0) AS revenue
             FROM {$this->table}
             WHERE status <> 'cancelled'
             GROUP BY {$key}
             ORDER BY revenue DESC, orders DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Customer/order/revenue distribution grouped by shipping city.
     */
    public function customersByCity(int $limit = 8): array
    {
        $key = $this->customerKeyExpr();
        return $this->fetchAll(
            "SELECT NULLIF(shipping_city, '') AS city,
                    COUNT(DISTINCT {$key}) AS customers,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total), 0) AS revenue
             FROM {$this->table}
             WHERE status <> 'cancelled' AND shipping_city <> ''
             GROUP BY shipping_city
             ORDER BY customers DESC, revenue DESC
             LIMIT ?",
            [$limit]
        );
    }

    // -----------------------------------------------------------------------
    // Delivery tracking
    // -----------------------------------------------------------------------

    /**
     * Shipments still awaiting delivery — pending first (highest priority),
     * then processing, then in transit; oldest first within each group.
     */
    public function deliveryQueue(int $limit = 15): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status IN ('pending', 'processing', 'shipped')
             ORDER BY FIELD(status, 'pending', 'processing', 'shipped'), created_at ASC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Most recently delivered orders.
     */
    public function recentlyDelivered(int $limit = 15): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE status = 'delivered'
             ORDER BY created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Parcel counts per delivery region (non-cancelled orders grouped by
     * shipping city into the mockup's four regions).
     *
     * @return array{ dhaka: array{parcels:int,delivered:int}, chittagong: array{parcels:int,delivered:int}, sylhet_rajshahi: array{parcels:int,delivered:int}, other: array{parcels:int,delivered:int} }
     */
    public function regionBreakdown(): array
    {
        $rows = $this->fetchAll(
            "SELECT
                CASE
                    WHEN shipping_city LIKE '%ঢাকা%' OR shipping_city LIKE '%dhaka%'
                         OR shipping_city LIKE '%গাজীপুর%' OR shipping_city LIKE '%gazipur%'
                         OR shipping_city LIKE '%নারায়ণগঞ্জ%' OR shipping_city LIKE '%narayanganj%'
                         OR shipping_city LIKE '%সাভার%' OR shipping_city LIKE '%savar%'
                    THEN 'dhaka'
                    WHEN shipping_city LIKE '%চট্টগ্রাম%' OR shipping_city LIKE '%চাটগাঁ%' OR shipping_city LIKE '%চিটাগং%'
                         OR shipping_city LIKE '%chattogram%' OR shipping_city LIKE '%chittagong%' OR shipping_city LIKE '%chitagong%'
                         OR shipping_city LIKE '%কক্সবাজার%' OR shipping_city LIKE '%cox%'
                         OR shipping_city LIKE '%কুমিল্লা%' OR shipping_city LIKE '%cumilla%'
                    THEN 'chittagong'
                    WHEN shipping_city LIKE '%সিলেট%' OR shipping_city LIKE '%sylhet%'
                         OR shipping_city LIKE '%রাজশাহী%' OR shipping_city LIKE '%rajshahi%'
                    THEN 'sylhet_rajshahi'
                    ELSE 'other'
                END AS region,
                COUNT(*) AS parcels,
                COALESCE(SUM(status = 'delivered'), 0) AS delivered
             FROM {$this->table}
             WHERE status <> 'cancelled' AND shipping_city <> ''
             GROUP BY region"
        );

        $base = [
            'dhaka'          => ['parcels' => 0, 'delivered' => 0],
            'chittagong'     => ['parcels' => 0, 'delivered' => 0],
            'sylhet_rajshahi'=> ['parcels' => 0, 'delivered' => 0],
            'other'          => ['parcels' => 0, 'delivered' => 0],
        ];
        foreach ($rows as $row) {
            $key = (string)($row['region'] ?? 'other');
            if (!array_key_exists($key, $base)) {
                $key = 'other';
            }
            $base[$key]['parcels']   = (int)$row['parcels'];
            $base[$key]['delivered'] = (int)$row['delivered'];
        }
        return $base;
    }
}

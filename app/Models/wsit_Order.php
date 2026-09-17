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
}
<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_ContactMessage extends wsit_Model
{
    protected string $table = 'contact_messages';
    private string $usersTable;

    public function __construct()
    {
        parent::__construct();
        $this->usersTable = table('users');
    }

    public function create(array $data): int
    {
        return (int)$this->insertRecord($this->table, $data);
    }

    public function all(int $perPage = 20, int $offset = 0): array
    {
        $sql = "SELECT cm.*, u.name AS user_name FROM {$this->table} cm
                LEFT JOIN {$this->usersTable} u ON u.id = cm.user_id
                ORDER BY cm.created_at DESC LIMIT ? OFFSET ?";
        return $this->fetchAll($sql, [$perPage, $offset]);
    }

    public function countAll(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table}");
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function markAsRead(int $id): void
    {
        $this->updateRecord($this->table, ['is_read' => 1], ['id' => $id]);
    }

    public function unreadCount(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE is_read = 0");
    }
}

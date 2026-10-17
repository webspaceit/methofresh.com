<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_ContactMessage extends wsit_Model
{
    protected string $table = 'contact_messages';

    public function create(array $data): int
    {
        return (int)$this->insertRecord($this->table, $data);
    }

    public function all(int $perPage = 20, int $offset = 0): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );
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

    public function deleteById(int $id): void
    {
        $this->deleteRecord($this->table, ['id' => $id]);
    }

    public function unreadCount(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE is_read = 0");
    }
}

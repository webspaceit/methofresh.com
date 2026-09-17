<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_User extends wsit_Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE email = ?", [$email]);
    }

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function create(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return (int)$this->insertRecord($this->table, $data);
    }

    public function all(string $search = '', string $orderBy = 'id DESC'): array
    {
        $sql = "SELECT id, name, email, phone, city, is_admin, created_at FROM {$this->table}";
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ?';
            $params = ["%{$search}%", "%{$search}%"];
        }
        $sql .= " ORDER BY {$orderBy}";
        return $this->fetchAll($sql, $params);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE is_admin = 0";
        return (int)$this->fetchColumn($sql);
    }
}
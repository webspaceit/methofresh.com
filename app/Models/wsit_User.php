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

    public function updatePassword(int $id, string $password): void
    {
        $this->updateRecord($this->table, ['password' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $id]);
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

    public function allPaginated(string $search = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT id, name, email, phone, city, is_admin, created_at FROM {$this->table}";
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?';
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }
        $sql .= ' ORDER BY is_admin DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
        return $this->fetchAll($sql, $params);
    }

    public function countAll(string $search = ''): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?';
            $params = ["%{$search}%", "%{$search}%", "%{$search}%"];
        }
        return (int)$this->fetchColumn($sql, $params);
    }

    public function countAdmins(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE is_admin = 1");
    }

    public function findByEmailExcluding(string $email, int $excludeId): ?array
    {
        return $this->fetch(
            "SELECT id FROM {$this->table} WHERE email = ? AND id != ?",
            [$email, $excludeId]
        );
    }

    public function updateProfile(int $id, array $data): void
    {
        $this->updateRecord($this->table, $data, ['id' => $id]);
    }

    public function deleteById(int $id): void
    {
        $this->deleteRecord($this->table, ['id' => $id]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE is_admin = 0";
        return (int)$this->fetchColumn($sql);
    }
}

<?php

namespace App\Core;

use App\Core\wsit_Database;
use PDO;

abstract class wsit_Model
{
    protected PDO $pdo;
    protected string $table = '';

    public function __construct()
    {
        $this->pdo = app()->get('db')->get();
        if ($this->table !== '') {
            $this->table = table($this->table);
        }
    }

    protected function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    protected function insertRecord(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    protected function updateRecord(string $table, array $data, array $where): int
    {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $conditions = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$conditions}";
        return $this->query($sql, array_merge(array_values($data), array_values($where)))->rowCount();
    }

    protected function deleteRecord(string $table, array $where): int
    {
        $conditions = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($where)));
        $sql = "DELETE FROM {$table} WHERE {$conditions}";
        return $this->query($sql, array_values($where))->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
}
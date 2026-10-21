<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_PasswordReset extends wsit_Model
{
    protected string $table = 'password_resets';

    public function recentlyIssued(int $userId): bool
    {
        return (bool)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 2 MINUTE)",
            [$userId]
        );
    }

    public function issue(int $userId, string $tokenHash): void
    {
        $this->query("DELETE FROM {$this->table} WHERE user_id = ? OR expires_at <= NOW()", [$userId]);
        $this->query(
            "INSERT INTO {$this->table} (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))",
            [$userId, $tokenHash]
        );
    }

    public function findValid(string $tokenHash): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE token_hash = ? AND expires_at > NOW()", [$tokenHash]);
    }

    public function findValidForUpdate(string $tokenHash): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE", [$tokenHash]);
    }

    public function consume(int $id): void
    {
        $this->deleteRecord($this->table, ['id' => $id]);
    }
}

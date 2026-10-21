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

    /**
     * Store a one-time token for the user.
     *
     * $hours controls how long the link stays valid: 1 hour for a regular
     * "forgot password" request, longer for the "set your password" link
     * that travels with an order confirmation (the customer may open it
     * days later).
     */
    public function issue(int $userId, string $tokenHash, int $hours = 1): void
    {
        $hours = max(1, min(720, (int)$hours));
        $this->query("DELETE FROM {$this->table} WHERE user_id = ? OR expires_at <= NOW()", [$userId]);
        $this->query(
            "INSERT INTO {$this->table} (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL {$hours} HOUR))",
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

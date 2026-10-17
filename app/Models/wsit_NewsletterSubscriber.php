<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_NewsletterSubscriber extends wsit_Model
{
    protected string $table = 'newsletter_subscribers';

    /**
     * Store (or reactivate) a subscriber.
     *
     * @return bool true when the email was already subscribed, false on a new row.
     */
    public function subscribe(string $email, string $locale = 'en'): bool
    {
        $email = strtolower(trim($email));
        $existing = $this->fetch("SELECT id FROM {$this->table} WHERE email = ?", [$email]);

        if ($existing !== null) {
            // Already known — make sure it is active and refresh its locale.
            $this->updateRecord($this->table, [
                'is_active' => 1,
                'locale'    => $locale,
            ], ['id' => (int)$existing['id']]);
            return true;
        }

        $this->insertRecord($this->table, [
            'email'     => $email,
            'locale'    => $locale,
            'is_active' => 1,
        ]);
        return false;
    }

    public function all(int $perPage = 30, int $offset = 0): array
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

    public function countActive(): int
    {
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table} WHERE is_active = 1");
    }

    public function deleteById(int $id): void
    {
        $this->deleteRecord($this->table, ['id' => $id]);
    }
}

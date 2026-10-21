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

        // A password the customer chose means the account is fully live, so
        // it is no longer "waiting" for one. Best effort: the column may not
        // exist yet on an un-migrated database, and the password itself has
        // already been stored by this point.
        try {
            $this->query("UPDATE {$this->table} SET auto_registered = 0 WHERE id = ?", [$id]);
        } catch (\Throwable) {
        }
    }

    public function create(array $data): int
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return (int)$this->insertRecord($this->table, $data);
    }

    /**
     * Create the account for a guest order.
     *
     * The customer never picked a password, so a random one is stored that
     * nobody knows and the account is flagged auto_registered. The shop then
     * sends them a "set your password" link, and until they use it the
     * account can still order without signing in (otherwise a customer who
     * never chose a password would be locked out of their own shop).
     *
     * Returns the new user id, or 0 when the account could not be created.
     */
    public function createForGuestOrder(array $data): int
    {
        $email = strtolower(trim((string)($data['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 0;
        }
        try {
            return $this->create([
                'name'            => trim((string)($data['name'] ?? '')),
                'email'           => $email,
                'phone'           => trim((string)($data['phone'] ?? '')),
                'address'         => trim((string)($data['address'] ?? '')),
                'city'            => trim((string)($data['city'] ?? '')),
                'password'        => bin2hex(random_bytes(24)),
                'is_admin'        => 0,
                'auto_registered' => 1,
            ]);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * True when the account was created automatically at checkout and the
     * customer still has no password of their own.
     */
    public function isAutoRegistered(?array $user): bool
    {
        return $user !== null && (int)($user['auto_registered'] ?? 0) === 1;
    }

    /**
     * Guest e-mail addresses that have orders but no account.
     *
     * Used by Dashboard -> Users to create the missing accounts in one go
     * (for orders placed before guest checkout created accounts).
     */
    public function guestOrderEmails(): array
    {
        $orders = table('orders');
        return $this->fetchAll(
            "SELECT o.shipping_email AS email,
                    COUNT(*) AS orders,
                    MAX(NULLIF(o.shipping_name, '')) AS name,
                    MAX(NULLIF(o.shipping_phone, '')) AS phone,
                    MAX(NULLIF(o.shipping_city, '')) AS city,
                    MAX(NULLIF(o.shipping_address, '')) AS address,
                    MIN(o.created_at) AS first_order_at
             FROM {$orders} o
             WHERE o.user_id IS NULL
               AND o.status <> 'cancelled'
               AND o.shipping_email IS NOT NULL
               AND o.shipping_email <> ''
             GROUP BY o.shipping_email
             ORDER BY orders DESC, first_order_at ASC"
        );
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
        $sql = "SELECT id, name, email, phone, city, is_admin, auto_registered, created_at FROM {$this->table}";
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

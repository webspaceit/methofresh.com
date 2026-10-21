<?php

namespace App\Models;

use App\Core\wsit_Model;

/**
 * One thread per WhatsApp phone number or Messenger PSID.
 *
 * `status` drives the chatbot: 'open' = bot replies, 'human' = an agent has
 * taken over (bot stays quiet and only logs), 'closed' = archived.
 */
class wsit_Conversation extends wsit_Model
{
    protected string $table = 'conversations';

    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function findByPhone(string $phone): ?array
    {
        return $this->fetch(
            "SELECT * FROM {$this->table} WHERE channel = 'whatsapp' AND phone = ? LIMIT 1",
            [$phone]
        );
    }

    public function findByPsid(string $psid): ?array
    {
        return $this->fetch(
            "SELECT * FROM {$this->table} WHERE channel = 'messenger' AND psid = ? LIMIT 1",
            [$psid]
        );
    }

    /**
     * A Messenger thread that has been linked to a phone number (the customer
     * shared an order number or phone in chat). Order updates can mirror here.
     */
    public function findMessengerByPhone(string $phone): ?array
    {
        return $this->fetch(
            "SELECT * FROM {$this->table} WHERE channel = 'messenger' AND phone = ? LIMIT 1",
            [$phone]
        );
    }

    /**
     * Link a Messenger thread to a verified order phone so future order
     * events can be mirrored to that customer.
     */
    public function linkPhone(int $id, string $phone): void
    {
        $phone = trim($phone);
        if ($phone === '') {
            return;
        }
        $existing = $this->findMessengerByPhone($phone);
        if ($existing !== null && (int)$existing['id'] !== $id) {
            return;
        }
        try {
            $this->updateRecord($this->table, ['phone' => mb_substr($phone, 0, 30)], ['id' => $id]);
        } catch (\Throwable) {
            // Unique clash with another thread — leave the link out.
        }
    }

    /**
     * Find-or-create the thread for an inbound message. Keeps the last
     * activity timestamps fresh and remembers the display name Meta sends.
     */
    public function touchInbound(string $channel, string $identifier, string $displayName = ''): array
    {
        $existing = $channel === 'messenger'
            ? $this->findByPsid($identifier)
            : $this->findByPhone($identifier);

        $now = date('Y-m-d H:i:s');

        if ($existing !== null) {
            $data = [
                'last_message_at' => $now,
                'last_inbound_at' => $now,
                'status'          => $existing['status'] === 'closed' ? 'open' : $existing['status'],
            ];
            if ($displayName !== '' && (string)$existing['display_name'] === '') {
                $data['display_name'] = mb_substr($displayName, 0, 120);
            }
            $this->updateRecord($this->table, $data, ['id' => (int)$existing['id']]);
            return array_merge($existing, $data);
        }

        $data = [
            'channel'         => $channel,
            'phone'           => $channel === 'messenger' ? null : mb_substr($identifier, 0, 30),
            'psid'            => $channel === 'messenger' ? mb_substr($identifier, 0, 60) : null,
            'display_name'    => $displayName !== '' ? mb_substr($displayName, 0, 120) : null,
            'status'          => 'open',
            'last_message_at' => $now,
            'last_inbound_at' => $now,
        ];

        try {
            $data['id'] = $this->insertRecord($this->table, $data);
        } catch (\Throwable) {
            // Concurrent webhook delivery created it first — re-read.
            $existing = $channel === 'messenger'
                ? $this->findByPsid($identifier)
                : $this->findByPhone($identifier);
            if ($existing === null) {
                throw new \RuntimeException('Could not create conversation');
            }
            $this->updateRecord($this->table, ['last_message_at' => $now, 'last_inbound_at' => $now], ['id' => (int)$existing['id']]);
            $data = array_merge($existing, ['last_message_at' => $now, 'last_inbound_at' => $now]);
        }

        return $data;
    }

    public function touchOutbound(int $id): void
    {
        $this->updateRecord($this->table, ['last_message_at' => date('Y-m-d H:i:s')], ['id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['open', 'closed', 'human'], true)) {
            return;
        }
        $this->updateRecord($this->table, ['status' => $status], ['id' => $id]);
    }

    /**
     * True when the customer messaged within Meta's 24-hour service window,
     * so a free-form reply is allowed. Outside it only templates may be sent.
     */
    public function withinServiceWindow(array $conversation): bool
    {
        $last = (string)($conversation['last_inbound_at'] ?? '');
        if ($last === '') {
            return false;
        }
        $ts = strtotime($last);
        return $ts !== false && $ts >= time() - 86400;
    }

    public function all(string $status = '', int $limit = 30, int $offset = 0): array
    {
        $where = '';
        $params = [];
        if (in_array($status, ['open', 'human', 'closed'], true)) {
            $where = ' WHERE status = ?';
            $params[] = $status;
        }
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return $this->fetchAll(
            "SELECT * FROM {$this->table}{$where}
             ORDER BY COALESCE(last_message_at, created_at) DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAll(string $status = ''): int
    {
        if (in_array($status, ['open', 'human', 'closed'], true)) {
            return (int)$this->fetchColumn(
                "SELECT COUNT(*) FROM {$this->table} WHERE status = ?",
                [$status]
            );
        }
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table}");
    }

    /**
     * @return array{open:int,human:int,closed:int,total:int}
     */
    public function countByStatus(): array
    {
        $row = $this->fetch(
            "SELECT
                COALESCE(SUM(status = 'open'), 0)   AS open,
                COALESCE(SUM(status = 'human'), 0)  AS human,
                COALESCE(SUM(status = 'closed'), 0) AS closed,
                COUNT(*) AS total
             FROM {$this->table}"
        );

        return [
            'open'   => (int)($row['open'] ?? 0),
            'human'  => (int)($row['human'] ?? 0),
            'closed' => (int)($row['closed'] ?? 0),
            'total'  => (int)($row['total'] ?? 0),
        ];
    }
}

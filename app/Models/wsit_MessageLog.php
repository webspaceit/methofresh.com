<?php

namespace App\Models;

use App\Core\wsit_Model;

/**
 * Outbound/inbound messaging audit trail for WhatsApp + Messenger.
 *
 * Every API call (success or failure) is persisted here. Inbound webhook
 * messages are stored too so duplicate deliveries can be ignored by the
 * unique message_id index.
 */
class wsit_MessageLog extends wsit_Model
{
    protected string $table = 'message_logs';

    public function logOutbound(
        string $channel,
        string $recipient,
        ?string $messageId,
        array $payload,
        bool $ok,
        ?string $error = null,
        ?int $orderId = null,
        ?string $template = null
    ): int {
        return (int)$this->insertRecord($this->table, [
            'channel'    => $channel,
            'direction'  => 'outbound',
            'recipient'  => mb_substr($recipient, 0, 50),
            'message_id' => $messageId !== null && $messageId !== '' ? mb_substr($messageId, 0, 120) : null,
            'template'   => $template !== null && $template !== '' ? mb_substr($template, 0, 60) : null,
            'order_id'   => $orderId,
            'payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'     => $ok ? 'sent' : 'failed',
            'error'      => $error,
        ]);
    }

    public function logInbound(
        string $channel,
        string $recipient,
        ?string $messageId,
        array $payload
    ): int {
        return (int)$this->insertRecord($this->table, [
            'channel'    => $channel,
            'direction'  => 'inbound',
            'recipient'  => mb_substr($recipient, 0, 50),
            'message_id' => $messageId !== null && $messageId !== '' ? mb_substr($messageId, 0, 120) : null,
            'payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status'     => 'received',
        ]);
    }

    /**
     * True when a webhook event with this provider message id was already
     * stored — Meta retries webhooks, so handlers must be idempotent.
     */
    public function messageIdExists(string $messageId): bool
    {
        if ($messageId === '') {
            return false;
        }
        return (bool)$this->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE message_id = ?",
            [$messageId]
        );
    }

    /**
     * Delivery receipts (sent / delivered / read / failed) update the row
     * that was created when the message was accepted by Meta.
     */
    public function updateStatusByMessageId(string $messageId, string $status, ?string $error = null): void
    {
        if ($messageId === '') {
            return;
        }
        $data = ['status' => mb_substr($status, 0, 30)];
        if ($error !== null && $error !== '') {
            $data['error'] = $error;
        }
        $this->updateRecord($this->table, $data, ['message_id' => $messageId]);
    }

    /**
     * @param array{channel?:string,direction?:string,status?:string,q?:string} $filters
     */
    public function all(array $filters = [], int $limit = 30, int $offset = 0): array
    {
        [$where, $params] = $this->buildWhere($filters);
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        return $this->fetchAll(
            "SELECT * FROM {$this->table}{$where}
             ORDER BY id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    /**
     * @param array{channel?:string,direction?:string,status?:string,q?:string} $filters
     */
    public function countAll(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);
        return (int)$this->fetchColumn("SELECT COUNT(*) FROM {$this->table}{$where}", $params);
    }

    /**
     * Today's counters for the messaging dashboard cards.
     *
     * @return array{outbound:int,inbound:int,failed:int,total:int,delivered:int,read:int}
     */
    public function stats(): array
    {
        $row = $this->fetch(
            "SELECT
                COALESCE(SUM(direction = 'outbound'), 0) AS outbound,
                COALESCE(SUM(direction = 'inbound'), 0)  AS inbound,
                COALESCE(SUM(status = 'failed'), 0)      AS failed,
                COALESCE(SUM(status IN ('delivered','read')), 0) AS delivered,
                COALESCE(SUM(status = 'read'), 0)        AS `read`,
                COUNT(*)                                  AS total
             FROM {$this->table}
             WHERE created_at >= CURDATE()"
        );

        return [
            'outbound'  => (int)($row['outbound'] ?? 0),
            'inbound'   => (int)($row['inbound'] ?? 0),
            'failed'    => (int)($row['failed'] ?? 0),
            'delivered' => (int)($row['delivered'] ?? 0),
            'read'      => (int)($row['read'] ?? 0),
            'total'     => (int)($row['total'] ?? 0),
        ];
    }

    /**
     * Was an outbound template already sent for this order recently? Used to
     * avoid duplicate delivery reminders on repeated cron runs.
     */
    public function recentForOrder(int $orderId, string $template, int $withinHours = 20): ?array
    {
        return $this->fetch(
            "SELECT * FROM {$this->table}
             WHERE order_id = ? AND template = ? AND direction = 'outbound'
               AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY id DESC LIMIT 1",
            [$orderId, $template, max(1, $withinHours)]
        );
    }

    public function deleteOlderThan(int $days): int
    {
        $days = max(1, $days);
        $stmt = $this->query(
            "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        return $stmt->rowCount();
    }

    public function clearAll(): int
    {
        $stmt = $this->query("DELETE FROM {$this->table}");
        return $stmt->rowCount();
    }

    /**
     * @return array{0:string,1:array<int,mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        $channel = (string)($filters['channel'] ?? '');
        if (in_array($channel, ['whatsapp', 'messenger'], true)) {
            $conditions[] = 'channel = ?';
            $params[] = $channel;
        }

        $direction = (string)($filters['direction'] ?? '');
        if (in_array($direction, ['inbound', 'outbound'], true)) {
            $conditions[] = 'direction = ?';
            $params[] = $direction;
        }

        $status = (string)($filters['status'] ?? '');
        if ($status !== '' && preg_match('/^[a-z_]{1,30}$/', $status)) {
            $conditions[] = 'status = ?';
            $params[] = $status;
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $conditions[] = '(recipient LIKE ? OR template LIKE ? OR order_id IN (SELECT id FROM ' . table('orders') . ' WHERE order_number LIKE ?))';
            $like = "%{$q}%";
            array_push($params, $like, $like, $like);
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }
}

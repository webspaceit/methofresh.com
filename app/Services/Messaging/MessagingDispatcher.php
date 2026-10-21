<?php

namespace App\Services\Messaging;

use App\Models\wsit_Conversation;
use App\Models\wsit_MessageLog;
use App\Models\wsit_Order;

/**
 * Orchestrates business-triggered messages (order placed, status changed,
 * delivery reminders) across WhatsApp and Messenger.
 *
 * Every method is best-effort: a Meta outage or missing credentials must
 * never break checkout or the admin dashboard.
 */
class MessagingDispatcher
{
    private MessagingConfig $config;
    private WhatsAppService $whatsApp;
    private MessengerService $messenger;
    private MessageTemplate $templates;
    private wsit_MessageLog $logs;
    private wsit_Conversation $conversations;
    private wsit_Order $orders;

    public function __construct(
        ?MessagingConfig $config = null,
        ?WhatsAppService $whatsApp = null,
        ?MessengerService $messenger = null,
        ?MessageTemplate $templates = null,
        ?wsit_MessageLog $logs = null,
        ?wsit_Conversation $conversations = null,
        ?wsit_Order $orders = null
    ) {
        $this->config        = $config ?? new MessagingConfig();
        $this->whatsApp      = $whatsApp ?? new WhatsAppService($this->config);
        $this->messenger     = $messenger ?? new MessengerService($this->config);
        $this->templates     = $templates ?? new MessageTemplate($this->config);
        $this->logs          = $logs ?? new wsit_MessageLog();
        $this->conversations = $conversations ?? new wsit_Conversation();
        $this->orders        = $orders ?? new wsit_Order();
    }

    /**
     * Order confirmation (template) right after checkout succeeds.
     */
    public function onOrderPlaced(array $order): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $this->sendTemplateAndLog($order, $this->templates->orderConfirmation($order), 'order_confirmation');
            $this->mirrorToMessenger($order, $this->templates->orderConfirmation($order)['preview']);
        } catch (\Throwable) {
            // Never bubble into the checkout request.
        }
    }

    /**
     * Status update triggered from the dashboard. `delivered` uses the
     * dedicated order_delivered template, everything else order_status_update.
     */
    public function onStatusChanged(array $order, string $oldStatus, string $newStatus): void
    {
        if (!$this->config->isEnabled() || $oldStatus === $newStatus) {
            return;
        }

        try {
            if ($newStatus === 'delivered') {
                $built = $this->templates->orderDelivered($order);
                $key   = 'order_delivered';
            } else {
                $built = $this->templates->statusUpdate($order, $oldStatus, $newStatus);
                $key   = 'order_status_update';
            }

            $this->sendTemplateAndLog($order, $built, $key);
            $this->mirrorToMessenger($order, $built['preview']);
        } catch (\Throwable) {
        }
    }

    /**
     * Cron entry point: remind customers whose order is on its way.
     *
     * @return array{sent:int,failed:int,skipped:int}
     */
    public function sendDeliveryReminders(int $limit = 50): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        if (!$this->config->isEnabled()) {
            return $result;
        }

        $candidates = $this->orders->deliveryReminderCandidates(max(1, $limit));

        foreach ($candidates as $order) {
            $orderId = (int)($order['id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }
            if ($this->logs->recentForOrder($orderId, 'delivery_reminder', 20) !== null) {
                $result['skipped']++;
                continue;
            }

            $ok = $this->sendTemplateAndLog($order, $this->templates->deliveryReminder($order), 'delivery_reminder');
            if ($ok) {
                $this->mirrorToMessenger($order, $this->templates->deliveryReminder($order)['preview']);
                $result['sent']++;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Alert the shop owner when a customer asks for a human agent.
     */
    public function notifyAdmin(string $message): bool
    {
        $phone = $this->config->get('admin_alert_phone');
        if ($phone === '' || !$this->config->whatsAppReady()) {
            return false;
        }

        try {
            $result = $this->whatsApp->sendText($phone, $message);
            $this->logs->logOutbound(
                'whatsapp',
                WhatsAppService::normalizePhone($phone) ?: $phone,
                $result['message_id'],
                ['text' => $message, 'response' => $result['response']],
                $result['ok'],
                $result['error'],
                null,
                'admin_alert'
            );
            return $result['ok'];
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Run messaging work after the HTTP response is flushed so checkout and
     * dashboard actions stay fast even if Meta is slow.
     */
    public static function defer(callable $work): void
    {
        register_shutdown_function(static function () use ($work): void {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            try {
                $work();
            } catch (\Throwable) {
                // Background work must stay silent.
            }
        });
    }

    private function sendTemplateAndLog(array $order, array $built, string $templateKey): bool
    {
        if (!$this->config->whatsAppReady()) {
            return false;
        }

        $rawTo   = (string)($order['shipping_phone'] ?? '');
        $to      = WhatsAppService::normalizePhone($rawTo);
        $orderId = (int)($order['id'] ?? 0);

        $result = $this->whatsApp->sendTemplate(
            $rawTo,
            $built['name'],
            $built['language'],
            $built['components']
        );

        try {
            $this->logs->logOutbound(
                'whatsapp',
                $to !== '' ? $to : $rawTo,
                $result['message_id'],
                [
                    'template'   => $built['name'],
                    'language'   => $built['language'],
                    'components' => $built['components'],
                    'response'   => $result['response'],
                ],
                $result['ok'],
                $result['error'],
                $orderId > 0 ? $orderId : null,
                $templateKey
            );
        } catch (\Throwable) {
        }

        return $result['ok'];
    }

    /**
     * Messenger can only message a PSID, never a phone number. Mirror the
     * message only when this customer already has a Messenger thread and is
     * inside the 24-hour service window (otherwise Meta would reject it).
     */
    private function mirrorToMessenger(array $order, string $text): void
    {
        if (!$this->config->messengerReady()) {
            return;
        }

        $phone = WhatsAppService::normalizePhone((string)($order['shipping_phone'] ?? ''));
        if ($phone === '') {
            return;
        }

        $conversation = $this->conversations->findMessengerByPhone($phone);
        if ($conversation === null || (string)($conversation['psid'] ?? '') === '') {
            return;
        }
        if (!$this->conversations->withinServiceWindow($conversation)) {
            return;
        }

        $psid   = (string)$conversation['psid'];
        $result = $this->messenger->sendText($psid, $text);

        try {
            $this->logs->logOutbound(
                'messenger',
                $psid,
                $result['message_id'],
                ['text' => $text, 'response' => $result['response']],
                $result['ok'],
                $result['error'],
                (int)($order['id'] ?? 0) ?: null,
                'order_mirror'
            );
        } catch (\Throwable) {
        }
    }
}

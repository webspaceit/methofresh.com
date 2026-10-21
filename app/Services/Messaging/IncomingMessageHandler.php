<?php

namespace App\Services\Messaging;

use App\Models\wsit_Conversation;
use App\Models\wsit_MessageLog;
use App\Models\wsit_Order;
use App\Models\wsit_Product;

/**
 * Processes inbound WhatsApp / Messenger webhook events and answers with a
 * small rule-based Bengali/English chatbot:
 *
 *   - order status lookup (by order number or by WhatsApp phone)
 *   - product search with prices + links
 *   - human-agent handover (thread is flagged 'human' and the owner is alerted)
 *   - welcome menu with interactive buttons
 *
 * All replies are logged through wsit_MessageLog, and duplicate webhook
 * deliveries are ignored via the provider message id.
 */
class IncomingMessageHandler
{
    private MessagingConfig $config;
    private WhatsAppService $whatsApp;
    private MessengerService $messenger;
    private MessageTemplate $templates;
    private wsit_MessageLog $logs;
    private wsit_Conversation $conversations;
    private wsit_Order $orders;
    private wsit_Product $products;

    public function __construct(
        ?MessagingConfig $config = null,
        ?WhatsAppService $whatsApp = null,
        ?MessengerService $messenger = null,
        ?MessageTemplate $templates = null,
        ?wsit_MessageLog $logs = null,
        ?wsit_Conversation $conversations = null,
        ?wsit_Order $orders = null,
        ?wsit_Product $products = null
    ) {
        $this->config        = $config ?? new MessagingConfig();
        $this->whatsApp      = $whatsApp ?? new WhatsAppService($this->config);
        $this->messenger     = $messenger ?? new MessengerService($this->config);
        $this->templates     = $templates ?? new MessageTemplate($this->config);
        $this->logs          = $logs ?? new wsit_MessageLog();
        $this->conversations = $conversations ?? new wsit_Conversation();
        $this->orders        = $orders ?? new wsit_Order();
        $this->products      = $products ?? new wsit_Product();
    }

    public function handleWhatsApp(array $payload): void
    {
        foreach ((array)($payload['entry'] ?? []) as $entry) {
            foreach ((array)($entry['changes'] ?? []) as $change) {
                $value = (array)($change['value'] ?? []);

                // Delivery receipts.
                foreach ((array)($value['statuses'] ?? []) as $status) {
                    $error = '';
                    if (!empty($status['errors'][0]['title'])) {
                        $error = (string)$status['errors'][0]['title'];
                    }
                    $this->logs->updateStatusByMessageId(
                        (string)($status['id'] ?? ''),
                        (string)($status['status'] ?? ''),
                        $error !== '' ? $error : null
                    );
                }

                $name = (string)($value['contacts'][0]['profile']['name'] ?? '');
                foreach ((array)($value['messages'] ?? []) as $message) {
                    $this->handleWhatsAppMessage((array)$message, $name);
                }
            }
        }
    }

    public function handleMessenger(array $payload): void
    {
        foreach ((array)($payload['entry'] ?? []) as $entry) {
            foreach ((array)($entry['messaging'] ?? []) as $event) {
                $event = (array)$event;

                // Delivery / read receipts.
                if (!empty($event['delivery']['mids'])) {
                    foreach ((array)$event['delivery']['mids'] as $mid) {
                        $this->logs->updateStatusByMessageId((string)$mid, 'delivered');
                    }
                    continue;
                }
                if (!empty($event['read']['watermark'])) {
                    continue;
                }

                $psid = (string)($event['sender']['id'] ?? '');
                if ($psid === '') {
                    continue;
                }

                $message = (array)($event['message'] ?? []);
                $postback = (array)($event['postback'] ?? []);
                $mid = (string)($message['mid'] ?? '');
                if ($mid === '' && $postback !== []) {
                    $mid = 'pb_' . (string)($event['timestamp'] ?? time()) . '_' . substr(md5(json_encode($postback) ?: ''), 0, 10);
                }
                if ($mid !== '' && $this->logs->messageIdExists($mid)) {
                    continue;
                }

                $conversation = $this->conversations->touchInbound('messenger', $psid);
                try {
                    $this->logs->logInbound('messenger', $psid, $mid !== '' ? $mid : null, $event);
                } catch (\Throwable) {
                }

                $text = $this->extractMessengerText($message, $postback);
                if ($text === '') {
                    continue;
                }

                $intent = $this->detectIntent($text, $postback['payload'] ?? null);
                $this->route('messenger', $psid, $text, $intent, $conversation);
            }
        }
    }

    private function handleWhatsAppMessage(array $message, string $contactName): void
    {
        $id = (string)($message['id'] ?? '');
        $from = (string)($message['from'] ?? '');
        if ($id === '' || $from === '') {
            return;
        }
        if ($this->logs->messageIdExists($id)) {
            return;
        }

        $conversation = $this->conversations->touchInbound('whatsapp', $from, $contactName);
        try {
            $this->logs->logInbound('whatsapp', $from, $id, $message);
        } catch (\Throwable) {
        }

        // Blue ticks + read receipt (best effort, never fatal).
        try {
            $this->whatsApp->markAsRead($id);
        } catch (\Throwable) {
        }

        $text = $this->extractWhatsAppText($message);
        if ($text === '') {
            $this->send('whatsapp', $from, 'দুঃখিত, এই ধরনের মেসেজ এখনো সমর্থিত নয়। অনুগ্রহ করে টেক্সট লিখুন।');
            return;
        }

        $intent = $this->detectIntent($text, $this->interactivePayload($message));
        $this->route('whatsapp', $from, $text, $intent, $conversation);
    }

    private function route(string $channel, string $to, string $text, string $intent, array $conversation): void
    {
        // A human agent has taken over — stay silent, only log.
        if (($conversation['status'] ?? 'open') === 'human') {
            return;
        }

        switch ($intent) {
            case 'order_status':
                $this->replyOrderStatus($channel, $to, $text, $conversation);
                break;
            case 'products':
                $this->replyProducts($channel, $to, $text);
                break;
            case 'human':
                $this->handover($channel, $to, $text, $conversation);
                break;
            case 'greeting':
                $this->sendMenu($channel, $to);
                break;
            default:
                $this->sendMenu($channel, $to, true);
                break;
        }
    }

    private function replyOrderStatus(string $channel, string $to, string $text, array $conversation): void
    {
        $order = null;

        if (preg_match('/MF-\d{6}-[A-Z0-9]{6}/i', $text, $matches)) {
            $order = $this->orders->findByNumber(strtoupper($matches[0]));
        }

        if ($order === null) {
            $phone = '';
            if ($channel === 'whatsapp') {
                $phone = $to;
            } elseif (!empty($conversation['phone'])) {
                $phone = (string)$conversation['phone'];
            }
            if ($phone !== '') {
                $order = $this->orders->latestByPhone($phone);
            }
        }

        if ($order === null) {
            $this->send(
                $channel,
                $to,
                "অর্ডারের সর্বশেষ অবস্থা জানতে আপনার অর্ডার নম্বরটি পাঠান।\nউদাহরণ: MF-261021-AB12CD",
                [['id' => 'human', 'title' => 'এজেন্টের সাথে কথা']]
            );
            return;
        }

        // Remember the verified phone for Messenger order-update mirrors.
        if ($channel === 'messenger' && (string)($conversation['phone'] ?? '') === '') {
            $this->conversations->linkPhone(
                (int)$conversation['id'],
                WhatsAppService::normalizePhone((string)($order['shipping_phone'] ?? ''))
            );
        }

        $status = (string)($order['status'] ?? 'pending');
        $label  = $this->templates->statusLabel($status);
        $note   = $this->templates->statusNote($status);
        $total  = $this->templates->money((float)($order['total'] ?? 0));

        $reply = "📦 অর্ডার #{$order['order_number']}\n"
            . "বর্তমান স্ট্যাটাস: {$label}\n"
            . ($note !== '' ? "{$note}\n" : '')
            . "মোট: {$total}\n"
            . 'আরও সহায়তা প্রয়োজন হলে "এজেন্ট" লিখুন।';

        $this->send($channel, $to, $reply);
    }

    private function replyProducts(string $channel, string $to, string $text): void
    {
        $query = $this->extractProductQuery($text);

        if ($query === '') {
            $this->send(
                $channel,
                $to,
                "কোন পণ্যটি খুঁজছেন? নাম লিখে পাঠান — যেমন: গুড়, সরিষার তেল, আম।"
            );
            return;
        }

        $matches = $this->products->suggest($query, 5);
        if ($matches === []) {
            $this->send(
                $channel,
                $to,
                "দুঃখিত, \"{$query}\" নামে কোনো পণ্য পাওয়া যায়নি।\n"
                . 'অন্য নামে খুঁজুন বা "এজেন্ট" লিখে আমাদের সাথে কথা বলুন।',
                [['id' => 'human', 'title' => 'এজেন্টের সাথে কথা']]
            );
            return;
        }

        $base = rtrim((string)config('app.base_url', ''), '/');
        $lines = ["🔎 \"{$query}\" এর জন্য পাওয়া গেল:"];
        $currency = '৳';

        foreach ($matches as $product) {
            $name = trim((string)($product['name_bn'] ?? ''));
            if ($name === '') {
                $name = (string)($product['name_en'] ?? '');
            }
            $price = $product['sale_price'] !== null && $product['sale_price'] !== ''
                ? (float)$product['sale_price']
                : (float)($product['price'] ?? 0);
            $priceText = $currency . ' ' . $this->templates->bnDigits(number_format($price, 0));
            $link = $base . '/en/products/' . rawurlencode((string)($product['slug'] ?? ''));
            $lines[] = "\n• {$name} — {$priceText}\n{$link}";
        }

        $lines[] = "\nঅর্ডার করতে ওয়েবসাইটে যান অথবা \"এজেন্ট\" লিখুন।";
        $this->send($channel, $to, implode('', $lines));
    }

    private function handover(string $channel, string $to, string $text, array $conversation): void
    {
        $this->conversations->setStatus((int)$conversation['id'], 'human');

        $this->send(
            $channel,
            $to,
            'ধন্যবাদ! আপনার অনুরোধ আমাদের কাছে পৌঁছেছে। আমাদের একজন প্রতিনিধি শীঘ্রই আপনার সাথে যোগাযোগ করবেন।'
        );

        try {
            $who = $channel === 'whatsapp' ? $to : ('PSID ' . $to);
            $name = (string)($conversation['display_name'] ?? '');
            (new MessagingDispatcher($this->config, $this->whatsApp, $this->messenger, $this->templates))
                ->notifyAdmin("🔔 নতুন হ্যান্ডঅভার অনুরোধ\n"
                    . 'চ্যানেল: ' . ucfirst($channel) . "\n"
                    . ($name !== '' ? "নাম: {$name}\n" : '')
                    . "আইডি: {$who}\n"
                    . "বার্তা: {$text}");
        } catch (\Throwable) {
        }
    }

    private function sendMenu(string $channel, string $to, bool $fallback = false): void
    {
        $text = $fallback
            ? "দুঃখিত, আমি বুঝতে পারিনি। নিচের অপশন থেকে বেছে নিন অথবা \"এজেন্ট\" লিখুন।"
            : "আসসালামু আলাইকুম! " . app_name() . "-এ আপনাকে স্বাগতম।\nকীভাবে সাহায্য করতে পারি?";

        $this->send($channel, $to, $text, [
            ['id' => 'order_status', 'title' => 'অর্ডার স্ট্যাটাস'],
            ['id' => 'products', 'title' => 'পণ্য খুঁজুন'],
            ['id' => 'human', 'title' => 'এজেন্ট'],
        ]);
    }

    /**
     * Send a reply on the right channel and persist a log row.
     *
     * @param array<int,array{id:string,title:string}> $buttons
     */
    private function send(string $channel, string $to, string $text, array $buttons = []): void
    {
        try {
            if ($channel === 'whatsapp') {
                $result = $buttons !== []
                    ? $this->whatsApp->sendButtons($to, $text, $buttons)
                    : $this->whatsApp->sendText($to, $text, true);
                $recipient = WhatsAppService::normalizePhone($to) ?: $to;
            } else {
                $payload = [];
                foreach ($buttons as $button) {
                    $payload[] = ['title' => $button['title'], 'payload' => $button['id']];
                }
                $result = $buttons !== []
                    ? $this->messenger->sendButtons($to, $text, $payload)
                    : $this->messenger->sendText($to, $text);
                $recipient = $to;
            }

            $this->logs->logOutbound(
                $channel,
                $recipient,
                $result['message_id'],
                ['text' => $text, 'buttons' => $buttons, 'response' => $result['response']],
                $result['ok'],
                $result['error'],
                null,
                'chatbot'
            );
        } catch (\Throwable) {
            // Logging must never break webhook processing.
        }
    }

    private function detectIntent(string $text, ?string $payload = null): string
    {
        $payload = strtolower(trim((string)$payload));
        if (in_array($payload, ['order_status', 'products', 'human'], true)) {
            return $payload;
        }

        $lower = mb_strtolower($text, 'UTF-8');

        if (preg_match('/MF-\d{6}-[A-Z0-9]{6}/i', $text)) {
            return 'order_status';
        }

        $humanWords = ['মানুষ', 'এজেন্ট', 'agent', 'human', 'সাহায্য', 'help', 'কথা বল', 'কল', 'call'];
        foreach ($humanWords as $word) {
            if (str_contains($lower, $word)) {
                return 'human';
            }
        }

        $statusWords = ['অর্ডার', 'order', 'স্ট্যাটাস', 'status', 'ডেলিভারি', 'delivery', 'পার্সেল', 'parcel', 'ট্র্যাক', 'track'];
        foreach ($statusWords as $word) {
            if (str_contains($lower, $word)) {
                return 'order_status';
            }
        }

        $productWords = ['পণ্য', 'প্রোডাক্ট', 'product', 'দাম', 'price', 'কি আছে', 'কী আছে', 'খুঁজ', 'search', 'মূল্য'];
        foreach ($productWords as $word) {
            if (str_contains($lower, $word)) {
                return 'products';
            }
        }

        $greetings = ['হ্যালো', 'হাই', 'সালাম', 'assalam', 'আসসালামু', 'hello', 'hi', 'hey', 'শুভ'];
        foreach ($greetings as $word) {
            if (str_contains($lower, $word)) {
                return 'greeting';
            }
        }

        return 'unknown';
    }

    /**
     * Strip conversational filler so "গুড়ের দাম কত" becomes "গুড়".
     */
    private function extractProductQuery(string $text): string
    {
        $query = trim($text);
        $fillers = [
            'পণ্যের দাম', 'পণ্য', 'প্রোডাক্ট', 'product', 'দাম', 'price', 'মূল্য',
            'কত', 'কি আছে', 'কী আছে', 'খুঁজছি', 'খুঁজ', 'search', 'চাই', 'আছে',
            '?', '؟', '।', '.', '!',
        ];
        foreach ($fillers as $filler) {
            $query = str_ireplace($filler, ' ', $query);
        }
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? '');
        return mb_substr($query, 0, 60);
    }

    private function extractWhatsAppText(array $message): string
    {
        if (!empty($message['text']['body'])) {
            return (string)$message['text']['body'];
        }
        if (!empty($message['interactive']['button_reply']['title'])) {
            return (string)$message['interactive']['button_reply']['title'];
        }
        if (!empty($message['interactive']['list_reply']['title'])) {
            return (string)$message['interactive']['list_reply']['title'];
        }
        if (!empty($message['button']['text'])) {
            return (string)$message['button']['text'];
        }
        foreach (['image', 'video', 'document'] as $media) {
            if (!empty($message[$media]['caption'])) {
                return (string)$message[$media]['caption'];
            }
        }
        return '';
    }

    private function extractMessengerText(array $message, array $postback): string
    {
        if (!empty($message['text'])) {
            return (string)$message['text'];
        }
        if (!empty($message['quick_reply']['payload'])) {
            return (string)$message['quick_reply']['payload'];
        }
        if (!empty($postback['title'])) {
            return (string)$postback['title'];
        }
        if (!empty($postback['payload'])) {
            return (string)$postback['payload'];
        }
        return '';
    }

    private function interactivePayload(array $message): ?string
    {
        $id = (string)($message['interactive']['button_reply']['id'] ?? '');
        if ($id === '') {
            $id = (string)($message['interactive']['list_reply']['id'] ?? '');
        }
        return $id !== '' ? $id : null;
    }
}

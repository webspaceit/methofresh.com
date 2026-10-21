<?php

namespace App\Services\Messaging;

/**
 * Builds Meta template payloads and plain-text mirrors for order events.
 *
 * Template names are configurable from the dashboard because they must match
 * the exact names approved in Meta Business Manager. Body placeholders follow
 * the copy proposed in the project spec:
 *
 *   order_confirmation  {{1}} name, {{2}} order number, {{3}} total, {{4}} address
 *   order_status_update {{1}} order number, {{2}} status, {{3}} note
 *   delivery_reminder   {{1}} order number
 *   order_delivered     {{1}} order number
 */
class MessageTemplate
{
    private MessagingConfig $config;

    public function __construct(?MessagingConfig $config = null)
    {
        $this->config = $config ?? new MessagingConfig();
    }

    /**
     * @return array{name:string,language:string,components:array<int,array<string,mixed>>,preview:string}
     */
    public function orderConfirmation(array $order): array
    {
        $name    = $this->config->get('wa_tpl_order_confirmation', 'order_confirmation');
        $number  = (string)($order['order_number'] ?? '');
        $total   = $this->money((float)($order['total'] ?? 0));
        $address = trim((string)($order['shipping_address'] ?? ''));

        return [
            'name'     => $name,
            'language' => $this->config->templateLanguage(),
            'components' => [[
                'type'       => 'body',
                'parameters' => [
                    $this->param((string)($order['shipping_name'] ?? '')),
                    $this->param($number),
                    $this->param($total),
                    $this->param($address),
                ],
            ]],
            'preview' => 'হ্যালো ' . (string)($order['shipping_name'] ?? '') . "!\n"
                . "আপনার অর্ডার #{$number} সফলভাবে গ্রহণ করা হয়েছে।\n"
                . "মোট পরিমাণ: {$total}\n"
                . "ডেলিভারি ঠিকানা: {$address}\n\n"
                . 'ধন্যবাদ — ' . app_name(),
        ];
    }

    /**
     * @return array{name:string,language:string,components:array<int,array<string,mixed>>,preview:string}
     */
    public function statusUpdate(array $order, string $oldStatus, string $newStatus): array
    {
        $name   = $this->config->get('wa_tpl_status_update', 'order_status_update');
        $number = (string)($order['order_number'] ?? '');
        $label  = $this->statusLabel($newStatus);
        $note   = $this->statusNote($newStatus);

        return [
            'name'     => $name,
            'language' => $this->config->templateLanguage(),
            'components' => [[
                'type'       => 'body',
                'parameters' => [
                    $this->param($number),
                    $this->param($label),
                    $this->param($note),
                ],
            ]],
            'preview' => "আপনার অর্ডার #{$number} এর বর্তমান স্ট্যাটাস: {$label}\n{$note}",
        ];
    }

    /**
     * @return array{name:string,language:string,components:array<int,array<string,mixed>>,preview:string}
     */
    public function deliveryReminder(array $order): array
    {
        $name   = $this->config->get('wa_tpl_delivery_reminder', 'delivery_reminder');
        $number = (string)($order['order_number'] ?? '');

        return [
            'name'     => $name,
            'language' => $this->config->templateLanguage(),
            'components' => [[
                'type'       => 'body',
                'parameters' => [$this->param($number)],
            ]],
            'preview' => "প্রিয় গ্রাহক, আগামীকাল আপনার অর্ডার #{$number} ডেলিভারি হবে।\n"
                . 'দয়া করে প্রস্তুত থাকুন। ক্যাশ অন ডেলিভারি।',
        ];
    }

    /**
     * @return array{name:string,language:string,components:array<int,array<string,mixed>>,preview:string}
     */
    public function orderDelivered(array $order): array
    {
        $name   = $this->config->get('wa_tpl_order_delivered', 'order_delivered');
        $number = (string)($order['order_number'] ?? '');

        return [
            'name'     => $name,
            'language' => $this->config->templateLanguage(),
            'components' => [[
                'type'       => 'body',
                'parameters' => [$this->param($number)],
            ]],
            'preview' => "আপনার অর্ডার #{$number} সফলভাবে ডেলিভারি সম্পন্ন হয়েছে।\n"
                . 'আমাদের সাথে থাকার জন্য ধন্যবাদ — ' . app_name(),
        ];
    }

    public function statusLabel(string $status): string
    {
        return [
            'pending'    => 'পেন্ডিং',
            'processing' => 'প্রসেসিং',
            'shipped'    => 'শিপড',
            'delivered'  => 'ডেলিভারি সম্পন্ন',
            'cancelled'  => 'বাতিল',
        ][$status] ?? $status;
    }

    public function statusNote(string $status): string
    {
        return [
            'pending'    => 'আপনার অর্ডারটি গ্রহণ করা হয়েছে।',
            'processing' => 'আপনার অর্ডার প্রস্তুত হচ্ছে।',
            'shipped'    => 'আপনার পার্সেল পথে আছে।',
            'delivered'  => 'আপনার অর্ডার সফলভাবে ডেলিভারি হয়েছে।',
            'cancelled'  => 'আপনার অর্ডার বাতিল করা হয়েছে।',
        ][$status] ?? '';
    }

    public function money(float $amount): string
    {
        $formatted = number_format($amount, 0, '.', ',');
        return '৳ ' . $this->bnDigits($formatted);
    }

    public function bnDigits(string $value): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            $value
        );
    }

    /**
     * @return array{type:string,text:string}
     */
    private function param(string $value): array
    {
        // Meta rejects newlines/tabs and >1024 chars in body parameters.
        $clean = trim(preg_replace('/[\r\n\t]+/', ' ', $value) ?? '');
        return ['type' => 'text', 'text' => mb_substr($clean, 0, 1024)];
    }
}

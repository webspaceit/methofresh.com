<?php

namespace App\Services\Messaging;

/**
 * Official WhatsApp Cloud API client (Meta Graph API).
 *
 * No third-party / unofficial libraries are used — every call is a plain
 * HTTPS JSON request to graph.facebook.com.
 */
class WhatsAppService
{
    private MessagingConfig $config;

    public function __construct(?MessagingConfig $config = null)
    {
        $this->config = $config ?? new MessagingConfig();
    }

    public function isConfigured(): bool
    {
        return $this->config->whatsAppReady();
    }

    public function sendText(string $to, string $message, bool $previewUrl = false): array
    {
        $to = self::normalizePhone($to);
        if ($to === '') {
            return $this->failure('invalid_recipient');
        }

        return $this->request('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => $previewUrl,
                'body'        => mb_substr($message, 0, 4096),
            ],
        ]);
    }

    /**
     * @param array<int,array<string,mixed>> $components e.g.
     *        [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => '...']]]]
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $languageCode = 'bn',
        array $components = []
    ): array {
        $to = self::normalizePhone($to);
        if ($to === '') {
            return $this->failure('invalid_recipient');
        }

        $template = [
            'name'     => $templateName,
            'language' => ['code' => $languageCode !== '' ? $languageCode : 'bn'],
        ];
        if ($components !== []) {
            $template['components'] = $components;
        }

        return $this->request('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'template',
            'template'          => $template,
        ]);
    }

    /**
     * @param array<int,array{id?:string,title:string}> $buttons max 3
     */
    public function sendButtons(
        string $to,
        string $bodyText,
        array $buttons,
        string $headerText = '',
        string $footerText = ''
    ): array {
        $to = self::normalizePhone($to);
        if ($to === '') {
            return $this->failure('invalid_recipient');
        }

        $actionButtons = [];
        foreach (array_slice($buttons, 0, 3) as $index => $button) {
            $actionButtons[] = [
                'type'  => 'reply',
                'reply' => [
                    'id'    => mb_substr((string)($button['id'] ?? 'btn_' . $index), 0, 256),
                    'title' => mb_substr((string)($button['title'] ?? ''), 0, 20),
                ],
            ];
        }

        $interactive = [
            'type'   => 'button',
            'body'   => ['text' => mb_substr($bodyText, 0, 1024)],
            'action' => ['buttons' => $actionButtons],
        ];
        if ($headerText !== '') {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($headerText, 0, 60)];
        }
        if ($footerText !== '') {
            $interactive['footer'] = ['text' => mb_substr($footerText, 0, 60)];
        }

        return $this->request('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ]);
    }

    /**
     * @param array<int,array{title:string,rows:array<int,array{id:string,title:string,description?:string}>}> $sections
     */
    public function sendList(
        string $to,
        string $bodyText,
        string $buttonText,
        array $sections,
        string $headerText = '',
        string $footerText = ''
    ): array {
        $to = self::normalizePhone($to);
        if ($to === '') {
            return $this->failure('invalid_recipient');
        }

        $cleanSections = [];
        foreach ($sections as $section) {
            $rows = [];
            foreach (array_slice((array)($section['rows'] ?? []), 0, 10) as $row) {
                $rows[] = [
                    'id'          => mb_substr((string)($row['id'] ?? ''), 0, 200),
                    'title'       => mb_substr((string)($row['title'] ?? ''), 0, 24),
                    'description' => mb_substr((string)($row['description'] ?? ''), 0, 72),
                ];
            }
            if ($rows === []) {
                continue;
            }
            $cleanSections[] = [
                'title' => mb_substr((string)($section['title'] ?? ''), 0, 24),
                'rows'  => $rows,
            ];
        }

        if ($cleanSections === []) {
            return $this->failure('empty_list');
        }

        $interactive = [
            'type'   => 'list',
            'body'   => ['text' => mb_substr($bodyText, 0, 1024)],
            'action' => [
                'button'   => mb_substr($buttonText, 0, 20),
                'sections' => $cleanSections,
            ],
        ];
        if ($headerText !== '') {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($headerText, 0, 60)];
        }
        if ($footerText !== '') {
            $interactive['footer'] = ['text' => mb_substr($footerText, 0, 60)];
        }

        return $this->request('messages', [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ]);
    }

    public function markAsRead(string $messageId): array
    {
        if ($messageId === '') {
            return $this->failure('invalid_message_id');
        }

        return $this->request('messages', [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ]);
    }

    /**
     * Bangladesh-friendly MSISDN normalization: 01712345678 / +8801712345678
     * / 8801712345678 / 1712345678 all become 8801712345678.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $length = strlen($digits);

        if (str_starts_with($digits, '880')) {
            return $length >= 13 ? $digits : '';
        }

        if (str_starts_with($digits, '0') && $length === 11) {
            return '880' . substr($digits, 1);
        }

        if (str_starts_with($digits, '1') && $length === 10) {
            return '880' . $digits;
        }

        // Already an international number with a different country code.
        return $length >= 8 ? $digits : '';
    }

    /**
     * @return array{ok:bool,status:int,message_id:?string,error:?string,response:array}
     */
    private function request(string $endpoint, array $payload): array
    {
        if (!$this->config->whatsAppReady()) {
            return $this->failure('not_configured');
        }

        $phoneNumberId = $this->config->get('wa_phone_number_id');
        $token         = $this->config->get('wa_access_token');
        $url = 'https://graph.facebook.com/' . $this->config->apiVersion() . '/'
             . rawurlencode($phoneNumberId) . '/' . ltrim($endpoint, '/');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body     = curl_exec($ch);
        $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        $response = json_decode((string)$body, true);

        if (!is_array($response)) {
            $response = [];
        }

        $ok        = $body !== false && $status >= 200 && $status < 300 && empty($response['error']);
        $messageId = $ok ? (string)($response['messages'][0]['id'] ?? '') : '';
        $error     = null;
        if (!$ok) {
            $error = (string)($response['error']['message'] ?? '');
            if ($error === '') {
                $error = $curlErr !== '' ? $curlErr : 'HTTP ' . $status;
            }
        }

        return [
            'ok'         => $ok,
            'status'     => $status,
            'message_id' => $messageId !== '' ? $messageId : null,
            'error'      => $error,
            'response'   => $response,
        ];
    }

    /**
     * @return array{ok:bool,status:int,message_id:?string,error:?string,response:array}
     */
    private function failure(string $error): array
    {
        return [
            'ok'         => false,
            'status'     => 0,
            'message_id' => null,
            'error'      => $error,
            'response'   => [],
        ];
    }
}

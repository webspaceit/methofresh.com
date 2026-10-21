<?php

namespace App\Services\Messaging;

/**
 * Official Facebook Messenger Platform API client (Meta Graph API).
 *
 * Sends messages to a Page-scoped id (PSID). Webhook replies are free-form
 * inside Meta's 24-hour window; templates/buttons are used otherwise.
 */
class MessengerService
{
    private MessagingConfig $config;

    public function __construct(?MessagingConfig $config = null)
    {
        $this->config = $config ?? new MessagingConfig();
    }

    public function isConfigured(): bool
    {
        return $this->config->messengerReady();
    }

    public function sendText(string $psid, string $message): array
    {
        return $this->sendMessage($psid, [
            'text' => mb_substr($message, 0, 2000),
        ]);
    }

    /**
     * @param array<string,mixed> $templatePayload A Messenger `message` object
     *        (e.g. attachment/template payload). Passed through as-is.
     */
    public function sendTemplate(string $psid, array $templatePayload): array
    {
        return $this->sendMessage($psid, $templatePayload);
    }

    /**
     * @param array<int,array{title:string,payload:string}> $buttons max 3
     */
    public function sendButtons(string $psid, string $text, array $buttons): array
    {
        $clean = [];
        foreach (array_slice($buttons, 0, 3) as $button) {
            $clean[] = [
                'type'    => 'postback',
                'title'   => mb_substr((string)($button['title'] ?? ''), 0, 20),
                'payload' => mb_substr((string)($button['payload'] ?? ($button['id'] ?? '')), 0, 300),
            ];
        }

        return $this->sendMessage($psid, [
            'attachment' => [
                'type'    => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text'          => mb_substr($text, 0, 640),
                    'buttons'       => $clean,
                ],
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $message
     * @return array{ok:bool,status:int,message_id:?string,error:?string,response:array}
     */
    private function sendMessage(string $psid, array $message): array
    {
        if (!$this->config->messengerReady() || $psid === '') {
            return $this->failure($psid === '' ? 'invalid_recipient' : 'not_configured');
        }

        $token = $this->config->get('fb_page_access_token');
        $url   = 'https://graph.facebook.com/' . $this->config->apiVersion()
               . '/me/messages?access_token=' . rawurlencode($token);

        $payload = [
            'recipient'      => ['id' => $psid],
            'messaging_type' => 'RESPONSE',
            'message'        => $message,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
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
        $messageId = $ok ? (string)($response['message_id'] ?? '') : '';
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

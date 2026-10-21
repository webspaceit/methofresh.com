<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Services\Messaging\IncomingMessageHandler;
use App\Services\Messaging\MessagingConfig;

/**
 * Meta WhatsApp Cloud API webhook.
 *
 * GET  /api/whatsapp/webhook  — challenge verification
 * POST /api/whatsapp/webhook  — message + delivery events (HMAC verified)
 */
class wsit_WhatsAppWebhookController extends wsit_Controller
{
    public function verify(wsit_Request $request): void
    {
        $config = new MessagingConfig();
        $mode   = (string)$request->query('hub_mode', '');
        $token  = (string)$request->query('hub_verify_token', '');
        $challenge = (string)$request->query('hub_challenge', '');

        $expected = $config->get('wa_verify_token');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            http_response_code(200);
            header('Content-Type: text/plain; charset=utf-8');
            echo $challenge;
            exit;
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'verification failed';
        exit;
    }

    public function handle(wsit_Request $request): void
    {
        $config = new MessagingConfig();
        $secret = $config->get('wa_app_secret');

        if ($secret === '') {
            $this->json(['status' => 'error', 'message' => 'webhook not configured'], 503);
        }

        $raw = (string)file_get_contents('php://input');
        $signature = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');

        if (!$this->validSignature($raw, $signature, $secret)) {
            $this->json(['status' => 'error', 'message' => 'invalid signature'], 403);
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->json(['status' => 'error', 'message' => 'invalid payload'], 400);
        }

        try {
            (new IncomingMessageHandler($config))->handleWhatsApp($payload);
        } catch (\Throwable $e) {
            // Return 500 so Meta retries; the failure is visible in PHP logs.
            error_log('WhatsApp webhook error: ' . $e->getMessage());
            $this->json(['status' => 'error', 'message' => 'processing failed'], 500);
        }

        $this->json(['status' => 'ok']);
    }

    private function validSignature(string $raw, string $header, string $secret): bool
    {
        if ($raw === '' || $header === '' || !str_starts_with($header, 'sha256=')) {
            return false;
        }

        $provided = substr($header, 7);
        $expected = hash_hmac('sha256', $raw, $secret);

        return hash_equals($expected, $provided);
    }
}

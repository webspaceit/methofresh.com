<?php

namespace App\Services\Messaging;

use App\Models\wsit_Setting;

/**
 * Resolves messaging credentials for the current request.
 *
 * Resolution order (last wins):
 *   1. built-in defaults
 *   2. `messaging.*` config block (app/config/wsit_config*.php)
 *   3. MF_MESSAGING_* environment variables
 *   4. dashboard settings (wsit_settings.messaging_*) — primary in production
 *
 * Settings saved from the dashboard therefore never require a deploy, while
 * deploy-time config/env still provides a safe fallback for fresh installs.
 */
class MessagingConfig
{
    private static ?array $cache = null;

    private array $values;

    public function __construct()
    {
        if (self::$cache === null) {
            self::$cache = $this->load();
        }
        $this->values = self::$cache;
    }

    private function load(): array
    {
        $defaults = [
            'enabled'                    => '0',
            'api_version'                => 'v21.0',
            'wa_phone_number_id'         => '',
            'wa_access_token'            => '',
            'wa_business_account_id'     => '',
            'wa_app_secret'              => '',
            'wa_verify_token'            => '',
            'wa_template_lang'           => 'bn',
            'wa_tpl_order_confirmation'  => 'order_confirmation',
            'wa_tpl_status_update'       => 'order_status_update',
            'wa_tpl_delivery_reminder'   => 'delivery_reminder',
            'wa_tpl_order_delivered'     => 'order_delivered',
            'fb_page_access_token'       => '',
            'fb_app_secret'              => '',
            'fb_verify_token'            => '',
            'admin_alert_phone'          => '',
        ];

        $values = $defaults;

        // 2) config fallback
        $config = config('messaging');
        if (is_array($config)) {
            foreach ($defaults as $key => $_) {
                if (array_key_exists($key, $config)) {
                    $values[$key] = (string)$config[$key];
                }
            }
        }

        // 3) environment fallback
        foreach ($defaults as $key => $_) {
            $env = getenv('MF_MESSAGING_' . strtoupper($key));
            if ($env !== false && $env !== '') {
                $values[$key] = (string)$env;
            }
        }

        // 4) dashboard-managed settings (database) take precedence
        try {
            $rows = (new wsit_Setting())->all();
            foreach ($defaults as $key => $_) {
                $dbKey = 'messaging_' . $key;
                if (array_key_exists($dbKey, $rows)) {
                    $values[$key] = (string)$rows[$dbKey];
                }
            }
        } catch (\Throwable) {
            // DB unavailable (installer/bootstrap phase) — keep config/env values.
        }

        return $values;
    }

    public function get(string $key, string $default = ''): string
    {
        return (string)($this->values[$key] ?? $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int)($this->values[$key] ?? $default);
    }

    public function bool(string $key): bool
    {
        return in_array(strtolower($this->get($key)), ['1', 'true', 'yes', 'on'], true);
    }

    public function isEnabled(): bool
    {
        return $this->bool('enabled');
    }

    /**
     * WhatsApp Cloud API needs a phone number id + access token; app secret and
     * verify token are only required for webhooks (not for outbound sends).
     */
    public function whatsAppReady(): bool
    {
        return $this->get('wa_phone_number_id') !== ''
            && $this->get('wa_access_token') !== '';
    }

    public function whatsAppWebhookReady(): bool
    {
        return $this->get('wa_app_secret') !== ''
            && $this->get('wa_verify_token') !== '';
    }

    public function messengerReady(): bool
    {
        return $this->get('fb_page_access_token') !== '';
    }

    public function messengerWebhookReady(): bool
    {
        return $this->get('fb_app_secret') !== ''
            && $this->get('fb_verify_token') !== '';
    }

    public function apiVersion(): string
    {
        $version = $this->get('api_version', 'v21.0');
        return preg_match('/^v\d+\.\d+$/', $version) ? $version : 'v21.0';
    }

    public function templateLanguage(): string
    {
        $lang = $this->get('wa_template_lang', 'bn');
        return in_array($lang, ['bn', 'en', 'en_US'], true) ? $lang : 'bn';
    }

    /**
     * Public (non-secret) view of the settings for the dashboard form.
     * Secrets are returned too — the page is behind requireAdmin().
     */
    public function all(): array
    {
        return $this->values;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}

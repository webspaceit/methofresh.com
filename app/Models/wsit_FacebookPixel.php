<?php

namespace App\Models;

use App\Core\wsit_Model;

/**
 * Facebook Pixel configuration model.
 *
 * Stores the pixel ID and event toggles in the `settings` table under the
 * `facebook_pixel` JSON key, so the storefront can render the base snippet
 * conditionally and fire standard e-commerce events (ViewContent, AddToCart,
 * InitiateCheckout, Purchase) without touching core controllers.
 */
class wsit_FacebookPixel extends wsit_Model
{
    protected string $table = 'settings';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the full pixel configuration merged with defaults.
     *
     * Shape:
     *   pixel_id   — the Facebook Pixel ID (empty = disabled)
     *   enabled    — master switch; false disables the snippet entirely
     *   test_mode  — when true, events are logged but not sent to Facebook
     *   events     — map of event name => bool (ViewContent, AddToCart,
     *                 InitiateCheckout, Purchase)
     */
    public function config(): array
    {
        $default = [
            'pixel_id' => '',
            'enabled'  => false,
            'test_mode' => false,
            'events'   => [
                'view_content'      => true,
                'add_to_cart'       => true,
                'initiate_checkout' => true,
                'purchase'          => true,
            ],
        ];

        try {
            $row = $this->fetch(
                "SELECT `value` FROM {$this->table} WHERE `key` = 'facebook_pixel' LIMIT 1",
                []
            );
            $raw = $row !== null ? trim((string)$row['value']) : '';
            if ($raw === '') {
                return $default;
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return $default;
            }
            foreach ($default as $key => $val) {
                if (!array_key_exists($key, $decoded)) {
                    continue;
                }
                if (is_array($val)) {
                    if (is_array($decoded[$key])) {
                        $default[$key] = array_merge($val, $decoded[$key]);
                    }
                } elseif (is_bool($val)) {
                    $default[$key] = (bool)$decoded[$key];
                } else {
                    $default[$key] = trim((string)$decoded[$key]);
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — fall back to defaults.
        }

        return $default;
    }

    /**
     * Persist the pixel configuration (upsert on the `facebook_pixel` key).
     */
    public function save(array $data): void
    {
        $pixelId  = trim((string)($data['pixel_id'] ?? ''));
        $enabled  = !empty($data['enabled']);
        $testMode = !empty($data['test_mode']);

        $events = [
            'view_content'      => !empty($data['events']['view_content']),
            'add_to_cart'       => !empty($data['events']['add_to_cart']),
            'initiate_checkout' => !empty($data['events']['initiate_checkout']),
            'purchase'          => !empty($data['events']['purchase']),
        ];

        $payload = json_encode([
            'pixel_id' => $pixelId,
            'enabled'  => $enabled && $pixelId !== '',
            'test_mode' => $testMode,
            'events'   => $events,
        ], JSON_UNESCAPED_UNICODE);

        $this->query(
            "INSERT INTO {$this->table} (`key`, `value`) VALUES ('facebook_pixel', ?)
             ON DUPLICATE KEY UPDATE `value` = ?",
            [$payload, $payload]
        );
    }
}
<?php

namespace App\Models;

use App\Core\wsit_Model;

class wsit_Setting extends wsit_Model
{
    protected string $table = 'settings';

    /**
     * Fetch a single setting value by key.
     * Returns $default when the key does not exist in the table.
     */
    public function get(string $key, string $default = ''): string
    {
        $row = $this->fetch(
            "SELECT `value` FROM {$this->table} WHERE `key` = ? LIMIT 1",
            [$key]
        );
        return $row !== null ? (string) $row['value'] : $default;
    }

    /**
     * Upsert a setting value (insert or update on duplicate key).
     */
    public function set(string $key, string $value): void
    {
        $this->query(
            "INSERT INTO {$this->table} (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = ?",
            [$key, $value, $value]
        );
    }

    /**
     * Return all settings as an associative key => value array.
     */
    public function all(): array
    {
        $rows = $this->fetchAll("SELECT `key`, `value` FROM {$this->table}");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }
}

<?php

namespace App\Core;

class wsit_Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $files;
    private array $server;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function query(string|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(string|null $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return trim((string)($this->post[$key] ?? $default));
    }

    public function all(): array
    {
        return $this->post;
    }

    public function inputArray(string $key, array $default = []): array
    {
        $value = $this->post[$key] ?? $default;
        return is_array($value) ? $value : [];
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function server(string|null $key = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }
        return $this->server[$key] ?? null;
    }
}
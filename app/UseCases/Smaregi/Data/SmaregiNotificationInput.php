<?php

namespace App\UseCases\Smaregi\Data;

/**
 * Webhook / IDP 通知の HTTP 入力。Request 非依存。
 */
class SmaregiNotificationInput
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public readonly string $method,
        public readonly array $headers,
        public readonly string $rawBody,
        public readonly array $body,
    ) {}

    public function isGetOrHead(): bool
    {
        return in_array($this->method, ['GET', 'HEAD'], true);
    }

    public function header(string $name): ?string
    {
        $value = $this->headers[strtolower($name)] ?? null;
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body;
    }
}

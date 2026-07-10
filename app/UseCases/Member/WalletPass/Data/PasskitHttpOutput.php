<?php

namespace App\UseCases\Member\WalletPass\Data;

final class PasskitHttpOutput
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body = '',
        public readonly ?array $json = null,
        public readonly array $headers = [],
    ) {}

    public static function unauthorized(): self
    {
        return new self(401);
    }

    public static function badRequest(): self
    {
        return new self(400);
    }

    public static function empty(int $status): self
    {
        return new self($status);
    }

    public static function json(int $status, array $json): self
    {
        return new self($status, '', $json);
    }

    public static function passContent(string $content): self
    {
        return new self(200, $content, null, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="membership.pkpass"',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public static function passGenerateFailed(): self
    {
        return new self(500, 'pass generate failed', null, [
            'Content-Type' => 'text/plain',
        ]);
    }
}

<?php

namespace App\UseCases\Diagnostics\Data;

/**
 * クライアントデバッグログ UseCase への入力。不変。
 */
class ClientDebugLogInput
{
    public function __construct(
        public readonly string $event,
        public readonly string $projectKey,
        public readonly string $path,
        public readonly string $userAgent,
        public readonly string $timestamp,
        public readonly string $payload,
        public readonly string $method,
    ) {}
}

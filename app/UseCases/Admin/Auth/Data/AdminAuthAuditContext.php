<?php

namespace App\UseCases\Admin\Auth\Data;

final class AdminAuthAuditContext
{
    public function __construct(
        public readonly ?string $routeName,
        public readonly string $method,
        public readonly string $url,
        public readonly ?string $ip,
        public readonly string $userAgent,
    ) {}
}

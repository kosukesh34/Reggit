<?php

namespace App\UseCases\Admin\Auth\Data;

use Illuminate\Http\Request;

class AdminRequestContext
{
    public function __construct(
        public string $method,
        public string $fullUrl,
        public ?string $ip,
        public string $userAgent,
        public ?string $routeName,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $route = $request->route();

        return new self(
            $request->getMethod(),
            $request->fullUrl(),
            $request->ip(),
            (string) $request->userAgent(),
            $route ? $route->getName() : null,
        );
    }
}

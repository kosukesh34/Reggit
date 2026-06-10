<?php

namespace App\UseCases\Admin\Auth\Data;

use Illuminate\Http\Request;

class AdminLoginInput
{
    public function __construct(
        public string $loginId,
        public string $password,
        public bool $remember,
        public string $path,
        public ?string $projectKey,
        public AdminRequestContext $context,
    ) {}

    public static function fromRequest(Request $request, ?string $projectKey = null): self
    {
        return new self(
            (string) $request->input('login_id'),
            (string) $request->input('password'),
            $request->boolean('remember'),
            $request->path(),
            $projectKey,
            AdminRequestContext::fromRequest($request),
        );
    }
}

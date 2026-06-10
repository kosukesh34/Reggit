<?php

namespace App\UseCases\Admin\Auth\Data;

use Illuminate\Http\Request;

class AdminLogoutInput
{
    public function __construct(
        public bool $isSystemAdminLogout,
        public AdminRequestContext $context,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->routeIs('system-admin.logout'),
            AdminRequestContext::fromRequest($request),
        );
    }
}

<?php

namespace App\Http\Support;

use App\Models\AdminUser;
use Illuminate\Http\Request;

class StaffManagementAuthorizer
{
    public function __construct(private AuthenticatedAdminResolver $authenticatedAdminResolver) {}

    public function authorize(Request $request): void
    {
        $admin = $this->authenticatedAdminResolver->resolve($request);
        abort_unless($admin->canCreateRole(AdminUser::ROLE_STAFF), 403);
    }
}

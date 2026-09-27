<?php

namespace App\Http\Support;

use App\Models\AdminUserPermission;

class StaffPermissionCatalog
{
    /** @return array<string, string> */
    public function labels(): array
    {
        return AdminUserPermission::ALL_PERMISSIONS;
    }

    /** @return array<string, array<string, string>> */
    public function groups(): array
    {
        return AdminUserPermission::PERMISSION_GROUPS;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->labels());
    }
}

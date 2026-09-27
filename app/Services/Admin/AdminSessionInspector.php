<?php

namespace App\Services\Admin;

use App\Models\AdminUser;

class AdminSessionInspector
{
    public function isAuthenticated(): bool
    {
        return auth('admin')->check();
    }

    public function isSystemAdmin(): bool
    {
        $admin = auth('admin')->user();

        return $admin instanceof AdminUser && $admin->isSystemAdmin();
    }
}

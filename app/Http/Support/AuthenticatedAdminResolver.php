<?php

namespace App\Http\Support;

use App\Models\AdminUser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class AuthenticatedAdminResolver
{
    /**
     * @throws AuthenticationException
     */
    public function resolve(Request $request): AdminUser
    {
        $admin = $request->user('admin');
        if (! $admin instanceof AdminUser) {
            throw new AuthenticationException;
        }

        return $admin;
    }
}

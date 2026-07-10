<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminUser;
use App\Repositories\Contracts\AdminUserRepository;

class EloquentAdminUserRepository implements AdminUserRepository
{
    public function findByLoginIdOrEmail(string $loginId): ?AdminUser
    {
        return AdminUser::query()
            ->where('login_id', $loginId)
            ->first()
            ?? AdminUser::query()->where('email', $loginId)->first();
    }
}

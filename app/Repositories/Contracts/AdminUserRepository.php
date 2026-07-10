<?php

namespace App\Repositories\Contracts;

use App\Models\AdminUser;

interface AdminUserRepository
{
    public function findByLoginIdOrEmail(string $loginId): ?AdminUser;
}

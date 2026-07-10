<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepository
{
    public function findByIdAndProject(int $userId, int $projectId): ?User;

    public function findByLineUserIdAndProject(string $lineUserId, int $projectId): ?User;
}

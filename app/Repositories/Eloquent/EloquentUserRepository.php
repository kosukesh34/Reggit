<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;

class EloquentUserRepository implements UserRepository
{
    public function findByIdAndProject(int $userId, int $projectId): ?User
    {
        return User::query()
            ->where('id', $userId)
            ->where('project_id', $projectId)
            ->first();
    }

    public function findByLineUserIdAndProject(string $lineUserId, int $projectId): ?User
    {
        return User::query()
            ->where('line_user_id', $lineUserId)
            ->where('project_id', $projectId)
            ->first();
    }
}

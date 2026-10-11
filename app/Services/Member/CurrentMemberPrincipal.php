<?php

namespace App\Services\Member;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Resolve the current authenticated user and optional membership under caller-owned locks. */
class CurrentMemberPrincipal
{
    public function lock(User $snapshot, int $projectId): ?User
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Current principal must be resolved inside a transaction.');
        }
        if ((int) $snapshot->project_id !== $projectId) {
            return null;
        }
        $user = User::query()->where('project_id', $projectId)->whereKey($snapshot->id)->lockForUpdate()->first();
        if ($user === null || (int) $user->authentication_version !== (int) $snapshot->authentication_version) {
            return null;
        }
        $user->setRelation('member', $user->member()->where('project_id', $projectId)->lockForUpdate()->first());

        return $user;
    }
}

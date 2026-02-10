<?php

namespace App\Services\Admin;

use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Member::with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Member
    {
        return Member::with(['user', 'pointTransactions.order'])
            ->findOrFail($id);
    }
}

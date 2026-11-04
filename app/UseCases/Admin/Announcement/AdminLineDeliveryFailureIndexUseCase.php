<?php

namespace App\UseCases\Admin\Announcement;

use App\Models\LineDeliveryFailure;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AdminLineDeliveryFailureIndexUseCase
{
    private const PER_PAGE = 50;

    private const BLOCKED_USERS_LIMIT = 50;

    /** @return array{failures: LengthAwarePaginator<int, LineDeliveryFailure>, reason: string, blockedUserCount: int, blockedUsers: Collection<int, User>} */
    public function handle(int $projectId, ?string $reason): array
    {
        $reason = array_key_exists((string) $reason, LineDeliveryFailure::REASON_LABELS) ? (string) $reason : 'all';

        $failures = LineDeliveryFailure::query()
            ->where('project_id', $projectId)
            ->when($reason !== 'all', fn ($query) => $query->where('reason', $reason))
            ->with(['member.user', 'announcement:id,title'])
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'failures' => $failures,
            'reason' => $reason,
            'blockedUserCount' => User::query()->where('project_id', $projectId)->whereNotNull('line_blocked_at')->count(),
            // 配信前にブロックされた人は未送信の履歴に出ないため、今ブロック中の人を別に並べる
            'blockedUsers' => User::query()
                ->where('project_id', $projectId)
                ->whereNotNull('line_blocked_at')
                ->with('member:id,user_id')
                ->latest('line_blocked_at')
                ->limit(self::BLOCKED_USERS_LIMIT)
                ->get(['id', 'name', 'line_name', 'line_blocked_at']),
        ];
    }
}

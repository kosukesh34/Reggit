<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\RankSettings;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Support\MemberChannel;
use App\Util\DateUtil;
use App\Util\StoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

/** 会員一覧とエクスポートで共通の絞り込み・並び替えを担当する。 */
class MemberAdminListQueryService
{
    public function __construct(private RankSettings $shopSettingRepository, private StoreContext $storeContext) {}

    /** @var array<string, string> */
    private const LIST_SORT_COLUMNS = [
        'id' => 'members.id',
        'name' => 'users.name',
        'points' => 'members.points',
        'last_visited_at' => 'members.last_visited_at',
        'created_at' => 'members.created_at',
    ];

    /**
     * @param  array<string, string|null>  $filters
     */
    public function getPaginatedList(int $projectId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->memberListQuery($projectId, $filters)
            ->with(['user.devices', 'tags'])
            ->paginate($perPage);
    }

    /**
     * @param  array<string, string|null>  $filters
     */
    public function getFilteredMembers(int $projectId, array $filters = []): Collection
    {
        return $this->memberListQuery($projectId, $filters)
            ->with(['user.devices'])
            ->get();
    }

    /**
     * @param  array<string, string|null>  $filters
     */
    private function memberListQuery(int $projectId, array $filters): Builder
    {
        $query = Member::query()
            ->leftJoin('users', function ($join): void {
                $join->on('users.id', '=', 'members.user_id')
                    ->whereNull('users.deleted_at');
            })
            ->select('members.*')
            ->where('members.project_id', $projectId)
            ->where('members.status', MemberStatus::ACTIVE);
        $this->applyMemberListFilters($query, $filters);
        $this->applyMemberListSort($query, $projectId, $filters);

        return $query;
    }

    /**
     * @param  array<string, string|null>  $filters
     */
    private function applyMemberListFilters(Builder $query, array $filters): void
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%'.$keyword.'%';
            $query->where(function (Builder $q) use ($like, $keyword): void {
                $q->where('members.member_number', 'like', $like)
                    ->orWhere('users.name', 'like', $like)
                    ->orWhere('users.kana', 'like', $like)
                    ->orWhere('users.phone', 'like', $like)
                    ->orWhere('users.email', 'like', $like);
                if (ctype_digit($keyword)) {
                    $q->orWhere('members.id', (int) $keyword);
                }
            });
        }

        $rank = trim((string) ($filters['rank'] ?? ''));
        if ($rank !== '') {
            $query->where('members.rank', $rank);
        }

        $this->applyMemberListChannelFilter($query, trim((string) ($filters['channel'] ?? '')));

        $tagId = $filters['tag_id'] ?? '';
        if (is_numeric($tagId) && (int) $tagId > 0) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('member_tags.id', (int) $tagId));
        }

        $visitedFrom = DateUtil::nullableCarbon($filters['visited_from'] ?? null);
        if ($visitedFrom !== null) {
            $query->where($this->lastVisitColumn(), '>=', $visitedFrom->startOfDay());
        }
        $visitedTo = DateUtil::nullableCarbon($filters['visited_to'] ?? null);
        if ($visitedTo !== null) {
            $query->where($this->lastVisitColumn(), '<=', $visitedTo->endOfDay());
        }
    }

    private function applyMemberListChannelFilter(Builder $query, string $channel): void
    {
        if ($channel === MemberChannel::LINE) {
            $query->whereRaw('users.line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);

            return;
        }
        if ($channel === MemberChannel::APP) {
            $query->whereHas('user.devices');

            return;
        }
        if ($channel === MemberChannel::BOTH) {
            $query->whereRaw('users.line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$'])
                ->whereHas('user.devices');
        }
    }

    /**
     * @param  array<string, string|null>  $filters
     */
    private function applyMemberListSort(Builder $query, int $projectId, array $filters): void
    {
        $sort = (string) ($filters['sort'] ?? 'created_at');
        $dir = ($filters['dir'] ?? '') === 'asc' ? 'asc' : 'desc';
        if ($sort === 'rank') {
            $this->applyMemberListRankSort($query, $projectId, $dir);

            return;
        }
        if (! array_key_exists($sort, self::LIST_SORT_COLUMNS)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort === 'last_visited_at' ? $this->lastVisitColumn() : self::LIST_SORT_COLUMNS[$sort], $dir)
            ->orderByDesc('members.id');
    }

    private function lastVisitColumn(): string|Expression
    {
        if ($this->storeContext->projectStoreCount <= 1 || $this->storeContext->visibleStoreIds === null) {
            return 'members.last_visited_at';
        }
        $ids = implode(',', array_map('intval', $this->storeContext->visibleStoreIds));
        $ids = $ids === '' ? '0' : $ids;

        return new Expression("(select max(visited_at) from member_visits where member_visits.member_id = members.id and member_visits.project_id = members.project_id and member_visits.store_id in ($ids))");
    }

    private function applyMemberListRankSort(Builder $query, int $projectId, string $dir): void
    {
        $sql = 'CASE members.rank';
        $bindings = [];
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            $key = trim((string) ($rank['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $sql .= ' WHEN ? THEN ?';
            $bindings[] = $key;
            $bindings[] = (int) ($rank['min_points'] ?? 0);
        }
        $sql .= ' ELSE 0 END';
        if ($bindings === []) {
            $query->orderBy('members.rank', $dir)->orderByDesc('members.id');

            return;
        }
        $query->orderByRaw($sql.' '.$dir, $bindings)->orderByDesc('members.id');
    }
}

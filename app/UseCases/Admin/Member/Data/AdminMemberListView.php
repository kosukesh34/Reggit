<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\MemberTag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** 管理画面会員一覧の表示用データ。 */
class AdminMemberListView
{
    /**
     * @param  array<string, string|null>  $filters
     * @param  array<string, string>  $rankOptions
     * @param  Collection<int, MemberTag>  $tags
     */
    public function __construct(
        public LengthAwarePaginator $members,
        public array $filters,
        public string $lineInviteUrl,
        public string $lineInviteLabel,
        public array $rankOptions,
        public Collection $tags,
    ) {}

    public function hasSearchFilters(): bool
    {
        foreach (['keyword', 'rank', 'channel', 'tag_id', 'visited_from', 'visited_to'] as $key) {
            if (trim((string) ($this->filters[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string> */
    public function queryFilters(): array
    {
        $kept = [];
        foreach ($this->filters as $key => $value) {
            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }
            $string = trim((string) $value);
            if ($string === '') {
                continue;
            }
            $kept[$key] = $string;
        }

        return $kept;
    }

    public function sortHref(string $column): string
    {
        $currentSort = (string) ($this->filters['sort'] ?? 'created_at');
        $currentDir = (string) ($this->filters['dir'] ?? 'desc');
        $nextDir = ($currentSort === $column && $currentDir === 'desc') ? 'asc' : 'desc';
        $query = $this->queryFilters();
        $query['sort'] = $column;
        $query['dir'] = $nextDir;

        return admin_route('admin.members', $query);
    }

    public function isSortedBy(string $column): bool
    {
        return (string) ($this->filters['sort'] ?? 'created_at') === $column;
    }

    public function sortIconClass(string $column): string
    {
        if (! $this->isSortedBy($column)) {
            return 'fas fa-sort';
        }

        return ((string) ($this->filters['dir'] ?? 'desc')) === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    }

    public function rankLabel(?string $rank): string
    {
        if ($rank === null || $rank === '') {
            return '—';
        }

        return $this->rankOptions[$rank] ?? $rank;
    }
}

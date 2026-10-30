<?php

namespace App\UseCases\Admin\Member\Data;

use App\Models\Coupon;
use App\Models\MemberAudienceGroup;
use App\Models\MemberTag;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** 管理画面会員一覧の表示用データ。 */
class AdminMemberListView
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $rankOptions
     * @param  Collection<int, MemberTag>  $memberTags
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Coupon>  $coupons
     * @param  array<string, array{label: string, color: string}>  $rfmSegments
     * @param  list<array<string, mixed>>  $profileFields
     * @param  Collection<int, MemberAudienceGroup>  $audienceGroups
     */
    public function __construct(
        public LengthAwarePaginator $members,
        public array $filters,
        public string $lineInviteUrl,
        public string $lineInviteLabel,
        public array $rankOptions,
        public Collection $memberTags,
        public Collection $products,
        public Collection $coupons,
        public array $rfmSegments,
        public array $profileFields,
        public Collection $audienceGroups,
        public bool $hasSegmentFilters = false,
    ) {}

    public function hasSearchFilters(): bool
    {
        if ($this->hasSegmentFilters) {
            return true;
        }
        foreach (['keyword', 'rank', 'channel', 'tag_id', 'visited_from', 'visited_to'] as $key) {
            if (trim((string) ($this->filters[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function queryFilters(): array
    {
        $kept = [];
        foreach ($this->filters as $key => $value) {
            if (is_array($value)) {
                if ($value !== []) {
                    $kept[$key] = $value;
                }

                continue;
            }
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

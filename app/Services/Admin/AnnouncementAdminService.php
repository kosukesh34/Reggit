<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class AnnouncementAdminService
{
    /** @return list<string> */
    public function deliveryTypeFilterKeys(): array
    {
        return [
            'all',
            Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            Announcement::DELIVERY_TYPE_PERSONAL,
            Announcement::DELIVERY_TYPE_SCHEDULED,
            Announcement::DELIVERY_TYPE_SEGMENT,
            Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ];
    }

    /**
     * @param  array<string, string|null>  $input
     * @return array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }
     */
    public function normalizeIndexFilters(array $input): array
    {
        return [
            'delivery_type' => $this->normalizeDeliveryTypeFilter($input['delivery_type'] ?? null),
            'keyword' => trim((string) ($input['keyword'] ?? '')),
            'published_from' => $this->normalizeDate($input['published_from'] ?? null) ?? '',
            'published_to' => $this->normalizeDate($input['published_to'] ?? null) ?? '',
        ];
    }

    public function normalizeDeliveryTypeFilter(?string $deliveryType): string
    {
        $normalized = trim((string) $deliveryType);
        if ($normalized === '' || ! in_array($normalized, $this->deliveryTypeFilterKeys(), true)) {
            return 'all';
        }

        return $normalized;
    }

    /**
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     */
    public function getPaginatedList(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->withCount('reads')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 配信形態タブの件数。期間・キーワード条件は反映し、delivery_type は除外する。
     *
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     * @return array<string, int>
     */
    public function getDeliveryTypeCounts(array $filters): array
    {
        $baseFilters = $filters;
        $baseFilters['delivery_type'] = 'all';
        $counts = ['all' => $this->buildFilteredQuery($baseFilters)->count()];
        foreach ($this->deliveryTypeFilterKeys() as $key) {
            if ($key === 'all') {
                continue;
            }
            $typeFilters = $baseFilters;
            $typeFilters['delivery_type'] = $key;
            $counts[$key] = $this->buildFilteredQuery($typeFilters)->count();
        }

        return $counts;
    }

    /**
     * @param  array{
     *     delivery_type: string,
     *     keyword: string,
     *     published_from: string,
     *     published_to: string,
     * }  $filters
     * @return Builder<Announcement>
     */
    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Announcement::query();

        if ($filters['delivery_type'] !== 'all') {
            $query->where('delivery_type', $filters['delivery_type']);
        }

        if ($filters['keyword'] !== '') {
            $keyword = $filters['keyword'];
            $query->where('title', 'like', '%'.addcslashes($keyword, '%_\\').'%');
        }

        if ($filters['published_from'] !== '') {
            $query->where('published_at', '>=', Carbon::parse($filters['published_from'])->startOfDay());
        }

        if ($filters['published_to'] !== '') {
            $query->where('published_at', '<=', Carbon::parse($filters['published_to'])->endOfDay());
        }

        return $query;
    }

    private function normalizeDate(?string $date): ?string
    {
        $normalized = trim((string) $date);
        if ($normalized === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) !== 1) {
            return null;
        }

        try {
            return Carbon::parse($normalized)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    public function create(int $projectId, array $data): Announcement
    {
        $data['project_id'] = $projectId;

        return Announcement::create($data);
    }

    public function findOrFail(int $id): Announcement
    {
        return Announcement::findOrFail($id);
    }

    /**
     * @return list<array{member_name: string, member_number: string, read_at: string}>
     */
    public function getReadsForAnnouncement(int $announcementId): array
    {
        $reads = AnnouncementRead::query()
            ->where('announcement_id', $announcementId)
            ->with(['user.member'])
            ->orderByDesc('read_at')
            ->orderByDesc('id')
            ->get();

        $rows = [];
        foreach ($reads as $read) {
            $user = $read->user;
            $member = $user?->member;
            $rows[] = [
                'member_name' => $user !== null && trim((string) $user->name) !== ''
                    ? (string) $user->name
                    : '—',
                'member_number' => $member !== null && trim((string) $member->member_number) !== ''
                    ? (string) $member->member_number
                    : '—',
                'read_at' => $read->read_at !== null
                    ? $read->read_at->format('Y/m/d H:i')
                    : '—',
            ];
        }

        return $rows;
    }

    public function update(int $id, array $data): Announcement
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update($data);

        return $announcement;
    }

    public function delete(int $id): void
    {
        Announcement::whereKey($id)->delete();
    }

    public function saveAnnouncementImage(UploadedFile $file, int $projectId): string
    {
        $path = $file->store('announcements/'.(string) $projectId, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store announcement image');
        }

        return asset('storage/'.$path);
    }
}

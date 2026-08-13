<?php

namespace App\Services\Admin;

use App\Enums\StampRewardType;
use App\Models\Coupon;
use App\Models\Settings\ProjectLotterySetting;
use App\Models\StampCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StampAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return StampCard::orderByDesc('id')->paginate($perPage);
    }

    public function create(int $projectId, array $data): StampCard
    {
        $data = $this->normalizePayload($data, $projectId);
        $data['project_id'] = $projectId;

        return StampCard::create($data);
    }

    public function findOrFail(int $id): StampCard
    {
        return StampCard::findOrFail($id);
    }

    public function update(int $id, array $data): StampCard
    {
        $card = StampCard::findOrFail($id);
        $data = $this->normalizePayload($data, (int) $card->project_id);
        $card->update($data);

        return $card;
    }

    public function isGachaEnabled(int $projectId): bool
    {
        $settings = ProjectLotterySetting::query()->find($projectId);

        return $settings !== null && $settings->gacha_enabled === true;
    }

    /** @return Collection<int, Coupon> */
    public function getCouponsForStampForm(int $projectId, ?StampCard $stampCard): Collection
    {
        $includeIds = [];
        $milestones = $stampCard !== null && is_array($stampCard->reward_milestones)
            ? $stampCard->reward_milestones
            : [];
        foreach ($milestones as $milestone) {
            if (! is_array($milestone)) {
                continue;
            }
            $couponId = (int) ($milestone['coupon_id'] ?? 0);
            if ($couponId > 0) {
                $includeIds[] = $couponId;
            }
        }

        return $this->getCouponsForRewardSelect($projectId, $includeIds);
    }

    /**
     * @param  list<int>  $includeCouponIds
     * @return Collection<int, Coupon>
     */
    public function getCouponsForRewardSelect(int $projectId, array $includeCouponIds = []): Collection
    {
        $includeCouponIds = array_values(array_unique(array_filter(
            $includeCouponIds,
            static fn (int $id): bool => $id > 0
        )));

        return Coupon::query()
            ->where('project_id', $projectId)
            ->where(function ($query) use ($includeCouponIds): void {
                $query->where('is_active', true);
                if ($includeCouponIds !== []) {
                    $query->orWhereIn('id', $includeCouponIds);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);
    }

    public function delete(int $id): void
    {
        StampCard::whereKey($id)->delete();
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, int $projectId): array
    {
        $data = $this->normalizeMilestones($data, $projectId);
        $data = $this->normalizeCardImage($data);
        $data = $this->normalizeStampImages($data);

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeMilestones(array $data, int $projectId): array
    {
        $milestones = $data['reward_milestones'] ?? null;
        if (! is_array($milestones)) {
            return $data;
        }

        $couponNames = $this->couponNamesForMilestones($milestones, $projectId);
        $normalized = [];
        foreach ($milestones as $milestone) {
            if (! is_array($milestone)) {
                continue;
            }
            $item = $this->normalizeMilestone($milestone, $couponNames);
            if ($item === null) {
                continue;
            }
            $normalized[] = $item;
        }

        usort(
            $normalized,
            static fn (array $a, array $b): int => (int) $a['stamp_count'] <=> (int) $b['stamp_count']
        );
        $byStampCount = [];
        foreach ($normalized as $item) {
            $byStampCount[(string) $item['stamp_count']] = $item;
        }
        $data['reward_milestones'] = array_values($byStampCount);

        return $data;
    }

    /**
     * @param  array<int|string, mixed>  $milestones
     * @return array<int, string>
     */
    private function couponNamesForMilestones(array $milestones, int $projectId): array
    {
        $ids = [];
        foreach ($milestones as $milestone) {
            if (! is_array($milestone)) {
                continue;
            }
            if (StampRewardType::normalize((string) ($milestone['reward_type'] ?? '')) !== StampRewardType::COUPON) {
                continue;
            }
            $couponId = (int) ($milestone['coupon_id'] ?? 0);
            if ($couponId > 0) {
                $ids[] = $couponId;
            }
        }
        if ($ids === []) {
            return [];
        }

        return Coupon::query()
            ->where('project_id', $projectId)
            ->whereIn('id', array_values(array_unique($ids)))
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $milestone
     * @param  array<int, string>  $couponNames
     * @return array{stamp_count: int, reward_type: string, description: string, coupon_id?: int}|null
     */
    private function normalizeMilestone(array $milestone, array $couponNames): ?array
    {
        $stampCount = (int) ($milestone['stamp_count'] ?? 0);
        if ($stampCount <= 0) {
            return null;
        }
        $rewardType = StampRewardType::normalize((string) ($milestone['reward_type'] ?? StampRewardType::TEXT));
        if ($rewardType === StampRewardType::COUPON) {
            $couponId = (int) ($milestone['coupon_id'] ?? 0);
            $name = trim((string) ($couponNames[$couponId] ?? ''));
            if ($couponId <= 0 || $name === '') {
                return null;
            }

            return [
                'stamp_count' => $stampCount,
                'reward_type' => $rewardType,
                'coupon_id' => $couponId,
                'description' => $name,
            ];
        }
        if ($rewardType === StampRewardType::GACHA) {
            return [
                'stamp_count' => $stampCount,
                'reward_type' => $rewardType,
                'description' => StampRewardType::GACHA_DESCRIPTION,
            ];
        }
        $description = trim((string) ($milestone['description'] ?? ''));
        if ($description === '') {
            return null;
        }

        return [
            'stamp_count' => $stampCount,
            'reward_type' => StampRewardType::TEXT,
            'description' => $description,
        ];
    }

    /** @param array<string, mixed> $data */
    private function normalizeCardImage(array $data): array
    {
        $imageData = $data['image_data'] ?? null;
        unset($data['image_data']);

        if (is_string($imageData) && $imageData !== '') {
            if (str_starts_with($imageData, 'data:image/')) {
                $url = $this->saveBase64Image($imageData, 'card');
                if ($url !== null) {
                    $data['image_url'] = $url;
                }
            } elseif (str_starts_with($imageData, 'http')) {
                $data['image_url'] = $imageData;
            }
        }

        return $data;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeStampImages(array $data): array
    {
        $data = $this->assignStampImageMap($data, 'stamp_images_data', 'stamp_images', 'stamp_');
        $data = $this->assignStampImageMap($data, 'stamp_empty_images_data', 'stamp_empty_images', 'stamp_empty_');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function assignStampImageMap(array $data, string $inputKey, string $column, string $prefix): array
    {
        $raw = $data[$inputKey] ?? null;
        unset($data[$inputKey]);
        if (! is_string($raw) || $raw === '') {
            return $data;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $data;
        }

        $processed = [];
        foreach ($decoded as $position => $imageData) {
            if (! is_string($imageData) || $imageData === '') {
                continue;
            }
            if (str_starts_with($imageData, 'data:image/')) {
                $url = $this->saveBase64Image($imageData, $prefix.$position);
                if ($url !== null) {
                    $processed[(string) $position] = $url;
                }
            } elseif (str_starts_with($imageData, 'http') || str_starts_with($imageData, '/uploads/')) {
                $processed[(string) $position] = $imageData;
            }
        }
        $data[$column] = $processed !== [] ? $processed : null;

        return $data;
    }

    private function saveBase64Image(string $dataUrl, string $prefix = 'stamp'): ?string
    {
        if (! preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/', $dataUrl, $matches)) {
            return null;
        }

        $ext = $matches[1];
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }

        $dir = public_path('uploads/stamps');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $prefix.'_'.uniqid().'.'.$ext;
        file_put_contents($dir.'/'.$filename, $binary);

        return '/uploads/stamps/'.$filename;
    }
}

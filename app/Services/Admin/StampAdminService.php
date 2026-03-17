<?php

namespace App\Services\Admin;

use App\Models\StampCard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StampAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return StampCard::orderByDesc('id')->paginate($perPage);
    }

    public function create(int $projectId, array $data): StampCard
    {
        $data = $this->normalizePayload($data);
        $data['project_id'] = $projectId;

        return StampCard::create($data);
    }

    public function findOrFail(int $id): StampCard
    {
        return StampCard::findOrFail($id);
    }

    public function update(int $id, array $data): StampCard
    {
        $data = $this->normalizePayload($data);
        $card = StampCard::findOrFail($id);
        $card->update($data);

        return $card;
    }

    public function delete(int $id): void
    {
        StampCard::whereKey($id)->delete();
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        $data = $this->normalizeMilestones($data);
        $data = $this->normalizeCardImage($data);
        $data = $this->normalizeStampImages($data);

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function normalizeMilestones(array $data): array
    {
        $milestones = $data['reward_milestones'] ?? null;
        if (is_array($milestones)) {
            $normalized = [];
            foreach ($milestones as $milestone) {
                if (!is_array($milestone)) {
                    continue;
                }
                $stampCount = (int) ($milestone['stamp_count'] ?? 0);
                $description = trim((string) ($milestone['description'] ?? ''));
                if ($stampCount <= 0 || $description === '') {
                    continue;
                }
                $normalized[] = [
                    'stamp_count' => $stampCount,
                    'description' => $description,
                ];
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
        }

        return $data;
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

    /** @param array<string, mixed> $data */
    private function normalizeStampImages(array $data): array
    {
        $raw = $data['stamp_images_data'] ?? null;
        unset($data['stamp_images_data']);

        if (!is_string($raw) || $raw === '') {
            return $data;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $data;
        }

        $processed = [];
        foreach ($decoded as $position => $imageData) {
            if (!is_string($imageData) || $imageData === '') {
                continue;
            }

            if (str_starts_with($imageData, 'data:image/')) {
                $url = $this->saveBase64Image($imageData, 'stamp_'.$position);
                if ($url !== null) {
                    $processed[(string) $position] = $url;
                }
            } elseif (str_starts_with($imageData, 'http') || str_starts_with($imageData, '/uploads/')) {
                $processed[(string) $position] = $imageData;
            }
        }

        $data['stamp_images'] = $processed !== [] ? $processed : null;

        return $data;
    }

    private function saveBase64Image(string $dataUrl, string $prefix = 'stamp'): ?string
    {
        if (!preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/', $dataUrl, $matches)) {
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
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $prefix.'_'.uniqid().'.'.$ext;
        file_put_contents($dir.'/'.$filename, $binary);

        return '/uploads/stamps/'.$filename;
    }
}

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
        $milestones = $data['reward_milestones'] ?? null;
        if (is_array($milestones)) {
            $normalized = [];
            foreach ($milestones as $milestone) {
                if (! is_array($milestone)) {
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
}

<?php

namespace App\UseCases\Queue;

use App\Models\QueueEntry;
use App\Util\AdminContext;
use Illuminate\Support\Collection;

/**
 * キュー一覧取得（iPad・受付画面用）。project_key でスコープされた一覧を返す。
 */
class QueueListUseCase
{
    private const MAX_ENTRIES = 100;

    public function handle(?int $projectId = null): array
    {
        $projectId = $projectId ?? AdminContext::getCurrentProjectId();
        if ($projectId === null) {
            return [];
        }

        $entries = QueueEntry::with('member.user', 'user')
            ->where('project_id', $projectId)
            ->orderByRaw("CASE status WHEN 'waiting' THEN 0 WHEN 'called' THEN 1 WHEN 'entered' THEN 2 ELSE 3 END")
            ->orderBy('queue_number')
            ->orderByDesc('created_at')
            ->limit(self::MAX_ENTRIES)
            ->get();

        return $entries->map(fn (QueueEntry $e) => $this->toListItem($e))->values()->all();
    }

    /** @return array{id: int, queue_number: int, status: string, party_size: int, line_linked: bool, member_linked: bool, display_name: string, created_at: string} */
    private function toListItem(QueueEntry $e): array
    {
        $displayName = $e->member?->user?->name ?? $e->user?->name ?? $e->guest_name ?? '—';
        $lineLinked = $this->isLineLinked($e);
        $memberLinked = $e->member_id !== null || $e->user_id !== null;

        return [
            'id' => $e->id,
            'queue_number' => (int) ($e->queue_number ?? $e->id),
            'status' => $e->status,
            'party_size' => (int) $e->party_size,
            'line_linked' => $lineLinked,
            'member_linked' => $memberLinked,
            'display_name' => $displayName,
            'created_at' => $e->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }

    private function isLineLinked(QueueEntry $e): bool
    {
        $lineUserId = $e->user?->line_user_id ?? $e->member?->user?->line_user_id ?? '';

        return is_string($lineUserId) && preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}

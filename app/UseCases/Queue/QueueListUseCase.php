<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry;
use App\Support\QueueLinkChannel;
use App\Util\AdminContext;

/**
 * キュー一覧取得（iPad・受付画面用）。project_key でスコープされた一覧を返す。
 */
class QueueListUseCase
{
    private const MAX_ENTRIES = 30;

    public function handle(?int $projectId = null): array
    {
        $projectId = $projectId ?? AdminContext::getCurrentProjectId();
        if ($projectId === null) {
            return [];
        }

        $entries = QueueEntry::with('member.user', 'user.devices')
            ->where('project_id', $projectId)
            ->where('created_at', '>=', now()->subDay())
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderByRaw("CASE status WHEN 'waiting' THEN 0 WHEN 'called' THEN 1 ELSE 2 END")
            ->orderBy('queue_number')
            ->orderByDesc('created_at')
            ->limit(self::MAX_ENTRIES)
            ->get();

        return $entries->map(fn (QueueEntry $e) => $this->toListItem($e))->values()->all();
    }

    /** @return array{id: int, queue_number: int, formatted_queue_number: string, status: string, party_size: int, line_linked: bool, link_channel: string, member_linked: bool, display_name: string, created_at: string} */
    private function toListItem(QueueEntry $e): array
    {
        $displayName = $e->member?->user?->name ?? $e->user?->name ?? $e->guest_name ?? '—';
        $linkChannel = QueueLinkChannel::resolve($e->user, $e->user_id !== null ? (int) $e->user_id : null);
        $memberLinked = $e->member_id !== null || $e->user_id !== null;

        return [
            'id' => $e->id,
            'queue_number' => (int) ($e->queue_number ?? $e->id),
            'formatted_queue_number' => $e->formattedQueueNumber(),
            'status' => $e->status,
            'party_size' => (int) $e->party_size,
            'line_linked' => $linkChannel !== QueueLinkChannel::NONE,
            'link_channel' => $linkChannel,
            'member_linked' => $memberLinked,
            'display_name' => $displayName,
            'created_at' => $e->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}

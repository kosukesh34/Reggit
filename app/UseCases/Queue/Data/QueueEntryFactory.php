<?php

namespace App\UseCases\Queue\Data;

use App\Models\QueueEntry as QueueEntryModel;
use App\Support\QueueLinkChannel;

/** QueueEntry モデルから表示用 QueueEntry を組み立てる。 */
class QueueEntryFactory
{
    public function create(QueueEntryModel $entry, array $stats): QueueEntry
    {
        $entry->loadMissing(['user.devices', 'member.user.devices']);
        $user = $entry->user ?? $entry->member?->user;
        $userId = $entry->user_id !== null
            ? (int) $entry->user_id
            : ($user !== null ? (int) $user->id : null);
        $linkChannel = QueueLinkChannel::resolve($user, $userId);

        return new QueueEntry(
            $entry->id,
            $entry->queue_number ?? 0,
            $entry->formattedQueueNumber(),
            $entry->status,
            $entry->party_size,
            $linkChannel !== QueueLinkChannel::NONE,
            $linkChannel,
            $entry->qr_token,
            $this->formatDate($entry->created_at) ?? now()->toIso8601String(),
            $this->formatDate($entry->updated_at) ?? now()->toIso8601String(),
            $this->formatDate($entry->called_at),
            $stats['waiting_count'],
            $stats['ahead_count'],
            $stats['estimated_wait_minutes']
        );
    }

    private function formatDate($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if (is_string($value)) {
            return $value;
        }

        return null;
    }
}

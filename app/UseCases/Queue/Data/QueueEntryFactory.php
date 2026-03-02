<?php

namespace App\UseCases\Queue\Data;

use App\Models\QueueEntry as QueueEntryModel;

/** QueueEntry モデルから表示用 QueueEntry を組み立てる。 */
class QueueEntryFactory
{
    public function create(QueueEntryModel $entry, array $stats): QueueEntry
    {
        return new QueueEntry(
            $entry->id,
            $entry->queue_number ?? 0,
            $entry->status,
            $entry->party_size,
            $this->isLineLinked($entry),
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

    private function isLineLinked(QueueEntryModel $entry): bool
    {
        if ($entry->relationLoaded('user') && $entry->user !== null) {
            $lineUserId = (string) ($entry->user->line_user_id ?? '');

            return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
        }

        return $entry->user_id !== null;
    }
}

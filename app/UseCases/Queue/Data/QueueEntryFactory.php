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
            $entry->formattedQueueNumber(),
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
        // 通知先ユーザーが紐づいていれば連携済み（LINE / ネイティブ共通）
        return $entry->user_id !== null;
    }
}

<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueMeInput;
use App\UseCases\Queue\Data\QueueMeOutput;
use App\Util\StrHelper;

class QueueMeUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
    ) {}

    public function handle(QueueMeInput $input): QueueMeOutput
    {
        $entry = $this->findActiveEntryFromToken($input);
        if ($entry === null) {
            $entry = $this->findActiveEntryFromSession($input);
        }
        if ($entry !== null && $this->isValidLineUserId($input->lineUserId)) {
            $this->linkEntryToLineUserIfNeeded($input, $entry);
            $entry->refresh();
        }

        $user = $input->user;
        $member = $user->member;
        $projectId = $member !== null ? $member->project_id : $input->projectId;

        if ($entry === null) {
            $entry = $this->getLatestEntry($user->id, $member?->id, $projectId);
            if ($entry !== null) {
                $projectId = $entry->project_id;
            }
        } else {
            $projectId = $entry->project_id;
        }

        $stats = $this->statsService->getStats($projectId, $entry?->queue_number);

        if ($entry === null) {
            return new QueueMeOutput(
                null,
                $stats['waiting_count'],
                $stats['estimated_wait_minutes'],
                shouldForgetSession: true,
                sessionProjectId: (int) $projectId,
            );
        }

        $queueEntry = $this->entryFactory->create($entry, $stats);

        return new QueueMeOutput(
            $queueEntry,
            $stats['waiting_count'],
            $stats['estimated_wait_minutes'],
            sessionEntryIdToRemember: (int) $entry->id,
            sessionProjectId: (int) $entry->project_id,
        );
    }

    private function getLatestEntry(int $userId, ?int $memberId, int $projectId): ?QueueEntryModel
    {
        $entry = QueueEntryModel::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderBy('created_at', 'desc')
            ->first();
        if ($entry !== null) {
            return $entry;
        }
        if ($memberId === null) {
            return null;
        }

        return QueueEntryModel::where('project_id', $projectId)
            ->where('member_id', $memberId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function findActiveEntryFromToken(QueueMeInput $input): ?QueueEntryModel
    {
        if ($input->queueToken !== null) {
            return QueueEntryModel::where('project_id', $input->projectId)
                ->where('qr_token', $input->queueToken)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
        }

        if ($input->queueId !== null) {
            return QueueEntryModel::where('project_id', $input->projectId)
                ->whereKey($input->queueId)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
        }

        foreach ($input->embeddedRawParams as $raw) {
            [$embeddedToken, $embeddedId] = $this->extractQueueIdentifierFromRaw($raw);

            if ($embeddedToken !== null) {
                $entry = QueueEntryModel::where('project_id', $input->projectId)
                    ->where('qr_token', $embeddedToken)
                    ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                    ->first();
                if ($entry !== null) {
                    return $entry;
                }
            }

            if ($embeddedId !== null) {
                $entry = QueueEntryModel::where('project_id', $input->projectId)
                    ->whereKey($embeddedId)
                    ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                    ->first();
                if ($entry !== null) {
                    return $entry;
                }
            }
        }

        return null;
    }

    private function findActiveEntryFromSession(QueueMeInput $input): ?QueueEntryModel
    {
        if ($input->sessionEntryId === null) {
            return null;
        }

        return QueueEntryModel::where('project_id', $input->projectId)
            ->whereKey($input->sessionEntryId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->first();
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function extractQueueIdentifierFromRaw(mixed $raw): array
    {
        if (! is_string($raw)) {
            return [null, null];
        }

        $candidates = [];
        $value = trim($raw);
        if ($value !== '') {
            $candidates[] = $value;
            $decoded = $value;
            for ($i = 0; $i < 2; $i++) {
                $next = urldecode($decoded);
                if ($next === $decoded) {
                    break;
                }
                $decoded = $next;
                $candidates[] = $decoded;
            }
        }

        foreach ($candidates as $candidate) {
            [$token, $id] = $this->extractQueueIdentifierFromString($candidate);
            if ($token !== null || $id !== null) {
                return [$token, $id];
            }
        }

        return [null, null];
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function extractQueueIdentifierFromString(string $value): array
    {
        $normalized = ltrim($value, '#');
        $params = [];

        $parts = parse_url($normalized);
        if (is_array($parts)) {
            if (isset($parts['query'])) {
                parse_str($parts['query'], $params);
            } elseif (isset($parts['fragment'])) {
                parse_str($parts['fragment'], $params);
            }
        }

        if (! is_array($params) || $params === []) {
            $query = str_contains($normalized, '?')
                ? (string) parse_url($normalized, PHP_URL_QUERY)
                : $normalized;
            parse_str((string) $query, $params);
        }

        $token = isset($params['queue_token']) && is_string($params['queue_token'])
            ? trim($params['queue_token'])
            : null;
        if ($token === '') {
            $token = null;
        }

        $queueIdRaw = isset($params['queue_id']) && is_string($params['queue_id'])
            ? trim($params['queue_id'])
            : '';
        $queueId = ctype_digit($queueIdRaw) ? (int) $queueIdRaw : null;

        return [$token, $queueId];
    }

    private function linkEntryToLineUserIfNeeded(QueueMeInput $input, QueueEntryModel $entry): void
    {
        if ($entry->user_id !== null) {
            return;
        }

        $user = $input->user;
        $entry->user_id = $user->id;
        if ($user->member !== null) {
            $entry->member_id = $user->member->id;
        }
        $entry->save();
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        if (StrHelper::isEmptyStringOrNull($lineUserId)) {
            return false;
        }

        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }
}

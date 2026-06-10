<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\Models\User;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueMeInput;
use App\UseCases\Queue\Data\QueueMeOutput;
use App\Services\Queue\QueueEntrySessionStore;
use App\Util\AdminContext;
use App\Util\StrHelper;

class QueueMeUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
        private QueueEntrySessionStore $sessionStore,
    ) {}

    public function handle(QueueMeInput $input): QueueMeOutput
    {
        $currentProjectId = AdminContext::getCurrentProjectId() ?? 1;
        $lineUserId = $input->lineUserId;

        $entry = $this->findActiveEntryFromInput($input, $currentProjectId);
        if ($entry === null) {
            $entry = $this->findActiveEntryFromSession($currentProjectId);
        }
        if ($entry !== null && $this->isValidLineUserId($lineUserId)) {
            $this->linkEntryToLineUserIfNeeded($input->user, $entry);
            $entry->refresh();
        }

        $user = $input->user;
        $member = $user->member;
        $projectId = $member ? $member->project_id : $currentProjectId;

        if ($entry === null) {
            $entry = $this->getLatestEntry($user->id, $member?->id, $projectId);
            if ($entry !== null) {
                $projectId = $entry->project_id;
            }
        } else {
            $projectId = $entry->project_id;
        }

        if ($entry !== null) {
            $this->sessionStore->putEntryId((int) $entry->project_id, (int) $entry->id);
        } else {
            $this->sessionStore->forgetEntryId($projectId);
        }

        $stats = $this->statsService->getStats($projectId, $entry?->queue_number);

        if ($entry === null) {
            return new QueueMeOutput(null, $stats['waiting_count'], $stats['estimated_wait_minutes']);
        }

        $queueEntry = $this->entryFactory->create($entry, $stats);

        return new QueueMeOutput($queueEntry, $stats['waiting_count'], $stats['estimated_wait_minutes']);
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

    private function findActiveEntryFromInput(QueueMeInput $input, int $projectId): ?QueueEntryModel
    {
        if ($input->queueToken !== null) {
            return QueueEntryModel::where('project_id', $projectId)
                ->where('qr_token', $input->queueToken)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
        }

        if ($input->queueId !== null && ctype_digit($input->queueId)) {
            return QueueEntryModel::where('project_id', $projectId)
                ->whereKey((int) $input->queueId)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
        }

        foreach ($input->embeddedRawParams as $raw) {
            [$embeddedToken, $embeddedId] = $this->extractQueueIdentifierFromRaw($raw);

            if ($embeddedToken !== null) {
                $entry = QueueEntryModel::where('project_id', $projectId)
                    ->where('qr_token', $embeddedToken)
                    ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                    ->first();
                if ($entry !== null) {
                    return $entry;
                }
            }

            if ($embeddedId !== null) {
                $entry = QueueEntryModel::where('project_id', $projectId)
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

    private function findActiveEntryFromSession(int $projectId): ?QueueEntryModel
    {
        if (! $this->sessionStore->hasSession()) {
            return null;
        }

        $entryId = $this->sessionStore->getEntryId($projectId);
        if ($entryId === null) {
            return null;
        }

        $entry = QueueEntryModel::where('project_id', $projectId)
            ->whereKey($entryId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->first();

        if ($entry === null) {
            $this->sessionStore->forgetEntryId($projectId);
        }

        return $entry;
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function extractQueueIdentifierFromRaw(string|array|null $raw): array
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

    private function linkEntryToLineUserIfNeeded(User $user, QueueEntryModel $entry): void
    {
        if ($entry->user_id !== null) {
            return;
        }

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

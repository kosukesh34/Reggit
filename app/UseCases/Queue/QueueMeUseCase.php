<?php

namespace App\UseCases\Queue;

use App\Models\QueueEntry as QueueEntryModel;
use App\Services\UserService;
use App\UseCases\Queue\Data\QueueEntry;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueMeOutput;
use App\Util\AdminContext;
use App\Enums\QueueStatus;
use App\Util\StrHelper;
use Illuminate\Http\Request;

class QueueMeUseCase
{
    private const SESSION_QUEUE_ENTRY_KEY_PREFIX = 'mobile_order_queue_entry';

    public function __construct(
        private UserService $userService,
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory
    ) {
    }

    public function handle(Request $request): QueueMeOutput
    {
        $currentProjectId = AdminContext::getCurrentProjectId() ?? 1;
        $lineUserId = (string) $request->header('X-Line-User-Id', '');

        // URL の queue_token / queue_id がある場合は最優先で対象を特定する
        $entry = $this->findActiveEntryFromRequestToken($request, $currentProjectId);
        if ($entry === null) {
            $entry = $this->findActiveEntryFromSession($request, $currentProjectId);
        }
        if ($entry !== null && $this->isValidLineUserId($lineUserId)) {
            $this->linkEntryToLineUserIfNeeded($request, $entry, $lineUserId);
            $entry->refresh();
        }

        $user = $this->userService->getOrCreateUser($request);
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
            $this->rememberEntryInSession($request, $entry);
        } else {
            $this->forgetEntryInSession($request, $projectId);
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

    private function findActiveEntryFromRequestToken(Request $request, int $projectId): ?QueueEntryModel
    {
        $token = $this->normalizeString($request->query('queue_token'));
        if ($token !== '') {
            return QueueEntryModel::where('project_id', $projectId)
                ->where('qr_token', $token)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
        }

        $id = $this->normalizeString($request->query('queue_id'));
        if ($id !== '' && ctype_digit($id)) {
            return QueueEntryModel::where('project_id', $projectId)
                ->whereKey((int) $id)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
        }

        $rawParams = [
            $request->query('liff.state'),
            $request->query('state'),
            $request->query('liffRedirectUri'),
            $request->headers->get('referer'),
        ];

        foreach ($rawParams as $raw) {
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

    private function findActiveEntryFromSession(Request $request, int $projectId): ?QueueEntryModel
    {
        if (!$request->hasSession()) {
            return null;
        }

        $sessionValue = $request->session()->get($this->getSessionEntryKey($projectId));
        $entryId = (int) $sessionValue;
        if ($entryId <= 0) {
            return null;
        }

        $entry = QueueEntryModel::where('project_id', $projectId)
            ->whereKey($entryId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->first();

        if ($entry === null) {
            $this->forgetEntryInSession($request, $projectId);
        }

        return $entry;
    }

    private function rememberEntryInSession(Request $request, QueueEntryModel $entry): void
    {
        if (!$request->hasSession()) {
            return;
        }
        $request->session()->put($this->getSessionEntryKey((int) $entry->project_id), (int) $entry->id);
    }

    private function forgetEntryInSession(Request $request, int $projectId): void
    {
        if (!$request->hasSession()) {
            return;
        }
        $request->session()->forget($this->getSessionEntryKey($projectId));
    }

    private function getSessionEntryKey(int $projectId): string
    {
        return self::SESSION_QUEUE_ENTRY_KEY_PREFIX . '_' . $projectId;
    }

    private function normalizeString(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        return trim($value);
    }

    /**
     * @return array{0: string|null, 1: int|null}
     */
    private function extractQueueIdentifierFromRaw(mixed $raw): array
    {
        if (!is_string($raw)) {
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

        if (empty($params)) {
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

    private function linkEntryToLineUserIfNeeded(Request $request, QueueEntryModel $entry, string $lineUserId): void
    {
        if ($entry->user_id !== null) {
            return;
        }

        $user = $this->userService->getOrCreateUser($request);
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

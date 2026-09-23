<?php

namespace App\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\QueueEntry as QueueEntryModel;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueMeInput;
use App\UseCases\Queue\Data\QueueMeOutput;

class QueueMeUseCase
{
    public function __construct(
        private QueueStatsService $statsService,
        private QueueEntryFactory $entryFactory,
    ) {}

    public function handle(QueueMeInput $input): QueueMeOutput
    {
        // URLの連番IDだけでは受付を取得・紐付けしない。
        $entry = $this->findActiveEntry($input->projectId, $input->queueToken, null);
        if ($entry === null) {
            $entry = $this->findActiveEntry($input->projectId, null, $input->sessionEntryId);
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

        if ($entry !== null) {
            $this->linkEntryToUserIfNeeded($input, $entry);
            $entry->refresh();
            $entry->loadMissing(['user.devices', 'member.user.devices']);
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

        return new QueueMeOutput(
            $this->entryFactory->create($entry, $stats),
            $stats['waiting_count'],
            $stats['estimated_wait_minutes'],
            sessionEntryIdToRemember: (int) $entry->id,
            sessionProjectId: (int) $entry->project_id,
        );
    }

    private function getLatestEntry(int $userId, ?int $memberId, int $projectId): ?QueueEntryModel
    {
        return QueueEntryModel::query()
            ->where('project_id', $projectId)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->where(function ($query) use ($userId, $memberId) {
                $query->where('user_id', $userId);
                if ($memberId !== null) {
                    $query->orWhere('member_id', $memberId);
                }
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    private function findActiveEntry(int $projectId, ?string $token, ?int $id): ?QueueEntryModel
    {
        if ($token !== null) {
            $entry = QueueEntryModel::query()
                ->where('project_id', $projectId)
                ->where('qr_token', $token)
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
                ->first();
            if ($entry !== null) {
                return $entry;
            }
        }
        if ($id === null) {
            return null;
        }

        return QueueEntryModel::query()
            ->where('project_id', $projectId)
            ->whereKey($id)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->first();
    }

    /** LINE / ネイティブ問わず、未紐付けの券を現在ユーザーへ紐付ける */
    private function linkEntryToUserIfNeeded(QueueMeInput $input, QueueEntryModel $entry): void
    {
        $user = $input->user;
        $needsUser = $entry->user_id === null;
        $needsMember = $entry->member_id === null && $user->member !== null;
        if (! $needsUser && ! $needsMember) {
            return;
        }

        if ($needsUser) {
            $entry->user_id = $user->id;
        }
        if ($needsMember) {
            $entry->member_id = $user->member->id;
        }
        $entry->save();
    }
}

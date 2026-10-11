<?php

namespace App\Infrastructure\Persistence\Eloquent\Member;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Repositories\MemberRepository;
use App\Models\Member as MemberModel;
use App\Models\PointTransaction;
use App\Models\Scopes\ProjectScope;

class EloquentMemberRepository implements MemberRepository
{
    public function findById(int $memberId, int $projectId): ?MemberAggregate
    {
        $model = MemberModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('id', $memberId)
            ->where('project_id', $projectId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->loadAsDomain($model);
    }

    public function findByIdForUpdate(int $memberId, int $projectId): ?MemberAggregate
    {
        $model = MemberModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('id', $memberId)
            ->where('project_id', $projectId)
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        return MemberMapper::toDomain($model, true);
    }

    public function findByUserId(int $userId, int $projectId): ?MemberAggregate
    {
        $model = MemberModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->loadAsDomain($model);
    }

    public function findByUserIdForUpdate(int $userId, int $projectId): ?MemberAggregate
    {
        $model = MemberModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->lockForUpdate()
            ->first();

        return $model !== null ? MemberMapper::toDomain($model, true) : null;
    }

    public function save(MemberAggregate $member): void
    {
        $model = MemberModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey($member->id()->value())
            ->where('project_id', $member->projectId())
            ->first();
        if ($model === null) {
            throw new \RuntimeException('Member not found for save: '.$member->id()->value());
        }

        foreach ($member->pullPendingEntries() as $entry) {
            PointTransaction::create([
                'project_id' => $member->projectId(),
                'member_id' => $member->id()->value(),
                'type' => $entry->type,
                'points' => $entry->signedPoints,
                'description' => $entry->description,
                'order_id' => $entry->orderId,
                'expires_at' => $entry->expiresAt,
            ]);
        }

        $model->recalcPointsFromTransactions();
    }

    public function hasPointTransactionForOrder(int $orderId, string $type, ?string $description = null): bool
    {
        $query = PointTransaction::query()
            ->where('order_id', $orderId)
            ->where('type', $type);

        if ($description !== null) {
            $query->where('description', $description);
        }

        return $query->exists();
    }

    public function syncPointsLedger(int $memberId, int $projectId): void
    {
        $model = MemberModel::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->where('id', $memberId)
            ->where('project_id', $projectId)
            ->first();
        if ($model === null) {
            return;
        }

        $model->syncPointsLedger();
    }

    private function loadAsDomain(MemberModel $model): MemberAggregate
    {
        return MemberMapper::toDomain($model);
    }
}

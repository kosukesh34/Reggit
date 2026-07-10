<?php

namespace App\Infrastructure\Persistence\Eloquent\Member;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\Member as MemberModel;
use App\Models\PointTransaction;
use App\Models\Scopes\ProjectScope;

class EloquentMemberRepository implements MemberRepository
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function findById(int $memberId, int $projectId): ?MemberAggregate
    {
        $model = MemberModel::query()
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
            ->where('id', $memberId)
            ->where('project_id', $projectId)
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->loadAsDomain($model);
    }

    public function findByUserId(int $userId, int $projectId): ?MemberAggregate
    {
        $model = MemberModel::query()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->loadAsDomain($model);
    }

    public function save(MemberAggregate $member): void
    {
        $model = MemberModel::query()->find($member->id()->value());
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
            ->where('id', $memberId)
            ->where('project_id', $projectId)
            ->first();
        if ($model === null) {
            return;
        }

        $columnPoints = max(0, (int) $model->points);
        $ledgerPoints = (int) $model->pointTransactions()
            ->withoutGlobalScope(ProjectScope::class)
            ->sum('points');

        if ($ledgerPoints === $columnPoints) {
            return;
        }

        if ($ledgerPoints === 0 && $columnPoints > 0) {
            $this->backfillEarnedPoints($model, $columnPoints, MemberModel::POINTS_LEDGER_BACKFILL_DESCRIPTION);

            return;
        }

        if ($columnPoints > $ledgerPoints) {
            if ($ledgerPoints > 0) {
                $model->recalcPointsFromTransactions();

                return;
            }

            $this->backfillEarnedPoints(
                $model,
                $columnPoints - $ledgerPoints,
                MemberModel::POINTS_LEDGER_BACKFILL_DESCRIPTION,
            );

            return;
        }

        $model->recalcPointsFromTransactions();
    }

    private function backfillEarnedPoints(MemberModel $model, int $points, string $description): void
    {
        if ($points <= 0) {
            return;
        }

        $validDays = $this->shopSettingRepository->getPointsValidDays((int) $model->project_id);
        PointTransaction::create([
            'project_id' => $model->project_id,
            'member_id' => $model->id,
            'type' => 'earned',
            'points' => $points,
            'description' => $description,
            'order_id' => null,
            'expires_at' => now()->addDays($validDays),
        ]);
        $model->recalcPointsFromTransactions();
    }

    private function loadAsDomain(MemberModel $model): MemberAggregate
    {
        return MemberMapper::toDomain($model);
    }
}

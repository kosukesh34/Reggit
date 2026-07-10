<?php

namespace App\Domain\Member\Repositories;

use App\Domain\Member\Aggregates\Member;

interface MemberRepository
{
    public function findById(int $memberId, int $projectId): ?Member;

    public function findByIdForUpdate(int $memberId, int $projectId): ?Member;

    public function findByUserId(int $userId, int $projectId): ?Member;

    public function save(Member $member): void;

    public function hasPointTransactionForOrder(int $orderId, string $type, ?string $description = null): bool;

    public function syncPointsLedger(int $memberId, int $projectId): void;
}

<?php

namespace App\Infrastructure\Persistence\Eloquent\Member;

use App\Domain\Member\Aggregates\Member as MemberAggregate;
use App\Domain\Member\ValueObjects\MemberId;
use App\Models\Member as MemberModel;

final class MemberMapper
{
    public static function toDomain(MemberModel $model): MemberAggregate
    {
        $smaregiCustomerId = $model->smaregi_customer_id ?? '';
        $smaregiCustomerId = is_string($smaregiCustomerId) ? trim($smaregiCustomerId) : '';

        return new MemberAggregate(
            new MemberId((int) $model->id),
            (int) $model->project_id,
            $model->getTotalPointsFromTransactions(),
            $smaregiCustomerId,
        );
    }
}

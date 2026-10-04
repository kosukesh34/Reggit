<?php

namespace App\UseCases\Member;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Models\User;
use App\Services\Member\PointQrRedeemer;

class RedeemPointQrUseCase
{
    public function __construct(private readonly PointQrRedeemer $redeemer, private readonly DomainEventDispatcher $events) {}

    /** @return array{member_id: int, granted_points: int, points: int} */
    public function handle(User $user, string $token): array
    {
        $result = $this->redeemer->redeem($user, $token);
        $this->events->dispatch(new PointsEarned($result['member_id'], (int) $user->project_id, $result['granted_points'], null, isBestEffortSync: true));

        return $result;
    }
}

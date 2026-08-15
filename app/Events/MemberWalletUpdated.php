<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MemberWalletUpdated implements ShouldBroadcastNow
{
    public function __construct(
        public int $projectId,
        public int $memberId,
        public int $points,
        public int $stampsTotal,
        public int $pendingLotteryCount,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('liff.member.'.$this->memberId)];
    }

    public function broadcastAs(): string
    {
        return 'wallet.updated';
    }

    /**
     * @return array{points: int, stamps_total: int, pending_lottery_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'points' => $this->points,
            'stamps_total' => $this->stampsTotal,
            'pending_lottery_count' => $this->pendingLotteryCount,
        ];
    }
}

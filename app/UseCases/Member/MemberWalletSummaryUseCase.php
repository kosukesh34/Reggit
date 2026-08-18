<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\MemberWalletSummaryService;

class MemberWalletSummaryUseCase
{
    public function __construct(
        private MemberWalletSummaryService $memberWalletSummaryService,
    ) {}

    /**
     * @return array{
     *     points: int,
     *     stamps_total: int,
     *     pending_lottery_count: int,
     *     updated_at: string
     * }
     */
    public function handle(User $user): array
    {
        return $this->memberWalletSummaryService->summarize($user);
    }
}

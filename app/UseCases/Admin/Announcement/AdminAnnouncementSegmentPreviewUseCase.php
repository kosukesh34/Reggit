<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\SalesSegmentService;

class AdminAnnouncementSegmentPreviewUseCase
{
    public function __construct(
        private SalesSegmentService $salesSegmentService,
    ) {}

    public function handle(int $projectId, int $periodDays, string $condition, ?float $minAmount): int
    {
        $periodDays = max(1, min(365, $periodDays));
        $noOrdersOnly = $condition === 'no_orders';

        return $this->salesSegmentService->getMemberIdsBySalesCriteria(
            $projectId,
            $periodDays,
            $noOrdersOnly ? null : ($minAmount ?? 0.0),
            $noOrdersOnly
        )->count();
    }
}

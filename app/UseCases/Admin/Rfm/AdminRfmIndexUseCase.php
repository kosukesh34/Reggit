<?php

namespace App\UseCases\Admin\Rfm;

use App\Services\Admin\RfmAnalysisService;
use App\UseCases\Admin\Rfm\Data\AdminRfmIndexView;

class AdminRfmIndexUseCase
{
    private RfmAnalysisService $rfmService;

    public function __construct(RfmAnalysisService $rfmService)
    {
        $this->rfmService = $rfmService;
    }

    public function handle(int $projectId, string $filterSegment): AdminRfmIndexView
    {
        $all = $this->rfmService->analyzeByProject($projectId);
        $summary = $this->rfmService->buildSegmentSummary($all);

        $rows = $filterSegment !== ''
            ? $all->filter(fn (array $row): bool => $row['segment'] === $filterSegment)->values()
            : $all;

        return new AdminRfmIndexView($rows, $summary, $filterSegment);
    }
}

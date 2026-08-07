<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\MemberSegmentService;

class AdminAnnouncementSegmentPreviewUseCase
{
    public function __construct(
        private MemberSegmentService $memberSegmentService,
    ) {}

    /**
     * @param  array<string, string|string[]>  $query
     */
    public function handle(int $projectId, array $query): int
    {
        $criteria = $this->memberSegmentService->buildCriteriaFromInput($query);
        if ($criteria === null) {
            return 0;
        }

        return $this->memberSegmentService->countMembers($projectId, $criteria);
    }
}

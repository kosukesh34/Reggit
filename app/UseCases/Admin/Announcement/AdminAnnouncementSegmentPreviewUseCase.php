<?php

namespace App\UseCases\Admin\Announcement;

use App\Services\Admin\MemberSegmentService;
use Illuminate\Http\Request;

class AdminAnnouncementSegmentPreviewUseCase
{
    public function __construct(
        private MemberSegmentService $memberSegmentService,
    ) {}

    public function handle(int $projectId, Request $request): int
    {
        $criteria = $this->memberSegmentService->buildCriteriaFromInput($request->query());
        if ($criteria === null) {
            return 0;
        }

        return $this->memberSegmentService->countMembers($projectId, $criteria);
    }
}

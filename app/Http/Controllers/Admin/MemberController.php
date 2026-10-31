<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberDetailUseCase;
use App\UseCases\Admin\Member\AdminMemberEditUseCase;
use App\UseCases\Admin\Member\AdminMemberTicketUseCase;
use App\UseCases\Admin\MemberTag\AdminMemberTagIndexUseCase;
use Illuminate\Http\Request;

class MemberController extends AdminBaseController
{
    public function __construct(
        private AdminMemberDetailUseCase $detailUseCase,
        private AdminMemberEditUseCase $editUseCase,
        private AdminMemberTagIndexUseCase $tagIndexUseCase,
        private AdminMemberTicketUseCase $ticketUseCase,
    ) {}

    public function memberDetail(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only([
            'visits_q', 'visits_from', 'visits_to',
            'points_q', 'points_from', 'points_to',
            'coupons_q', 'coupons_from', 'coupons_to',
            'stamps_q', 'stamps_from', 'stamps_to',
            'rank_q', 'rank_from', 'rank_to',
            'redemptions_q', 'redemptions_from', 'redemptions_to',
        ]);
        $output = $this->detailUseCase->handle($projectId, $id, $filters);
        $tags = $this->tagIndexUseCase->handle($projectId);

        return view('admin.member-detail', [
            'view' => $output,
            'allTags' => $tags,
            ...$this->ticketUseCase->forMember((int) $output->member->id),
        ]);
    }

    public function editMember(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editUseCase->handle($projectId, (int) $id);

        return view('admin.member-edit', ['view' => $output]);
    }
}

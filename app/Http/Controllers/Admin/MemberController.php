<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MemberUpdateRequest;
use App\UseCases\Admin\Member\AdminMemberDeleteUseCase;
use App\UseCases\Admin\Member\AdminMemberDetailUseCase;
use App\UseCases\Admin\Member\AdminMemberEditUseCase;
use App\UseCases\Admin\Member\AdminMemberExportUseCase;
use App\UseCases\Admin\Member\AdminMemberListUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberListView;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use App\UseCases\Admin\Member\GetAdminMemberListExtrasUseCase;
use App\UseCases\Admin\MemberTag\AdminMemberTagIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MemberController extends AdminBaseController
{
    public function __construct(
        private AdminMemberListUseCase $listUseCase,
        private AdminMemberDetailUseCase $detailUseCase,
        private AdminMemberEditUseCase $editUseCase,
        private AdminMemberUpdateUseCase $updateUseCase,
        private AdminMemberDeleteUseCase $deleteUseCase,
        private AdminMemberExportUseCase $exportUseCase,
        private AdminMemberTagIndexUseCase $tagIndexUseCase,
        private GetAdminMemberListExtrasUseCase $memberListExtrasUseCase,
    ) {}

    public function members(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only(['keyword', 'rank', 'channel', 'tag_id', 'visited_from', 'visited_to', 'sort', 'dir']);
        $output = $this->listUseCase->handle($projectId, $filters);
        $extras = $this->memberListExtrasUseCase->handle($projectId);

        return view('admin.members', ['view' => new AdminMemberListView(
            $output->members,
            $filters,
            $extras->lineInviteUrl,
            $extras->lineInviteLabel,
            $output->rankOptions,
            $this->tagIndexUseCase->handle($projectId),
        )]);
    }

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
        ]);
    }

    public function editMember(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editUseCase->handle($projectId, (int) $id);

        return view('admin.member-edit', ['view' => $output]);
    }

    public function updateMember(MemberUpdateRequest $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();

        $userData = [
            'name' => $request->input('name'),
            'kana' => $request->input('kana'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ];
        $memberData = [
            'member_number' => $request->input('member_number'),
            'status' => $request->input('status'),
            'rank' => $request->input('rank') ?: null,
            'birthday' => $request->input('birthday') ?: null,
            'address' => $request->input('address'),
            'postal_code' => $request->input('postal_code'),
            'sex' => $request->input('sex') ?: null,
            'mail_receive_flag' => $request->input('mail_receive_flag') !== null && $request->input('mail_receive_flag') !== ''
                ? (string) $request->input('mail_receive_flag')
                : null,
            'notification_preference' => (string) $request->input('notification_preference'),
        ];
        $profileExtra = $request->getProfileExtraArray();
        if ($profileExtra !== null) {
            $memberData['profile_extra'] = $profileExtra;
        }

        $input = new AdminMemberUpdateInput(
            $projectId,
            $id,
            $userData,
            $memberData,
            $request->getPointAdjustment()
        );
        $this->updateUseCase->handle($input);

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', '会員情報を更新しました');
    }

    public function deleteMember(string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $this->deleteUseCase->handle($projectId, $id);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.members', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.members', $this->buildAdminRouteParams())
            ->with('success', '会員を削除しました');
    }

    /** 会員一覧CSVエクスポート */
    public function export(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only(['keyword', 'rank', 'channel', 'tag_id', 'visited_from', 'visited_to', 'sort', 'dir']);
        $rows = $this->exportUseCase->handle($projectId, $filters);

        $csvHeader = array_keys($rows->first() ?? []);
        $callback = function () use ($rows, $csvHeader): void {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $csvHeader);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="members_'.now()->format('Ymd_His').'.csv"',
        ]);
    }
}

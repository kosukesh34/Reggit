<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MemberUpdateRequest;
use App\UseCases\Admin\Member\AdminMemberAddStampsUseCase;
use App\UseCases\Admin\Member\AdminMemberAttachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberDetachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberDetailUseCase;
use App\UseCases\Admin\Member\AdminMemberEditUseCase;
use App\UseCases\Admin\Member\AdminMemberExportUseCase;
use App\UseCases\Admin\Member\AdminMemberIssueCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberListUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateMemoUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use App\UseCases\Admin\MemberTag\AdminMemberTagIndexUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class MemberController extends AdminBaseController
{
    public function __construct(
        private AdminMemberListUseCase $listUseCase,
        private AdminMemberDetailUseCase $detailUseCase,
        private AdminMemberEditUseCase $editUseCase,
        private AdminMemberUpdateUseCase $updateUseCase,
        private AdminMemberIssueCouponUseCase $issueCouponUseCase,
        private AdminMemberAddStampsUseCase $addStampsUseCase,
        private AdminMemberAttachTagUseCase $attachTagUseCase,
        private AdminMemberDetachTagUseCase $detachTagUseCase,
        private AdminMemberUpdateMemoUseCase $updateMemoUseCase,
        private AdminMemberExportUseCase $exportUseCase,
        private AdminMemberTagIndexUseCase $tagIndexUseCase
    ) {}

    public function members(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only(['keyword', 'status', 'rank']);
        $output = $this->listUseCase->handle($projectId, $filters);

        return view('admin.members', [
            'members' => $output->members,
            'filters' => $filters,
        ]);
    }

    public function memberDetail(string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->detailUseCase->handle($projectId, $id);
        $tags = $this->tagIndexUseCase->handle($projectId);

        return view('admin.member-detail', [
            'member' => $output->member,
            'activeCoupons' => $output->activeCoupons,
            'activeStampCards' => $output->activeStampCards,
            'purchaseSummary' => $output->purchaseSummary,
            'allTags' => $tags,
        ]);
    }

    public function editMember(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editUseCase->handle($projectId, (int) $id);

        return view('admin.member-edit', [
            'member' => $output->member,
            'rankOptions' => $output->rankOptions,
        ]);
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
            'prefecture' => $request->input('prefecture'),
            'city' => $request->input('city'),
            'street' => $request->input('street'),
            'sex' => $request->input('sex') ?: null,
            'profile_extra' => $request->getProfileExtraArray(),
        ];

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

    /** 会員にクーポンを配布 */
    public function issueCoupon(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->where('project_id', $projectId)],
        ]);
        $this->issueCouponUseCase->handle($projectId, $id, (int) $validated['coupon_id']);

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'クーポンを配布しました');
    }

    /** 会員にスタンプを付与 */
    public function addStamps(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'stamp_card_id' => ['required', 'integer', Rule::exists('stamp_cards', 'id')->where('project_id', $projectId)],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $this->addStampsUseCase->handle(
            $projectId,
            $id,
            (int) $validated['stamp_card_id'],
            (int) $validated['count']
        );

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'スタンプを付与しました');
    }

    /** 会員にタグを付与 */
    public function attachTag(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'tag_id' => ['required', 'integer', Rule::exists('member_tags', 'id')->where('project_id', $projectId)],
        ]);
        $this->attachTagUseCase->handle($projectId, $id, (int) $validated['tag_id']);

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'タグを付与しました');
    }

    /** 会員のタグを解除 */
    public function detachTag(string $project_key, int $id, int $tagId)
    {
        $projectId = $this->resolveAdminProjectId();
        $this->detachTagUseCase->handle($projectId, $id, $tagId);

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'タグを解除しました');
    }

    /** 会員メモ更新 */
    public function updateMemo(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate(['memo' => ['nullable', 'string', 'max:2000']]);
        $this->updateMemoUseCase->handle($projectId, $id, $validated['memo'] ?? null);

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'メモを更新しました');
    }

    /** 会員一覧CSVエクスポート */
    public function export(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only(['keyword', 'status']);
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

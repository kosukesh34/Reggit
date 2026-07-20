<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MemberUpdateRequest;
use App\UseCases\Admin\Member\AdminMemberAdjustStampsUseCase;
use App\UseCases\Admin\Member\AdminMemberAttachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberDeleteCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberDeleteUseCase;
use App\UseCases\Admin\Member\AdminMemberDetachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberDetailUseCase;
use App\UseCases\Admin\Member\AdminMemberEditUseCase;
use App\UseCases\Admin\Member\AdminMemberExportUseCase;
use App\UseCases\Admin\Member\AdminMemberHistorySectionUseCase;
use App\UseCases\Admin\Member\AdminMemberIssueCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberListUseCase;
use App\UseCases\Admin\Member\AdminMemberRecordVisitUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateMemoUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateUseCase;
use App\UseCases\Admin\Member\AdminMemberVisitDetailUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use App\UseCases\Admin\Member\GetAdminMemberListExtrasUseCase;
use App\UseCases\Admin\MemberTag\AdminMemberTagIndexUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class MemberController extends AdminBaseController
{
    public function __construct(
        private AdminMemberListUseCase $listUseCase,
        private AdminMemberDetailUseCase $detailUseCase,
        private AdminMemberHistorySectionUseCase $historySectionUseCase,
        private AdminMemberEditUseCase $editUseCase,
        private AdminMemberUpdateUseCase $updateUseCase,
        private AdminMemberDeleteUseCase $deleteUseCase,
        private AdminMemberIssueCouponUseCase $issueCouponUseCase,
        private AdminMemberAdjustStampsUseCase $adjustStampsUseCase,
        private AdminMemberDeleteCouponUseCase $deleteCouponUseCase,
        private AdminMemberRecordVisitUseCase $recordVisitUseCase,
        private AdminMemberVisitDetailUseCase $visitDetailUseCase,
        private AdminMemberAttachTagUseCase $attachTagUseCase,
        private AdminMemberDetachTagUseCase $detachTagUseCase,
        private AdminMemberUpdateMemoUseCase $updateMemoUseCase,
        private AdminMemberExportUseCase $exportUseCase,
        private AdminMemberTagIndexUseCase $tagIndexUseCase,
        private GetAdminMemberListExtrasUseCase $memberListExtrasUseCase,
    ) {}

    public function members(Request $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = $request->only(['keyword', 'status', 'rank', 'channel']);
        $output = $this->listUseCase->handle($projectId, $filters);
        $extras = $this->memberListExtrasUseCase->handle($projectId);

        return view('admin.members', [
            'members' => $output->members,
            'filters' => $filters,
            'lineInviteUrl' => $extras->lineInviteUrl,
            'lineInviteLabel' => $extras->lineInviteLabel,
        ]);
    }

    public function memberDetail(string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $filters = request()->only([
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
            'member' => $output->member,
            'purchaseSummary' => $output->purchaseSummary,
            'allTags' => $tags,
            'visits' => $output->visits,
            'pointTransactions' => $output->pointTransactions,
            'memberCoupons' => $output->memberCoupons,
            'stampTransactions' => $output->stampTransactions,
            'rankHistories' => $output->rankHistories,
            'couponRedemptions' => $output->couponRedemptions,
            'historyFilters' => $output->historyFilters,
        ]);
    }

    public function memberHistory(string $project_key, int $id, string $section): JsonResponse
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $output = $this->historySectionUseCase->handle(
                $projectId,
                $id,
                $section,
                (string) request()->query('q', ''),
                (string) request()->query('from', ''),
                (string) request()->query('to', ''),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => '不正なセクションです'], 422);
        }

        return response()->json([
            'html' => $output->html,
            'total' => $output->total,
            'section' => $output->section,
            'keyword' => $output->keyword,
            'from' => $output->from,
            'to' => $output->to,
        ]);
    }

    public function visitDetail(string $project_key, int $id, int $visitId)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->visitDetailUseCase->handle($projectId, $id, $visitId);

        return view('admin.member-visit-detail', [
            'member' => $output->member,
            'visit' => $output->visit,
        ]);
    }

    public function editMember(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editUseCase->handle($projectId, (int) $id);

        return view('admin.member-edit', [
            'member' => $output->member,
            'rankOptions' => $output->rankOptions,
            'activeCoupons' => $output->activeCoupons,
            'activeStampCards' => $output->activeStampCards,
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

    /** 会員にクーポンを配布 */
    public function issueCoupon(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->where('project_id', $projectId)],
        ]);
        $this->issueCouponUseCase->handle($projectId, $id, (int) $validated['coupon_id']);

        return redirect()->route('admin.members.edit', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'クーポンを配布しました');
    }

    /** 会員の発行済みクーポンを削除 */
    public function deleteCoupon(string $project_key, int $id, int $memberCouponId)
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $this->deleteCouponUseCase->handle($projectId, $id, $memberCouponId);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.members.edit', array_merge(['id' => $id], $this->buildAdminRouteParams()))
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.members.edit', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'クーポンを削除しました');
    }

    /** 会員のスタンプを加減算 */
    public function adjustStamps(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'stamp_card_id' => ['required', 'integer', Rule::exists('stamp_cards', 'id')->where('project_id', $projectId)],
            'stamp_add' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stamp_subtract' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stamp_description' => ['nullable', 'string', 'max:255'],
        ]);

        $add = (int) ($validated['stamp_add'] ?? 0);
        $subtract = (int) ($validated['stamp_subtract'] ?? 0);
        $delta = $add - $subtract;
        if ($delta === 0) {
            return redirect()->route('admin.members.edit', array_merge(['id' => $id], $this->buildAdminRouteParams()))
                ->with('error', 'スタンプの加算または減算を指定してください');
        }

        $this->adjustStampsUseCase->handle(
            $projectId,
            $id,
            (int) $validated['stamp_card_id'],
            $delta,
            $validated['stamp_description'] ?? null
        );

        return redirect()->route('admin.members.edit', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'スタンプを更新しました');
    }

    /** 来店を手動登録 */
    public function recordVisit(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'visited_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $this->recordVisitUseCase->handle(
            $projectId,
            $id,
            $validated['visited_at'],
            $validated['note'] ?? null
        );

        return redirect()->route('admin.members.edit', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', '来店を登録しました');
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
        $filters = $request->only(['keyword', 'status', 'channel']);
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

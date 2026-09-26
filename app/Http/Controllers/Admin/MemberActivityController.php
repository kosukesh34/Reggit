<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberAdjustStampsUseCase;
use App\UseCases\Admin\Member\AdminMemberAttachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberDeleteCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberDetachTagUseCase;
use App\UseCases\Admin\Member\AdminMemberHistorySectionUseCase;
use App\UseCases\Admin\Member\AdminMemberIssueCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberRecordVisitUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateMemoUseCase;
use App\UseCases\Admin\Member\AdminMemberVisitDetailUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class MemberActivityController extends AdminBaseController
{
    public function __construct(
        private AdminMemberHistorySectionUseCase $historySectionUseCase,
        private AdminMemberVisitDetailUseCase $visitDetailUseCase,
        private AdminMemberIssueCouponUseCase $issueCouponUseCase,
        private AdminMemberDeleteCouponUseCase $deleteCouponUseCase,
        private AdminMemberAdjustStampsUseCase $adjustStampsUseCase,
        private AdminMemberRecordVisitUseCase $recordVisitUseCase,
        private AdminMemberAttachTagUseCase $attachTagUseCase,
        private AdminMemberDetachTagUseCase $detachTagUseCase,
        private AdminMemberUpdateMemoUseCase $updateMemoUseCase,
    ) {}

    public function history(Request $request, string $project_key, int $id, string $section): JsonResponse
    {
        try {
            $output = $this->historySectionUseCase->handle(
                $this->resolveAdminProjectId(),
                $id,
                $section,
                (string) $request->query('q', ''),
                (string) $request->query('from', ''),
                (string) $request->query('to', ''),
            );
        } catch (InvalidArgumentException) {
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

    public function visitDetail(string $project_key, int $id, int $visitId): View
    {
        $output = $this->visitDetailUseCase->handle($this->resolveAdminProjectId(), $id, $visitId);

        return view('admin.member-visit-detail', [
            'member' => $output->member,
            'visit' => $output->visit,
        ]);
    }

    public function issueCoupon(Request $request, string $project_key, int $id): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->where('project_id', $projectId)],
        ]);

        try {
            $this->issueCouponUseCase->handle($projectId, $id, (int) $validated['coupon_id']);
        } catch (InvalidArgumentException $exception) {
            return $this->redirectToEdit($id)->with('error', $exception->getMessage());
        }

        return $this->redirectToEdit($id)->with('success', 'クーポンを配布しました');
    }

    public function deleteCoupon(string $project_key, int $id, int $memberCouponId): RedirectResponse
    {
        try {
            $this->deleteCouponUseCase->handle($this->resolveAdminProjectId(), $id, $memberCouponId);
        } catch (RuntimeException $exception) {
            return $this->redirectToEdit($id)->with('error', $exception->getMessage());
        }

        return $this->redirectToEdit($id)->with('success', 'クーポンを削除しました');
    }

    public function adjustStamps(Request $request, string $project_key, int $id): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'stamp_card_id' => ['required', 'integer', Rule::exists('stamp_cards', 'id')->where('project_id', $projectId)],
            'stamp_add' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stamp_subtract' => ['nullable', 'integer', 'min:0', 'max:100'],
            'stamp_description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->adjustStampsUseCase->handle(
                $projectId,
                $id,
                (int) $validated['stamp_card_id'],
                (int) ($validated['stamp_add'] ?? 0),
                (int) ($validated['stamp_subtract'] ?? 0),
                $validated['stamp_description'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->redirectToEdit($id)->with('error', $exception->getMessage());
        }

        return $this->redirectToEdit($id)->with('success', 'スタンプを更新しました');
    }

    public function recordVisit(Request $request, string $project_key, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'visited_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $this->recordVisitUseCase->handle(
            $this->resolveAdminProjectId(),
            $id,
            $validated['visited_at'],
            $validated['note'] ?? null,
        );

        return $this->redirectToEdit($id)->with('success', '来店を登録しました');
    }

    public function attachTag(Request $request, string $project_key, int $id): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validate([
            'tag_id' => ['required', 'integer', Rule::exists('member_tags', 'id')->where('project_id', $projectId)],
        ]);
        $this->attachTagUseCase->handle($projectId, $id, (int) $validated['tag_id']);

        return $this->redirectToDetail($id)->with('success', 'タグを付与しました');
    }

    public function detachTag(string $project_key, int $id, int $tagId): RedirectResponse
    {
        $this->detachTagUseCase->handle($this->resolveAdminProjectId(), $id, $tagId);

        return $this->redirectToDetail($id)->with('success', 'タグを解除しました');
    }

    public function updateMemo(Request $request, string $project_key, int $id): RedirectResponse
    {
        $validated = $request->validate(['memo' => ['nullable', 'string', 'max:2000']]);
        $this->updateMemoUseCase->handle($this->resolveAdminProjectId(), $id, $validated['memo'] ?? null);

        return $this->redirectToDetail($id)->with('success', 'メモを更新しました');
    }

    private function redirectToEdit(int $memberId): RedirectResponse
    {
        return redirect()->route('admin.members.edit', array_merge(
            ['id' => $memberId],
            $this->buildAdminRouteParams(),
        ));
    }

    private function redirectToDetail(int $memberId): RedirectResponse
    {
        return redirect()->route('admin.members.detail', array_merge(
            ['id' => $memberId],
            $this->buildAdminRouteParams(),
        ));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\AdminMemberAdjustStampsUseCase;
use App\UseCases\Admin\Member\AdminMemberDeleteCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberIssueCouponUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class MemberBenefitController extends AdminBaseController
{
    public function __construct(
        private AdminMemberIssueCouponUseCase $issueCouponUseCase,
        private AdminMemberDeleteCouponUseCase $deleteCouponUseCase,
        private AdminMemberAdjustStampsUseCase $adjustStampsUseCase,
    ) {}

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

    private function redirectToEdit(int $memberId): RedirectResponse
    {
        return redirect()->route('admin.members.edit', array_merge(
            ['id' => $memberId],
            $this->buildAdminRouteParams(),
        ));
    }
}

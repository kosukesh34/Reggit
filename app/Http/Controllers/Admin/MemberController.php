<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\MemberAdminService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends AdminBaseController
{
    public function __construct(
        private MemberAdminService $memberAdminService
    ) {
    }

    public function members()
    {
        $projectId = $this->resolveAdminProjectId();
        $members = $this->memberAdminService->getPaginatedList($projectId, 20);
        return view('admin.members', compact('members'));
    }

    public function memberDetail(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $member = $this->memberAdminService->findOrFail((int) $id, $projectId);
        $activeCoupons = $this->memberAdminService->getActiveCouponsForSelect($projectId);
        $activeStampCards = $this->memberAdminService->getActiveStampCardsForSelect($projectId);
        return view('admin.member-detail', compact('member', 'activeCoupons', 'activeStampCards'));
    }

    /** 会員にクーポンを配布 */
    public function issueCoupon(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $member = $this->memberAdminService->findOrFail($id, $projectId);
        $validated = $request->validate([
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')->where('project_id', $projectId)],
        ]);
        $this->memberAdminService->issueCouponToMember($member, (int) $validated['coupon_id']);
        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'クーポンを配布しました');
    }

    /** 会員にスタンプを付与 */
    public function addStamps(Request $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $member = $this->memberAdminService->findOrFail($id, $projectId);
        $validated = $request->validate([
            'stamp_card_id' => ['required', 'integer', Rule::exists('stamp_cards', 'id')->where('project_id', $projectId)],
            'count' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $this->memberAdminService->addStampsToMember($member, (int) $validated['stamp_card_id'], (int) $validated['count']);
        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', 'スタンプを付与しました');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MemberUpdateRequest;
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

    public function editMember(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $member = $this->memberAdminService->findOrFail((int) $id, $projectId);
        $rankOptions = $this->memberAdminService->getRankOptionsForSelect($projectId);
        return view('admin.member-edit', compact('member', 'rankOptions'));
    }

    public function updateMember(MemberUpdateRequest $request, string $project_key, int $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $member = $this->memberAdminService->findOrFail($id, $projectId);

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
        $this->memberAdminService->updateMemberProfile($member, $userData, $memberData);

        $adjustment = $request->getPointAdjustment();
        if ($adjustment !== null) {
            $desc = $adjustment['description'] ?: '管理画面での調整';
            if ($adjustment['type'] === 'add') {
                $member->addPoints((int) $adjustment['points'], $desc, null);
            } else {
                $member->usePoints((int) $adjustment['points'], $desc, null);
            }
        }

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', '会員情報を更新しました');
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

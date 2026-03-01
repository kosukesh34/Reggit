<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MemberUpdateRequest;
use App\UseCases\Admin\Member\AdminMemberAddStampsUseCase;
use App\UseCases\Admin\Member\AdminMemberDetailUseCase;
use App\UseCases\Admin\Member\AdminMemberEditUseCase;
use App\UseCases\Admin\Member\AdminMemberIssueCouponUseCase;
use App\UseCases\Admin\Member\AdminMemberListUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends AdminBaseController
{
    private AdminMemberListUseCase $listUseCase;

    private AdminMemberDetailUseCase $detailUseCase;

    private AdminMemberEditUseCase $editUseCase;

    private AdminMemberUpdateUseCase $updateUseCase;

    private AdminMemberIssueCouponUseCase $issueCouponUseCase;

    private AdminMemberAddStampsUseCase $addStampsUseCase;

    public function __construct(
        AdminMemberListUseCase $listUseCase,
        AdminMemberDetailUseCase $detailUseCase,
        AdminMemberEditUseCase $editUseCase,
        AdminMemberUpdateUseCase $updateUseCase,
        AdminMemberIssueCouponUseCase $issueCouponUseCase,
        AdminMemberAddStampsUseCase $addStampsUseCase
    ) {
        $this->listUseCase = $listUseCase;
        $this->detailUseCase = $detailUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->issueCouponUseCase = $issueCouponUseCase;
        $this->addStampsUseCase = $addStampsUseCase;
    }

    public function members()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->listUseCase->handle($projectId);

        return view('admin.members', ['members' => $output->members]);
    }

    public function memberDetail(string $project_key, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->detailUseCase->handle($projectId, (int) $id);

        return view('admin.member-detail', [
            'member' => $output->member,
            'activeCoupons' => $output->activeCoupons,
            'activeStampCards' => $output->activeStampCards,
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
}

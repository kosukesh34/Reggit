<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\MemberUpdateRequest;
use App\UseCases\Admin\Member\AdminMemberDeleteUseCase;
use App\UseCases\Admin\Member\AdminMemberUpdateUseCase;
use App\UseCases\Admin\Member\Data\AdminMemberUpdateInput;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class MemberMutationController extends AdminBaseController
{
    public function __construct(
        private AdminMemberUpdateUseCase $updateUseCase,
        private AdminMemberDeleteUseCase $deleteUseCase,
    ) {}

    public function update(MemberUpdateRequest $request, string $projectKey, int $id): RedirectResponse
    {
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

        $this->updateUseCase->handle(new AdminMemberUpdateInput(
            $this->resolveAdminProjectId(),
            $id,
            [
                'name' => $request->input('name'),
                'kana' => $request->input('kana'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ],
            $memberData,
            $request->getPointAdjustment(),
        ));

        return redirect()->route('admin.members.detail', array_merge(['id' => $id], $this->buildAdminRouteParams()))
            ->with('success', '会員情報を更新しました');
    }

    public function destroy(string $projectKey, int $id): RedirectResponse
    {
        try {
            $this->deleteUseCase->handle($this->resolveAdminProjectId(), $id);
        } catch (RuntimeException $exception) {
            return $this->redirectToIndex()->with('error', $exception->getMessage());
        }

        return $this->redirectToIndex()->with('success', '会員を削除しました');
    }

    private function redirectToIndex(): RedirectResponse
    {
        return redirect()->route('admin.members', $this->buildAdminRouteParams());
    }
}

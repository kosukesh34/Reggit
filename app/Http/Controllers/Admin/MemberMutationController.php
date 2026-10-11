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
        $memberData = $request->only([
            'member_number', 'status', 'rank', 'birthday', 'address', 'postal_code',
            'sex', 'mail_receive_flag', 'notification_preference', 'customer_store_id',
        ]);
        foreach (['rank', 'birthday', 'sex', 'mail_receive_flag', 'customer_store_id'] as $key) {
            if (array_key_exists($key, $memberData) && $memberData[$key] === '') {
                $memberData[$key] = null;
            }
        }
        $profileExtra = $request->getProfileExtraArray();
        if ($profileExtra !== null) {
            $memberData['profile_extra'] = $profileExtra;
        }

        $this->updateUseCase->handle(new AdminMemberUpdateInput(
            $this->resolveAdminProjectId(),
            $id,
            $request->only(['name', 'kana', 'email', 'phone']),
            $memberData,
            $request->getPointAdjustment(),
            $request->has('custom_profile') || $request->boolean('custom_profile_form'),
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

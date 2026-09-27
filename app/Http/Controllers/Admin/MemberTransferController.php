<?php

namespace App\Http\Controllers\Admin;

use App\UseCases\Admin\Member\IssueAdminMemberTransferCodeUseCase;
use App\Util\LogHelper;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class MemberTransferController extends AdminBaseController
{
    public function __construct(
        private IssueAdminMemberTransferCodeUseCase $issueAdminMemberTransferCodeUseCase,
    ) {}

    public function store(string $project_key, string $id): RedirectResponse
    {
        $memberId = (int) $id;
        $projectId = $this->resolveAdminProjectId();
        $back = redirect()->route('admin.members.detail', [
            'project_key' => $project_key,
            'id' => $memberId,
        ]);
        try {
            $adminId = auth('admin')->id();
            $issued = $this->issueAdminMemberTransferCodeUseCase->handle(
                $projectId,
                $memberId,
                $adminId !== null ? (int) $adminId : null,
            );
        } catch (InvalidArgumentException $e) {
            return $back->withErrors(['transfer' => $e->getMessage()]);
        } catch (\Throwable $e) {
            LogHelper::error('[Member] admin transfer code issue failed', [
                'project_id' => $projectId,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return $back->withErrors(['transfer' => '引き継ぎコードの発行に失敗しました']);
        }

        return $back
            ->with('transfer_member_id', $issued->memberId)
            ->with('transfer_code', $issued->code)
            ->with('transfer_expires_at', $issued->expiresAt);
    }
}

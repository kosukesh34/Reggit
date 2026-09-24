<?php

namespace App\UseCases\Member;

use App\Models\ExistingMemberLinkRequest;
use App\Models\Member;
use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Services\ExistingMemberLinkWriter;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use InvalidArgumentException;

class ConfirmExistingMemberLinkUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private ExistingMemberLinkWriter $writer,
    ) {}

    public function handle(string $token): void
    {
        $request = ExistingMemberLinkRequest::withoutGlobalScope(ProjectScope::class)
            ->where('token', $token)->first();
        if ($request === null || $request->confirmed_at !== null || $request->expires_at->lt(now())) {
            throw new InvalidArgumentException('確認リンクが無効か、期限切れです');
        }
        $project = Project::query()->whereKey($request->project_id)->where('is_active', true)->first();
        if ($project === null) {
            throw new InvalidArgumentException('確認リンクが無効か、期限切れです');
        }
        AdminContext::setCurrentProject($project);
        if (Member::query()->where('project_id', $request->project_id)->where('smaregi_customer_id', $request->smaregi_customer_id)->exists()) {
            throw new InvalidArgumentException('この会員はすでに連携されています');
        }
        $contractId = trim((string) $this->smaregiApi->getContractIdForProject((int) $request->project_id));
        $customer = $contractId === '' ? null : $this->smaregiApi->getCustomer($contractId, $request->smaregi_customer_id);
        if (! is_array($customer)) {
            throw new InvalidArgumentException('スマレジの会員を取得できませんでした');
        }
        $user = $request->user_id !== null ? User::query()->find($request->user_id) : null;
        if ($request->user_id !== null && $user === null) {
            throw new InvalidArgumentException('確認リンクが無効か、期限切れです');
        }
        $this->writer->link((int) $request->project_id, $user, $customer);
        $request->forceFill(['confirmed_at' => now()])->save();
        LogHelper::info('[Member] existing link confirmed', [
            'project_id' => $request->project_id,
            'user_id' => $request->user_id,
        ]);
    }
}

<?php

namespace App\UseCases\Member;

use App\Models\ExistingMemberLinkRequest;
use App\Models\Member;
use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Services\ExistingMemberLinkWriter;
use App\Services\Member\CurrentMemberPrincipal;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConfirmExistingMemberLinkUseCase
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private ExistingMemberLinkWriter $writer,
        private CurrentMemberPrincipal $principals,
    ) {}

    public function pendingRequest(string $token): ExistingMemberLinkRequest
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

        return $request;
    }

    public function handle(string $token, ?User $authenticatedUser): void
    {
        $snapshot = $this->pendingRequest($token);
        $contractId = $this->validateConfirmation($snapshot, $authenticatedUser);
        $customer = $this->smaregiApi->getCustomer($contractId, $snapshot->smaregi_customer_id);
        if (! is_array($customer)) {
            throw new InvalidArgumentException('スマレジの会員を取得できませんでした');
        }

        DB::transaction(function () use ($token, $authenticatedUser, $snapshot, $customer): void {
            $this->pendingRequest($token);
            $request = ExistingMemberLinkRequest::withoutGlobalScopes()->whereKey($snapshot->id)->lockForUpdate()->first();
            if ($request === null
                || $request->token !== $snapshot->token
                || $request->project_id !== $snapshot->project_id
                || $request->user_id !== $snapshot->user_id
                || $request->smaregi_customer_id !== $snapshot->smaregi_customer_id
                || $request->contract_id !== $snapshot->contract_id) {
                throw new InvalidArgumentException('確認リンクが無効か、期限切れです');
            }
            $this->validateConfirmation($request, $authenticatedUser);
            $user = $request->user_id !== null && $authenticatedUser !== null
                ? $this->principals->lock($authenticatedUser, (int) $request->project_id)
                : null;
            if ($request->user_id !== null && $user === null) {
                throw new InvalidArgumentException('確認リンクが無効か、期限切れです');
            }
            if ($user !== null) {
                $member = $user->member;
                if ($member !== null && trim((string) $member->smaregi_customer_id) !== '') {
                    throw new InvalidArgumentException('すでに既存会員連携済みです');
                }
                $user->setRelation('member', $member);
            }
            $this->writer->link((int) $request->project_id, $user, $customer);
            $request->forceFill(['confirmed_at' => now()])->save();
            LogHelper::info('[Member] existing link confirmed', [
                'project_id' => $request->project_id,
                'user_id' => $request->user_id,
            ]);
        });
    }

    private function validateConfirmation(ExistingMemberLinkRequest $request, ?User $authenticatedUser): string
    {
        if ($request->confirmed_at !== null || $request->expires_at->lt(now())) {
            throw new InvalidArgumentException('確認リンクが無効か、期限切れです');
        }
        $samePrincipal = $request->user_id === null
            ? $authenticatedUser === null
            : $authenticatedUser !== null
                && (int) $authenticatedUser->id === (int) $request->user_id
                && (int) $authenticatedUser->project_id === (int) $request->project_id;
        if (! $samePrincipal) {
            throw new InvalidArgumentException('連携を申請したアプリ・ブラウザで確認リンクを開いてください。申請に心当たりがない場合は連携しないでください。');
        }
        if (Member::query()->where('project_id', $request->project_id)->where('smaregi_customer_id', $request->smaregi_customer_id)->exists()) {
            throw new InvalidArgumentException('この会員はすでに連携されています');
        }
        $contractId = trim((string) $request->contract_id);
        if ($contractId === '' || $contractId !== trim((string) $this->smaregiApi->getContractIdForProject((int) $request->project_id))) {
            throw new InvalidArgumentException('スマレジの契約が変更されたか、確認リンクの契約を特定できません。連携を再申請してください。');
        }

        return $contractId;
    }
}

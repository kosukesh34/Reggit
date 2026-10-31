<?php

namespace App\Services\Admin;

use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Models\MemberVisit;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Services\Member\MemberPointLedger;
use App\Services\MemberVisitRecorder;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiMemberProfileSyncService;
use App\Services\UserService;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

class MemberAdminService
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiMemberProfileSyncService $profileSync,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private MemberAdminQueryService $memberQuery,
        private UserService $userService,
        private MemberVisitRecorder $memberVisitRecorder,
        private MemberPointLedger $pointLedger,
    ) {}

    /**
     * 更新・付与などの書き込み処理用。スマレジへのインバウンド同期は行わない（ローカル変更の上書きを防ぐ）。
     */
    public function findForWrite(int $id, ?int $projectId = null): Member
    {
        $query = Member::with(['user', 'pointTransactions.order', 'memberStamps.stampCard', 'memberCoupons.coupon']);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $member = $query->findOrFail($id);
        $this->pointLedger->sync($member);
        $member->refresh();

        return $member;
    }

    /**
     * 来店を記録し、最終来店日時を更新する。
     */
    public function recordVisit(
        Member $member,
        \DateTimeInterface $visitedAt,
        string $source,
        ?int $orderId = null,
        ?string $note = null,
        ?float $amount = null,
        ?string $smaregiTransactionHeadId = null,
    ): MemberVisit {
        return $this->memberVisitRecorder->record(
            $member, $visitedAt, $source, $orderId, $note, $amount, $smaregiTransactionHeadId,
        );
    }

    private function recordRankHistory(
        Member $member,
        string $fromRank,
        string $toRank,
        ?string $fromLabel,
        string $toLabel,
    ): void {
        MemberRankHistory::create([
            'project_id' => (int) $member->project_id,
            'member_id' => (int) $member->id,
            'from_rank' => $fromRank !== '' ? $fromRank : null,
            'to_rank' => $toRank,
            'from_rank_label' => $fromLabel,
            'to_rank_label' => $toLabel,
            'achieved_at' => now(),
        ]);
    }

    /**
     * 会員のユーザー情報・会員情報を一括更新する。
     * ローカルを先に保存し、スマレジ連携はベストエフォート（失敗してもローカル更新は維持）。
     */
    public function updateMemberProfile(Member $member, array $userData, array $memberData, bool $profileExtraIsPatch = false): void
    {
        $allowedUser = ['name', 'kana', 'email', 'phone'];
        $updateUser = array_intersect_key($userData, array_flip($allowedUser));
        foreach (['name', 'kana', 'phone'] as $key) {
            if (array_key_exists($key, $updateUser) && $updateUser[$key] === '') {
                $updateUser[$key] = null;
            }
        }
        if (array_key_exists('email', $updateUser) && $updateUser['email'] === '') {
            $updateUser['email'] = null;
        }

        $allowedMember = [
            'member_number', 'status', 'rank', 'birthday', 'address',
            'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'customer_store_id', 'notification_preference', 'profile_extra',
        ];
        $updateMember = array_intersect_key($memberData, array_flip($allowedMember));
        $updateMember = array_filter(
            $updateMember,
            static fn ($value, string $key): bool => ! in_array($key, ['member_number', 'status', 'rank', 'notification_preference'], true) || $value !== null,
            ARRAY_FILTER_USE_BOTH
        );
        if (array_key_exists('address', $memberData) && ($memberData['address'] === null || $memberData['address'] === '')
            && array_intersect(array_keys($memberData), ['prefecture', 'city', 'street']) === []) {
            $updateMember = array_merge($updateMember, ['address' => null, 'prefecture' => null, 'city' => null, 'street' => null]);
        }
        DB::transaction(function () use ($member, $updateUser, $updateMember, $profileExtraIsPatch): void {
            $expectedUserId = (int) $member->user_id;
            $user = User::query()->whereKey($expectedUserId)->where('project_id', $member->project_id)
                ->lockForUpdate()->first();
            $member = Member::query()->where('project_id', $member->project_id)
                ->lockForUpdate()->findOrFail($member->id);
            if ((int) $member->user_id !== $expectedUserId) {
                throw new \RuntimeException('会員情報が変更されました。再読み込みしてから保存してください。');
            }
            if ($user !== null && $updateUser !== []) {
                $user->update($updateUser);
            }
            $extraKeys = is_array($updateMember['profile_extra'] ?? null) ? array_keys($updateMember['profile_extra']) : [];
            if ($profileExtraIsPatch && is_array($updateMember['profile_extra'] ?? null)) {
                $updateMember['profile_extra'] = array_replace($member->profile_extra ?? [], $updateMember['profile_extra']);
            }
            if ($updateMember !== []) {
                $previousRank = (string) ($member->rank ?? '');
                $member->offsetUnset('rank_label');
                $member->offsetUnset('points_expiry');
                $member->update($updateMember);
                $member->refresh();
                if (array_key_exists('rank', $updateMember)) {
                    $newRank = (string) $updateMember['rank'];
                    if ($newRank !== '' && $newRank !== $previousRank) {
                        $options = $this->memberQuery->getRankOptionsForSelect((int) $member->project_id);
                        $this->recordRankHistory(
                            $member,
                            $previousRank,
                            $newRank,
                            $previousRank !== '' ? ($options[$previousRank] ?? $previousRank) : null,
                            $options[$newRank] ?? $newRank,
                        );
                        $this->smaregiCustomerRankAssignmentService->assignRankToCustomer($member, $newRank);
                    }
                }
            }
            if ($user !== null) {
                $keys = array_values(array_intersect(array_merge(array_keys($updateUser), array_keys($updateMember)), [
                    'name', 'kana', 'email', 'phone', 'member_number', 'birthday', 'address', 'postal_code',
                    'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'customer_store_id',
                ]));
                if ($extraKeys !== []) {
                    $keys = array_values(array_unique(array_merge($keys, $extraKeys)));
                }
                if ($keys !== []) {
                    $this->profileSync->record($member, $user, $keys);
                }
            }
        });
        $member->refresh();
    }

    /**
     * 管理画面から会員を削除（ソフトデリート）。
     * スマレジ連携済みなら先にPOS会員を削除し、失敗時はローカルも削除しない。
     */
    public function delete(int $memberId, int $projectId): void
    {
        $member = $this->findForWrite($memberId, $projectId);
        $expectedUserId = (int) $member->user_id;

        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId !== '' && $smaregiCustomerId !== '') {
            $result = $this->smaregiApi->deleteCustomer($contractId, $smaregiCustomerId);
            if ($result === null) {
                throw new \RuntimeException(
                    'スマレジの会員削除に失敗しました。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
                );
            }
        }

        $user = DB::transaction(function () use ($memberId, $projectId, $expectedUserId, $smaregiCustomerId): ?User {
            $user = User::query()->withoutGlobalScope(ProjectScope::class)
                ->whereKey($expectedUserId)->where('project_id', $projectId)->lockForUpdate()->first();
            $member = Member::query()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $projectId)->lockForUpdate()->findOrFail($memberId);
            if ((int) $member->user_id !== $expectedUserId
                || trim((string) $member->smaregi_customer_id) !== $smaregiCustomerId) {
                throw new \RuntimeException('会員の連携情報が変更されました。再読み込みしてから削除してください。');
            }

            $member->delete();
            if ($user !== null) {
                try {
                    if (method_exists($user, 'tokens')) {
                        $user->tokens()->delete();
                    }
                } catch (\Throwable $e) {
                    LogHelper::warning('[AdminMember] token cleanup skipped', [
                        'user_id' => (int) $user->id,
                        'message' => $e->getMessage(),
                    ]);
                }
                $this->userService->releaseLineUserIdForWithdraw($user);
                $user->delete();
            }

            return $user;
        });

        LogHelper::info('[AdminMember] member deleted', [
            'project_id' => $projectId,
            'member_id' => $memberId,
            'user_id' => $user !== null ? (int) $user->id : null,
        ]);
    }
}

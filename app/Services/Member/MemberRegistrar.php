<?php

namespace App\Services\Member;

use App\Domain\Member\Services\MemberRankResolver;
use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberNotificationPreference;
use App\Enums\MemberRegistrationMode;
use App\Enums\MemberStatus;
use App\Enums\ScheduledDeliveryType;
use App\Jobs\SendAfterRegistrationJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Models\MemberRegistrationAttempt;
use App\Models\Project;
use App\Models\User;
use App\Services\CouponIssueService;
use App\Services\CustomerStoreProfile;
use App\Services\MemberRegistrationRecovery;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** 会員登録（スマレジ会員の作成・再開、初回クーポン、登録後配信の予約を含む）。 */
class MemberRegistrar
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private CustomerStoreProfile $customerStoreProfile,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private MemberRankResolver $memberRankResolver,
        private MemberSettings $shopSettingRepository,
        private MemberRegistrationRecovery $registrationRecovery,
        private MemberNumberAssigner $memberNumbers,
    ) {}

    public function ensureMemberWhenRegistrationOptional(User $user): void
    {
        if ($user->member !== null) {
            return;
        }
        $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
        if (! MemberRegistrationMode::shouldAutoCreateMember(
            $this->shopSettingRepository->getMemberRegistrationMode($projectId)
        )) {
            return;
        }
        $payload = [];
        $lineName = is_string($user->line_name) ? trim($user->line_name) : '';
        if ($lineName !== '') {
            $payload['line_name'] = $lineName;
        }
        try {
            $this->registerMember($user, $payload, false);
        } catch (\Exception $e) {
            if ($e->getMessage() !== '既に会員登録済みです') {
                LogHelper::warning('[Member] auto create on login failed', [
                    'user_id' => (int) $user->id,
                    'error' => $e->getMessage(),
                ]);

                return;
            }
        }
        $user->unsetRelation('member');
        $user->load('member');
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function registerMember(User $user, array $data, bool $requireSmaregi = true): Member
    {
        $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
        $contractId = trim((string) $this->smaregiApi->getContractIdForProject($projectId));
        $hasPendingAttempt = MemberRegistrationAttempt::where('project_id', $projectId)->where('user_id', $user->id)->exists();
        $attempt = $contractId !== '' || $hasPendingAttempt
            ? $this->reserveRegistrationAttempt($user, $projectId, $contractId) : null;

        $smaregiCustomerId = null;
        $rankSettings = $this->shopSettingRepository->getMemberRankSettings($projectId);
        $resolved = $this->memberRankResolver->resolve(
            $rankSettings,
            $this->shopSettingRepository->getRankBasis($projectId),
            0,
            0,
            null,
        );
        $defaultRankKey = (string) ($resolved['current_rank'] ?? 'bronze');
        $defaultRank = null;
        foreach ($rankSettings as $rank) {
            if ((string) ($rank['key'] ?? '') === $defaultRankKey) {
                $defaultRank = $rank;
                break;
            }
        }
        $smaregiRankId = is_array($defaultRank) && isset($defaultRank['smaregi_rank_id'])
            ? trim((string) $defaultRank['smaregi_rank_id'])
            : '';

        if ($attempt !== null) {
            $body = $this->buildSmaregiCustomerBody(
                $attempt->member_number,
                $data,
                $smaregiRankId !== '' ? $smaregiRankId : null,
                $defaultRankKey,
                $projectId,
            );
            $smaregiCustomerId = $this->registrationRecovery->createOrRecover($attempt, $body);
            if ($smaregiCustomerId === null) {
                LogHelper::warning('[Member] smaregi create customer failed', [
                    'project_id' => $projectId,
                    'user_id' => (int) $user->id,
                    'member_number' => $attempt->member_number,
                    'detail' => $this->smaregiApi->getLastErrorDetail(),
                ]);
                if ($requireSmaregi) {
                    throw new \RuntimeException(
                        'スマレジへの会員登録に失敗しました。入力内容やスマレジ連携設定を確認してください。'
                    );
                }
            }
        }

        return DB::transaction(function () use ($user, $data, $attempt, $defaultRankKey, $smaregiCustomerId, $projectId): Member {
            $user = User::query()->whereKey($user->id)->where('project_id', $projectId)->lockForUpdate()->first();
            if ($user === null) {
                throw new \RuntimeException('会員登録の状態が変更されました。再度お試しください。');
            }
            $currentAttempt = null;
            if ($attempt !== null) {
                $currentAttempt = MemberRegistrationAttempt::whereKey($attempt->id)
                    ->where('project_id', $projectId)->where('user_id', $user->id)->lockForUpdate()->first();
                if ($currentAttempt === null || $currentAttempt->registration_key !== $attempt->registration_key
                    || $currentAttempt->member_number !== $attempt->member_number
                    || $currentAttempt->contract_id !== $attempt->contract_id) {
                    throw new \RuntimeException('会員登録の状態が変更されました。再度お試しください。');
                }
                if ($currentAttempt->contract_id !== trim((string) $this->smaregiApi->getContractIdForProject($projectId))) {
                    throw new \RuntimeException('登録途中にスマレジ契約が変更されました。管理者にご確認ください。');
                }
            }
            // Reserved codes already belong to this attempt; only unreserved allocation needs the project lock.
            if ($attempt === null) {
                Project::query()->whereKey($projectId)->lockForUpdate()->firstOrFail();
            }

            if (Member::query()->where('user_id', $user->id)->exists()) {
                throw new \Exception('既に会員登録済みです');
            }

            $memberNumber = $attempt?->member_number ?? $this->memberNumbers->next($projectId);
            $member = Member::create([
                'project_id' => $projectId,
                'user_id' => $user->id,
                'member_number' => $memberNumber,
                'smaregi_customer_id' => $smaregiCustomerId,
                'points' => 0,
                'status' => MemberStatus::ACTIVE,
                'rank' => $defaultRankKey,
                'birthday' => $data['birthday'] ?? null,
                'address' => $data['address'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'prefecture' => $data['prefecture'] ?? null,
                'city' => $data['city'] ?? null,
                'street' => $data['street'] ?? null,
                'sex' => $data['sex'] ?? null,
                'mail_receive_flag' => $data['mail_receive_flag'] ?? null,
                'customer_store_id' => ($data['customer_store_id'] ?? '') !== '' ? $data['customer_store_id'] : null,
                'notification_preference' => MemberNotificationPreference::normalize(
                    isset($data['notification_preference']) ? (string) $data['notification_preference'] : null
                ),
                'profile_extra' => $this->extractCustomProfileExtra($projectId, $data),
            ]);

            $this->couponIssueService->issueForFirstRegistration($member);

            AfterRegistrationDelivery::where('project_id', $projectId)
                ->where('is_active', true)
                ->where('schedule_type', ScheduledDeliveryType::NEW_USER_MINUTES)
                ->get()
                ->each(function (AfterRegistrationDelivery $delivery) use ($member): void {
                    $job = SendAfterRegistrationJob::dispatch($member->id, $delivery->id)->afterCommit();
                    $delay = $delivery->resolvedDelayMinutes();
                    if ($delay > 0) {
                        $job->delay(now()->addMinutes($delay));
                    }
                });

            $userUpdates = array_filter([
                'name' => isset($data['name']) ? trim((string) $data['name']) : null,
                'kana' => $data['kana'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
            $lineName = isset($data['line_name']) ? trim((string) $data['line_name']) : '';
            if ($lineName !== '') {
                $userUpdates['line_name'] = $lineName;
            }
            if ($userUpdates !== []) {
                $user->update($userUpdates);
            }

            if ($currentAttempt !== null && $smaregiCustomerId !== null) {
                $currentAttempt->delete();
            }

            return $member->load('user');
        });
    }

    private function reserveRegistrationAttempt(User $user, int $projectId, string $contractId): MemberRegistrationAttempt
    {
        return DB::transaction(function () use ($user, $projectId, $contractId): MemberRegistrationAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            Project::query()->whereKey($projectId)->lockForUpdate()->firstOrFail();
            if (Member::query()->where('user_id', $user->id)->exists()) {
                throw new \Exception('既に会員登録済みです');
            }
            $attempt = MemberRegistrationAttempt::where('project_id', $projectId)->where('user_id', $user->id)->first();
            if ($attempt !== null) {
                if ($attempt->contract_id !== $contractId) {
                    throw new \RuntimeException('登録途中にスマレジ契約が変更されました。管理者にご確認ください。');
                }

                return $attempt;
            }

            return MemberRegistrationAttempt::create([
                'project_id' => $projectId, 'user_id' => $user->id,
                'member_number' => $this->memberNumbers->next($projectId),
                'contract_id' => $contractId, 'registration_key' => (string) Str::uuid(),
            ]);
        });
    }

    /** 登録データから任意項目のみ抽出 */
    private function extractCustomProfileExtra(int $projectId, array $data): array
    {
        $settings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
        $customKeys = array_column($settings['custom_fields'] ?? [], 'key');
        $out = [];
        foreach ($customKeys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                $out[$key] = $data[$key];
            }
        }

        return $out;
    }

    /** スマレジ会員登録用のリクエストボディを組み立て（会員コードは20文字以内） */
    private function buildSmaregiCustomerBody(
        string $memberNumber,
        array $data,
        ?string $smaregiRankId = null,
        ?string $rankKey = null,
        ?int $projectId = null,
    ): array {
        $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromProfileData(
            $projectId !== null ? $this->customerStoreProfile->forSmaregi($data, $projectId) : $data,
            $projectId !== null
                ? ($this->shopSettingRepository->getProfileFieldSettings($projectId)['custom_fields'] ?? [])
                : [],
        );
        $body['customerCode'] = Str::limit($memberNumber, 20, '');
        $body['entryDate'] = now()->format('Y-m-d');
        if ($smaregiRankId !== null && $smaregiRankId !== '' && $rankKey !== null && $projectId !== null) {
            $rankBody = $this->smaregiCustomerRankAssignmentService->buildCustomerRankBody($projectId, $rankKey);
            if ($rankBody !== []) {
                $body = array_merge($body, $rankBody);
            } else {
                $body['rank'] = $smaregiRankId;
            }
        } elseif ($smaregiRankId !== null && $smaregiRankId !== '') {
            $body['rank'] = $smaregiRankId;
        }

        return $this->smaregiCustomerUpdatePayloadBuilder->withRegisterNamePlaceholders($body);
    }
}

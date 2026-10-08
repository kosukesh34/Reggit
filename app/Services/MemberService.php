<?php

namespace App\Services;

use App\Domain\Member\Services\MemberRankResolver;
use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberNotificationPreference;
use App\Enums\MemberRegistrationMode;
use App\Enums\MemberStatus;
use App\Enums\ProfileCustomFieldInputType;
use App\Enums\ScheduledDeliveryType;
use App\Jobs\SendAfterRegistrationJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Models\User;
use App\Rules\ProfileValidationRules;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private CustomerStoreProfile $customerStoreProfile,
        private SmaregiCustomerPointsInboundSyncService $smaregiCustomerPointsInboundSyncService,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private MemberRankCalculator $rankCalculator,
        private MemberRankResolver $memberRankResolver,
        private MemberSettings $shopSettingRepository,
        private MemberPointsExpiryCalculator $expiryCalculator,
    ) {}

    public function getMemberData(User $user): array
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
            $profileSettings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
            $profileFormFields = $this->buildProfileFormFields($profileSettings, $projectId);

            return [
                'is_member' => false,
                'user_id' => (int) $user->id,
                'message' => '会員登録がまだです',
                'show_member_card_for_guest' => $this->shopSettingRepository->getShowMemberCardForGuest($projectId),
                'member_registration_mode' => $this->shopSettingRepository->getMemberRegistrationMode($projectId),
                'profile_field_settings' => $profileFormFields,
                'profile_image_enabled' => $this->profileImageEnabled($profileSettings),
                'profile' => [],
            ];
        }

        $this->maybeSyncMemberPointsFromSmaregi($member);
        $member->refresh();
        $member->syncPointsLedger();
        $member->refresh();

        $rankInfo = $this->getRankInfo($member);
        $expiryInfo = $this->expiryCalculator->getExpiryInfo($member);

        $projectId = AdminContext::resolveProjectId($member->project_id !== null ? (int) $member->project_id : null);
        $profileSettings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
        $profileFormFields = $this->buildProfileFormFields($profileSettings, $projectId);
        $profileValues = $this->getProfileValues($member->user, $member);

        return [
            'is_member' => true,
            'user_id' => (int) $user->id,
            'member' => $member->load('user'),
            'user' => $member->user,
            'profile_field_settings' => $profileFormFields,
            'profile_image_enabled' => $this->profileImageEnabled($profileSettings),
            'profile' => $profileValues,
            'points' => $member->getTotalPointsFromTransactions(),
            'current_rank' => $rankInfo['current_rank'],
            'current_rank_label' => $rankInfo['current_rank_label'],
            'current_rank_icon' => $rankInfo['current_rank_icon'],
            'current_rank_background' => $rankInfo['current_rank_background'],
            'current_rank_color' => $rankInfo['current_rank_color'] ?? null,
            'next_rank' => $rankInfo['next_rank'],
            'next_rank_label' => $rankInfo['next_rank_label'],
            'points_to_next_rank' => $rankInfo['points_to_next_rank'],
            'rank_basis' => $rankInfo['rank_basis'],
            'rank_metric' => $rankInfo['rank_metric'],
            'points_expiry' => $expiryInfo,
            'show_points_display' => $this->shopSettingRepository->getShowPointsDisplay($projectId)
                && $this->shopSettingRepository->getUsePoints($projectId),
            'show_rank_progress' => $this->shopSettingRepository->getShowRankProgress($projectId),
            'member_registration_mode' => $this->shopSettingRepository->getMemberRegistrationMode($projectId),
            'member_card_design' => $this->shopSettingRepository->getMemberCardDesign($projectId),
            'member_card_logo_url' => $this->shopSettingRepository->getFrontendLogoUrl($projectId),
            'member_card_display_type' => $this->shopSettingRepository->getMemberCardDisplayType($projectId),
            'member_card_code_type' => $this->shopSettingRepository->getMemberCardCodeType($projectId),
            'rank_up_history' => $this->buildRankUpHistory($member),
            'days_to_next_rank' => $rankInfo['days_to_next_rank'] ?? null,
            'days_required_for_next_rank' => $rankInfo['days_required_for_next_rank'] ?? null,
            'rank_aggregation_type' => $rankInfo['rank_aggregation_type'] ?? 'total',
        ];
    }

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
     * @return list<array{from_rank_label: string, to_rank_label: string, achieved_at: string}>
     */
    private function buildRankUpHistory(Member $member): array
    {
        $rows = [];
        foreach (
            $member->rankHistories()
                ->orderByDesc('achieved_at')
                ->get() as $history
        ) {
            $rows[] = [
                'from_rank_label' => trim((string) ($history->from_rank_label ?? '')) !== ''
                    ? (string) $history->from_rank_label
                    : '—',
                'to_rank_label' => trim((string) ($history->to_rank_label ?? '')) !== ''
                    ? (string) $history->to_rank_label
                    : (string) ($history->to_rank ?? '—'),
                'achieved_at' => $history->achieved_at !== null
                    ? $history->achieved_at->format('Y/m/d')
                    : '',
            ];
        }

        return $rows;
    }

    private function maybeSyncMemberPointsFromSmaregi(Member $member): void
    {
        if (config('smaregi.inbound_sync_on_member_fetch', true) !== true) {
            return;
        }

        $ttl = (int) config('smaregi.inbound_sync_on_member_fetch_ttl_seconds', 30);
        $cacheKey = 'smaregi_member_fetch_sync:'.(int) $member->id;
        if ($ttl > 0 && Cache::has($cacheKey)) {
            return;
        }

        try {
            $this->smaregiCustomerPointsInboundSyncService->syncMemberFromSmaregi($member);
            if ($ttl > 0) {
                Cache::put($cacheKey, 1, $ttl);
            }
        } catch (\Throwable $e) {
            LogHelper::warning('[Member] smaregi inbound sync on fetch failed', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** プロフィール設定に基づくバリデーションルールを組み立て */
    public function getProfileValidationRules(int $projectId): array
    {
        $settings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
        $rules = [];
        $formatRules = ProfileValidationRules::formatRulesByField();
        foreach ($settings['smaregi_fields'] ?? [] as $key => $field) {
            if ((bool) ($field['enabled'] ?? false) === false) {
                continue;
            }
            if ($key === 'profile_image') {
                continue;
            }
            $rule = [(bool) ($field['required'] ?? false) === true ? 'required' : 'nullable'];
            foreach ($formatRules[$key] ?? ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX] as $formatRule) {
                $rule[] = $formatRule;
            }
            if ($key === 'customer_store_id') {
                $rule[] = $this->customerStoreProfile->rule($projectId);
            }
            $rules[$key] = $rule;
        }
        foreach ($settings['custom_fields'] ?? [] as $cf) {
            $key = $cf['key'] ?? null;
            if ($key === null || $key === '') {
                continue;
            }
            foreach ($this->buildCustomFieldValidationRules((string) $key, $cf) as $ruleKey => $rule) {
                $rules[$ruleKey] = $rule;
            }
        }

        if (isset($rules['name']) && is_array($rules['name'])) {
            $rules['name'] = array_map(
                static fn (string $rule): string => $rule === 'required' ? 'required_without:line_name' : $rule,
                $rules['name'],
            );
        }
        $rules['line_name'] = ['nullable', 'string', 'max:'.ProfileValidationRules::NAME_MAX];
        $rules['notification_preference'] = ['nullable', Rule::in(MemberNotificationPreference::keys())];

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $cf
     * @return array<string, list<string>>
     */
    private function buildCustomFieldValidationRules(string $key, array $cf): array
    {
        $required = (bool) ($cf['required'] ?? false) === true;
        $inputType = (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT);
        $base = [$required ? 'required' : 'nullable'];
        $values = isset($cf['values']) && is_array($cf['values'])
            ? array_values(array_filter(array_map('strval', $cf['values']), static fn (string $v): bool => $v !== ''))
            : [];

        if ($inputType === ProfileCustomFieldInputType::CHECKBOX) {
            $rules = [
                $key => array_merge($base, ['array']),
            ];
            if ($values !== []) {
                $rules[$key.'.*'] = ['string', 'in:'.implode(',', $values)];
            }

            return $rules;
        }

        if ($inputType === ProfileCustomFieldInputType::TEXTAREA) {
            return [$key => array_merge($base, ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX])];
        }

        if (ProfileCustomFieldInputType::needsValues($inputType) && $values !== []) {
            return [$key => array_merge($base, ['string', 'in:'.implode(',', $values)])];
        }

        return [$key => array_merge($base, ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX])];
    }

    /** プロフィール設定で有効な項目のみのフォーム用定義（キー・ラベル・必須） */
    private function buildProfileFormFields(array $profileSettings, int $projectId): array
    {
        $out = [];
        foreach ($profileSettings['smaregi_fields'] ?? [] as $key => $field) {
            if ((bool) ($field['enabled'] ?? false) === false) {
                continue;
            }
            if ($key === 'profile_image') {
                continue;
            }
            $out[] = [
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'required' => (bool) ($field['required'] ?? false) === true,
                'input_type' => $this->resolveBasicFieldInputType($key),
                'options' => $key === 'customer_store_id' ? $this->customerStoreProfile->options($projectId) : [],
                'description' => $key === 'customer_store_id' ? '主に利用する店舗を選択してください。' : null,
            ];
        }
        foreach ($profileSettings['custom_fields'] ?? [] as $cf) {
            $out[] = [
                'key' => $cf['key'] ?? '',
                'label' => $cf['label'] ?? '',
                'required' => (bool) ($cf['required'] ?? false) === true,
                'input_type' => (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT),
                'values' => isset($cf['values']) && is_array($cf['values']) ? array_values($cf['values']) : [],
                'description' => isset($cf['description']) ? (string) $cf['description'] : null,
            ];
        }

        $out = array_values(array_filter(
            $out,
            static fn (array $field): bool => ($field['key'] ?? '') !== 'notification_preference'
        ));
        $out[] = [
            'key' => 'notification_preference',
            'label' => 'お知らせ通知',
            'required' => true,
            'input_type' => ProfileCustomFieldInputType::RADIO,
            'values' => MemberNotificationPreference::keys(),
            'description' => '配信通知の受け取り方は、あとからプロフィール編集で変更できます。',
        ];

        return $out;
    }

    private function resolveBasicFieldInputType(string $key): string
    {
        return match ($key) {
            'birthday' => 'date',
            'customer_store_id' => ProfileCustomFieldInputType::SELECTBOX,
            'sex', 'mail_receive_flag' => ProfileCustomFieldInputType::RADIO,
            'address' => ProfileCustomFieldInputType::TEXTAREA,
            default => ProfileCustomFieldInputType::TEXT,
        };
    }

    private function profileImageEnabled(array $profileSettings): bool
    {
        return (bool) ($profileSettings['smaregi_fields']['profile_image']['enabled'] ?? false);
    }

    private function assertProfileImageAvailable(User $user): void
    {
        $projectId = $user->member?->project_id;
        if ($projectId === null) {
            throw ValidationException::withMessages(['image' => '会員登録が必要です']);
        }
        $settings = $this->shopSettingRepository->getProfileFieldSettings((int) $projectId);
        if ($this->profileImageEnabled($settings) !== true) {
            throw ValidationException::withMessages(['image' => 'プロフィール画像の設定は現在利用できません']);
        }
    }

    /** 会員・ユーザーからプロフィール値を配列で取得 */
    private function getProfileValues(User $user, Member $member): array
    {
        $profile = [
            'profile_image_url' => $user->profile_image_path
                ? asset('storage/'.ltrim($user->profile_image_path, '/'))
                : asset('images/no-image-profile.svg'),
            'has_profile_image' => $user->profile_image_path !== null && $user->profile_image_path !== '',
            'name' => $user->name,
            'kana' => $user->kana ?? null,
            'phone' => $user->phone,
            'email' => $user->email,
            'postal_code' => $member->postal_code ?? null,
            'address' => $this->smaregiCustomerUpdatePayloadBuilder->composeAddress([
                'address' => $member->address,
                'prefecture' => $member->prefecture,
                'city' => $member->city,
                'street' => $member->street,
            ]),
            'birthday' => $member->birthday ? $member->birthday->format('Y-m-d') : null,
            'sex' => $member->sex ?? null,
            'mail_receive_flag' => $member->mail_receive_flag ?? null,
            'customer_store_id' => $member->customer_store_id === null ? null : (string) $member->customer_store_id,
            'notification_preference' => MemberNotificationPreference::normalize($member->notification_preference),
        ];
        $extra = $member->profile_extra ?? [];
        if (is_array($extra)) {
            foreach ($extra as $k => $v) {
                $profile[$k] = $v;
            }
        }

        return $profile;
    }

    public function saveProfileImage(User $user, UploadedFile $image): array
    {
        $this->assertProfileImageAvailable($user);
        $oldPath = $user->profile_image_path;
        $path = $image->store('member-profile-images/'.(int) $user->project_id, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store image');
        }
        $user->update(['profile_image_path' => $path]);
        if ($oldPath !== null && $oldPath !== '' && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->getMemberData($user->fresh(['member']));
    }

    public function deleteProfileImage(User $user): array
    {
        $this->assertProfileImageAvailable($user);
        $oldPath = $user->profile_image_path;
        $user->update(['profile_image_path' => null]);
        if ($oldPath !== null && $oldPath !== '') {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->getMemberData($user->fresh(['member']));
    }

    public function updateProfile(User $user, array $data): array
    {
        $member = $user->member;
        if ($member === null) {
            throw ValidationException::withMessages(['member' => '会員登録が必要です']);
        }

        $projectId = AdminContext::resolveProjectId($member->project_id !== null ? (int) $member->project_id : null);
        $settings = $this->shopSettingRepository->getProfileFieldSettings($projectId);

        $userUpdates = [];
        $memberUpdates = [];
        $extraUpdates = [];

        $userKeys = ['name', 'kana', 'email', 'phone'];
        foreach ($userKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            if ($key === 'email' && $val === '') {
                $val = null;
            }
            $userUpdates[$key] = $val === '' ? null : $val;
        }

        $memberKeys = ['birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'customer_store_id', 'notification_preference'];
        foreach ($memberKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            if ($key === 'birthday') {
                $memberUpdates[$key] = $val ?: null;
            } elseif ($key === 'notification_preference') {
                $memberUpdates[$key] = MemberNotificationPreference::normalize(is_string($val) ? $val : null);
            } else {
                $memberUpdates[$key] = $val === '' ? null : $val;
            }
        }

        $customKeys = array_column($settings['custom_fields'] ?? [], 'key');
        foreach ($customKeys as $key) {
            if (array_key_exists($key, $data)) {
                $extraUpdates[$key] = $data[$key] === '' ? null : $data[$key];
            }
        }

        if ($userUpdates !== []) {
            $user->update($userUpdates);
        }
        if ($memberUpdates !== []) {
            $member->update($memberUpdates);
        }
        if ($extraUpdates !== []) {
            $current = $member->profile_extra ?? [];
            $member->update(['profile_extra' => array_merge($current, $extraUpdates)]);
        }

        $smaregiCustomerId = $member->smaregi_customer_id ?? '';
        if ($smaregiCustomerId !== '') {
            $contractId = $this->smaregiApi->getContractIdForProject($projectId);
            $contractId = is_string($contractId) ? trim($contractId) : '';
            if ($contractId !== '') {
                $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromProfileData(
                    $this->customerStoreProfile->forSmaregi($data, $projectId),
                    $settings['custom_fields'] ?? [],
                );
                if ($body !== []) {
                    $this->smaregiApi->clearLastErrorDetail();
                    $result = $this->smaregiApi->updateCustomer($contractId, $smaregiCustomerId, $body);
                    if ($result === null) {
                        $detail = $this->smaregiApi->getLastErrorDetail();
                        LogHelper::warning('[Member] smaregi profile sync failed after local update', [
                            'member_id' => (int) $member->id,
                            'project_id' => $projectId,
                            'smaregi_customer_id' => $smaregiCustomerId,
                            'detail' => $detail,
                        ]);
                    }
                }
            }
        }

        return $this->getMemberData($user->fresh(['member']));
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

    /**
     * @param  array<string, string|int|float|bool|null>  $data
     */
    public function registerMember(User $user, array $data, bool $requireSmaregi = true): Member
    {
        return DB::transaction(function () use ($user, $data, $requireSmaregi): Member {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (Member::query()->where('user_id', $user->id)->exists()) {
                throw new \Exception('既に会員登録済みです');
            }

            $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
            $memberNumber = $this->generateMemberNumber($projectId);
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

            $contractId = $this->smaregiApi->getContractIdForProject($projectId);
            $contractId = is_string($contractId) ? trim($contractId) : '';
            if ($contractId !== '') {
                $body = $this->buildSmaregiCustomerBody(
                    $memberNumber,
                    $data,
                    $smaregiRankId !== '' ? $smaregiRankId : null,
                    $defaultRankKey,
                    $projectId,
                );
                $this->smaregiApi->clearLastErrorDetail();
                $result = $this->smaregiApi->createCustomer($contractId, $body);
                if ($result === null || ! isset($result['customerId']) || (string) $result['customerId'] === '') {
                    LogHelper::warning('[Member] smaregi create customer failed', [
                        'project_id' => $projectId,
                        'user_id' => (int) $user->id,
                        'member_number' => $memberNumber,
                        'detail' => $this->smaregiApi->getLastErrorDetail(),
                    ]);
                    if ($requireSmaregi) {
                        throw new \RuntimeException(
                            'スマレジへの会員登録に失敗しました。入力内容やスマレジ連携設定を確認してください。'
                        );
                    }
                } else {
                    $smaregiCustomerId = (string) $result['customerId'];
                }
            }

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
                    $job = SendAfterRegistrationJob::dispatch($member->id, $delivery->id);
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

            return $member->load('user');
        });
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

    private function getRankInfo(Member $member): array
    {
        return $this->rankCalculator->getRankInfo($member);
    }

    private function generateMemberNumber(int $projectId): string
    {
        $type = $this->shopSettingRepository->getMemberCodeType($projectId);
        $format = $this->shopSettingRepository->getMemberCodeFormat($projectId);
        $length = max(1, min(20, $this->shopSettingRepository->getMemberCodeLength($projectId)));
        $prefix = $this->shopSettingRepository->getMemberCodePrefix($projectId);
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix);
        $prefixLen = strlen($prefix);
        $bodyLen = min($length, 20 - $prefixLen);
        if ($bodyLen < 1) {
            $bodyLen = 1;
        }

        $charsNumeric = '0123456789';
        $charsAlpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 英大文字+数字（紛らわしいI,O,1,0を除く）

        if ($type === 'sequential') {
            $next = $this->shopSettingRepository->getMemberCodeNextNumber($projectId);
            $maxExisting = Member::where('project_id', $projectId)
                ->whereNotNull('member_number')
                ->where('member_number', '!=', '')
                ->get()
                ->map(function (Member $m) use ($prefix) {
                    $num = $m->member_number;
                    if ($prefix !== '' && str_starts_with($num, $prefix)) {
                        $num = substr($num, strlen($prefix));
                    }

                    return ctype_digit($num) ? (int) $num : 0;
                })
                ->max();
            $next = max($next, ($maxExisting ?? 0) + 1);
            $this->shopSettingRepository->setMemberCodeNextNumber($next + 1, $projectId);
            $body = sprintf('%0'.$bodyLen.'d', $next);
            if (strlen($body) > $bodyLen) {
                $body = substr($body, -$bodyLen);
            }
            $candidate = $prefix.$body;

            return strlen($candidate) <= 20 ? $candidate : substr($candidate, 0, 20);
        }

        $chars = ($format === 'alphanumeric') ? $charsAlpha : $charsNumeric;
        $maxAttempts = 20;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $body = '';
            $charLen = strlen($chars);
            for ($j = 0; $j < $bodyLen; $j++) {
                $body .= $chars[random_int(0, $charLen - 1)];
            }
            $candidate = $prefix.$body;
            if (strlen($candidate) > 20) {
                $candidate = substr($candidate, 0, 20);
            }
            $exists = Member::where('project_id', $projectId)->where('member_number', $candidate)->exists();
            if (! $exists) {
                return $candidate;
            }
        }

        return $prefix.sprintf('%0'.$bodyLen.'d', (time() + random_int(0, 9999)) % (10 ** $bodyLen));
    }
}

<?php

namespace App\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\MemberStatus;
use App\Enums\ProfileCustomFieldInputType;
use App\Jobs\SendAfterRegistrationJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Models\PointTransaction;
use App\Models\User;
use App\Rules\ProfileValidationRules;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\UseCases\Coupon\IssueCouponOnRankUpUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private SmaregiCustomerPointsInboundSyncService $smaregiCustomerPointsInboundSyncService,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private MemberRankCalculator $rankCalculator,
        private ShopSettingRepository $shopSettingRepository,
        private IssueCouponOnRankUpUseCase $issueCouponOnRankUpUseCase,
    ) {}

    public function getMemberData(User $user): array
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = $user->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
            $profileSettings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
            $profileFormFields = $this->buildProfileFormFields($profileSettings);

            return [
                'is_member' => false,
                'message' => '会員登録がまだです',
                'show_member_card_for_guest' => $this->shopSettingRepository->getShowMemberCardForGuest($projectId),
                'member_registration_mode' => $this->shopSettingRepository->getMemberRegistrationMode($projectId),
                'profile_field_settings' => $profileFormFields,
                'profile' => [],
            ];
        }

        $this->maybeSyncMemberPointsFromSmaregi($member);
        $member->refresh();
        $member->syncPointsLedger();
        $this->syncMemberRank($member);
        $member->refresh();

        $rankInfo = $this->getRankInfo($member);
        $expiryInfo = $this->getPointsExpiryInfo($member);

        $projectId = $member->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
        $profileSettings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
        $profileFormFields = $this->buildProfileFormFields($profileSettings);
        $profileValues = $this->getProfileValues($member->user, $member);

        return [
            'is_member' => true,
            'member' => $member->load('user'),
            'user' => $member->user,
            'profile_field_settings' => $profileFormFields,
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
            'show_points_display' => $this->shopSettingRepository->getShowPointsDisplay($projectId),
            'show_rank_progress' => $this->shopSettingRepository->getShowRankProgress($projectId),
            'member_registration_mode' => $this->shopSettingRepository->getMemberRegistrationMode($projectId),
            'member_card_design' => $this->shopSettingRepository->getMemberCardDesign($projectId),
            'member_card_logo_url' => $this->shopSettingRepository->getFrontendLogoUrl($projectId),
            'member_card_display_type' => $this->shopSettingRepository->getMemberCardDisplayType($projectId),
            'member_card_code_type' => $this->shopSettingRepository->getMemberCardCodeType($projectId),
            'rank_up_history' => $this->buildRankUpHistory($member),
        ];
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
            $rule = [(bool) ($field['required'] ?? false) === true ? 'required' : 'nullable'];
            foreach ($formatRules[$key] ?? ['string', 'max:'.ProfileValidationRules::CUSTOM_TEXT_MAX] as $formatRule) {
                $rule[] = $formatRule;
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
    private function buildProfileFormFields(array $profileSettings): array
    {
        $out = [];
        foreach ($profileSettings['smaregi_fields'] ?? [] as $key => $field) {
            if ((bool) ($field['enabled'] ?? false) === false) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'required' => (bool) ($field['required'] ?? false) === true,
                'input_type' => $this->resolveBasicFieldInputType($key),
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

        return $out;
    }

    private function resolveBasicFieldInputType(string $key): string
    {
        return match ($key) {
            'birthday' => 'date',
            'sex', 'mail_receive_flag' => ProfileCustomFieldInputType::RADIO,
            'address' => ProfileCustomFieldInputType::TEXTAREA,
            default => ProfileCustomFieldInputType::TEXT,
        };
    }

    /** 会員・ユーザーからプロフィール値を配列で取得 */
    private function getProfileValues(User $user, Member $member): array
    {
        $profile = [
            'name' => $user->name,
            'kana' => $user->kana ?? null,
            'phone' => $user->phone,
            'email' => $user->email,
            'postal_code' => $member->postal_code ?? null,
            'prefecture' => $member->prefecture ?? null,
            'city' => $member->city ?? null,
            'street' => $member->street ?? null,
            'address' => $member->address,
            'birthday' => $member->birthday ? $member->birthday->format('Y-m-d') : null,
            'sex' => $member->sex ?? null,
            'mail_receive_flag' => $member->mail_receive_flag ?? null,
        ];
        $extra = $member->profile_extra ?? [];
        if (is_array($extra)) {
            foreach ($extra as $k => $v) {
                $profile[$k] = $v;
            }
        }

        return $profile;
    }

    public function updateProfile(User $user, array $data): array
    {
        $member = $user->member;
        if ($member === null) {
            throw new \Exception('会員登録が必要です');
        }

        $projectId = $member->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
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

        $memberKeys = ['birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag'];
        foreach ($memberKeys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $val = $data[$key];
            if ($key === 'birthday') {
                $memberUpdates[$key] = $val ?: null;
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
                $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromProfileData($data);
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

    public function registerMember(User $user, array $data): Member
    {
        return DB::transaction(function () use ($user, $data): Member {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (Member::query()->where('user_id', $user->id)->exists()) {
                throw new \Exception('既に会員登録済みです');
            }

            $projectId = $user->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
            $memberNumber = $this->generateMemberNumber($projectId);
            $smaregiCustomerId = null;
            $rankSettings = $this->shopSettingRepository->getMemberRankSettings($projectId);
            $defaultRank = collect($rankSettings)->first(fn ($r) => ($r['is_active'] ?? false) === true);
            $defaultRankKey = is_array($defaultRank) ? ((string) ($defaultRank['key'] ?? 'bronze')) : 'bronze';
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

                    throw new \RuntimeException(
                        'スマレジへの会員登録に失敗しました。入力内容やスマレジ連携設定を確認してください。'
                    );
                }
                $smaregiCustomerId = (string) $result['customerId'];
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
                'profile_extra' => $this->extractCustomProfileExtra($projectId, $data),
            ]);

            $this->couponIssueService->issueForFirstRegistration($member);

            AfterRegistrationDelivery::where('project_id', $projectId)
                ->where('is_active', true)
                ->get()
                ->each(function (AfterRegistrationDelivery $delivery) use ($member): void {
                    SendAfterRegistrationJob::dispatch($member->id, $delivery->id)
                        ->delay(now()->addMinutes($delivery->delay_minutes));
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
        $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromProfileData($data);
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

    private function syncMemberRank(Member $member): void
    {
        $previousRank = (string) ($member->rank ?? '');
        $rankInfo = $this->getRankInfo($member);
        $newRank = (string) ($rankInfo['current_rank'] ?? '');
        if ($newRank !== '' && $newRank !== $previousRank) {
            $member->update(['rank' => $newRank]);
            $this->recordRankHistory(
                $member,
                $previousRank,
                $newRank,
                $this->resolveRankLabel((int) $member->project_id, $previousRank),
                (string) ($rankInfo['current_rank_label'] ?? $newRank),
            );
            $this->issueCouponOnRankUpUseCase->handle($member->fresh() ?? $member, $previousRank, $newRank);
            $this->smaregiCustomerRankAssignmentService->assignRankToCustomer($member, $newRank);
        }
    }

    private function recordRankHistory(
        Member $member,
        string $fromRank,
        string $toRank,
        string $fromLabel,
        string $toLabel,
    ): void {
        MemberRankHistory::create([
            'project_id' => (int) $member->project_id,
            'member_id' => (int) $member->id,
            'from_rank' => $fromRank !== '' ? $fromRank : null,
            'to_rank' => $toRank,
            'from_rank_label' => $fromRank !== '' ? $fromLabel : null,
            'to_rank_label' => $toLabel,
            'achieved_at' => now(),
        ]);
    }

    private function resolveRankLabel(int $projectId, string $rankKey): string
    {
        if ($rankKey === '') {
            return '—';
        }
        foreach ($this->shopSettingRepository->getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') === $rankKey) {
                return (string) ($rank['label'] ?? $rankKey);
            }
        }

        return $rankKey;
    }

    private function getRankInfo(Member $member): array
    {
        return $this->rankCalculator->getRankInfo($member);
    }

    private function getPointsExpiryInfo(Member $member): ?array
    {
        $earliest = PointTransaction::where('member_id', $member->id)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at')
            ->first();

        if ($earliest === null) {
            return null;
        }

        $pointsExpiring = PointTransaction::where('member_id', $member->id)
            ->where('type', 'earned')
            ->where('expires_at', $earliest->expires_at)
            ->sum('points');

        return [
            'expires_at' => $earliest->expires_at->format('Y-m-d'),
            'expires_at_label' => $earliest->expires_at->format('Y年n月j日'),
            'points_expiring' => (int) $pointsExpiring,
        ];
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

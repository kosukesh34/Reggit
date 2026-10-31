<?php

namespace App\Services\Member;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberNotificationPreference;
use App\Enums\ProfileCustomFieldInputType;
use App\Models\Member;
use App\Models\User;
use App\Services\CustomerStoreProfile;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Services\MemberRegistrationRecovery;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** 会員証・プロフィール画面に返す会員データ（未登録ならゲスト用データ）を組み立てる。 */
class MemberDataReader
{
    public function __construct(
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private CustomerStoreProfile $customerStoreProfile,
        private SmaregiCustomerPointsInboundSyncService $smaregiCustomerPointsInboundSyncService,
        private MemberRankCalculator $rankCalculator,
        private MemberSettings $shopSettingRepository,
        private MemberPointsExpiryCalculator $expiryCalculator,
        private MemberRegistrationRecovery $registrationRecovery,
        private MemberPointLedger $pointLedger,
    ) {}

    public function getMemberData(User $user, bool $syncFromSmaregi = true): array
    {
        $projectId = AdminContext::resolveProjectId($user->project_id !== null ? (int) $user->project_id : null);
        $member = $user->member()->where('project_id', $projectId)->first();
        if ($member === null) {
            return $this->getGuestMemberData($user, $projectId);
        }

        if ((string) $member->smaregi_customer_id === '' && Cache::add('member_registration_recovery:'.$member->id, 1, 30)) {
            try {
                $this->registrationRecovery->recoverForMember($member);
            } catch (\Throwable $exception) {
                LogHelper::warning('[Member] registration recovery unavailable', ['member_id' => $member->id]);
            }
        }
        $member = $user->member()->where('project_id', $projectId)->whereKey($member->id)->first();
        if ($member === null) {
            return $this->getGuestMemberData($user, $projectId);
        }
        if ($syncFromSmaregi) {
            $this->maybeSyncMemberPointsFromSmaregi($member);
        }

        return DB::transaction(function () use ($user, $projectId, $member): array {
            $member = $user->member()->where('project_id', $projectId)->whereKey($member->id)
                ->lockForUpdate()->first();
            if ($member === null) {
                return $this->getGuestMemberData($user, $projectId);
            }
            $member->refresh();
            $this->pointLedger->sync($member);
            $member->refresh();

            $rankInfo = $this->rankCalculator->getRankInfo($member);
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
                'profile_image_enabled' => self::profileImageEnabled($profileSettings),
                'profile' => $profileValues,
                'points' => $this->pointLedger->total($member),
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
        }, 3);
    }

    public static function profileImageEnabled(array $profileSettings): bool
    {
        return (bool) ($profileSettings['smaregi_fields']['profile_image']['enabled'] ?? false);
    }

    private function getGuestMemberData(User $user, int $projectId): array
    {
        $profileSettings = $this->shopSettingRepository->getProfileFieldSettings($projectId);
        $profileFormFields = $this->buildProfileFormFields($profileSettings, $projectId);

        return [
            'is_member' => false,
            'user_id' => (int) $user->id,
            'message' => '会員登録がまだです',
            'show_member_card_for_guest' => $this->shopSettingRepository->getShowMemberCardForGuest($projectId),
            'member_registration_mode' => $this->shopSettingRepository->getMemberRegistrationMode($projectId),
            'profile_field_settings' => $profileFormFields,
            'profile_image_enabled' => self::profileImageEnabled($profileSettings),
            'profile' => [],
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
            if ($key === 'customer_store_id') {
                $out[] = $this->storeProfileField($projectId, (bool) ($field['required'] ?? false));

                continue;
            }
            $out[] = [
                'key' => $key,
                'label' => $field['label'] ?? $key,
                'required' => (bool) ($field['required'] ?? false) === true,
                'input_type' => $this->resolveBasicFieldInputType($key),
                'options' => [],
                'description' => null,
            ];
        }
        // ご利用店舗はアプリの注文・お知らせの店舗と同じ。店舗が複数あれば、項目設定に関係なく選べるようにする
        if (! in_array('customer_store_id', array_column($out, 'key'), true) && count($this->customerStoreProfile->options($projectId)) > 1) {
            array_unshift($out, $this->storeProfileField($projectId, false));
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

    /** @return array<string, mixed> */
    private function storeProfileField(int $projectId, bool $required): array
    {
        return [
            'key' => 'customer_store_id',
            'label' => 'ご利用店舗',
            'required' => $required,
            'input_type' => ProfileCustomFieldInputType::SELECTBOX,
            'options' => $this->customerStoreProfile->options($projectId),
            'description' => '注文・メニュー・お知らせに使う店舗です。変更するとアプリの表示が切り替わります。',
        ];
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
}

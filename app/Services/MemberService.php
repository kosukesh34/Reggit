<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Jobs\SendAfterRegistrationJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MemberService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private SmaregiCustomerPointsInboundSyncService $smaregiCustomerPointsInboundSyncService,
    ) {}

    public function getMemberData(User $user): array
    {
        $member = $user->member;

        if ($member === null) {
            $projectId = $user->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
            $profileSettings = ShopSetting::getProfileFieldSettings($projectId);
            $profileFormFields = $this->buildProfileFormFields($profileSettings);

            return [
                'is_member' => false,
                'message' => '会員登録がまだです',
                'show_member_card_for_guest' => ShopSetting::getShowMemberCardForGuest($projectId),
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
        $profileSettings = ShopSetting::getProfileFieldSettings($projectId);
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
            'show_points_display' => ShopSetting::getShowPointsDisplay($projectId),
            'show_rank_progress' => ShopSetting::getShowRankProgress($projectId),
            'member_card_design' => ShopSetting::getMemberCardDesign($projectId),
            'member_card_logo_url' => ShopSetting::getFrontendLogoUrl($projectId),
            'member_card_display_type' => ShopSetting::getMemberCardDisplayType($projectId),
            'member_card_code_type' => ShopSetting::getMemberCardCodeType($projectId),
        ];
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
        $settings = ShopSetting::getProfileFieldSettings($projectId);
        $rules = [];
        $stringMax = ['name' => 255, 'kana' => 255, 'phone' => 50, 'email' => 255, 'postal_code' => 16, 'prefecture' => 64, 'city' => 128, 'street' => 256, 'address' => 500, 'sex' => 16];
        foreach ($settings['smaregi_fields'] ?? [] as $key => $field) {
            if ((bool) ($field['enabled'] ?? false) === false) {
                continue;
            }
            $rule = ['nullable'];
            if ((bool) ($field['required'] ?? false) === true) {
                $rule = ['required'];
            }
            if ($key === 'email') {
                $rule[] = 'email';
            }
            if ($key === 'birthday') {
                $rule[] = 'date';
            }
            if (isset($stringMax[$key])) {
                $rule[] = 'string';
                $rule[] = 'max:'.$stringMax[$key];
            }
            $rules[$key] = $rule;
        }
        foreach ($settings['custom_fields'] ?? [] as $cf) {
            $key = $cf['key'] ?? null;
            if ($key === null) {
                continue;
            }
            $rules[$key] = (bool) ($cf['required'] ?? false) === true ? ['required', 'string', 'max:500'] : ['nullable', 'string', 'max:500'];
        }

        return $rules;
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
            ];
        }
        foreach ($profileSettings['custom_fields'] ?? [] as $cf) {
            $out[] = [
                'key' => $cf['key'] ?? '',
                'label' => $cf['label'] ?? '',
                'required' => (bool) ($cf['required'] ?? false) === true,
            ];
        }

        return $out;
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
        $settings = ShopSetting::getProfileFieldSettings($projectId);

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

        $memberKeys = ['birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex'];
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
        $settings = ShopSetting::getProfileFieldSettings($projectId);
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
        if ($user->member) {
            throw new \Exception('既に会員登録済みです');
        }

        $projectId = $user->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
        $memberNumber = $this->generateMemberNumber($projectId);
        $smaregiCustomerId = null;

        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId !== '') {
            $body = $this->buildSmaregiCustomerBody($memberNumber, $data);
            $result = $this->smaregiApi->createCustomer($contractId, $body);
            if ($result !== null && isset($result['customerId']) && (string) $result['customerId'] !== '') {
                $smaregiCustomerId = (string) $result['customerId'];
            } else {
                LogHelper::warning('[Member] smaregi create customer failed. fallback to local registration', [
                    'project_id' => $projectId,
                    'user_id' => (int) $user->id,
                    'member_number' => $memberNumber,
                ]);
            }
        }

        $rankSettings = ShopSetting::getMemberRankSettings($projectId);
        $defaultRankKey = collect($rankSettings)->first(fn ($r) => ($r['is_active'] ?? false) === true)['key'] ?? 'bronze';
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
            'name' => $data['name'] ?? null,
            'kana' => $data['kana'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        if ($userUpdates !== []) {
            $user->update($userUpdates);
        }

        return $member->load('user');
    }

    /** スマレジ会員登録用のリクエストボディを組み立て（会員コードは20文字以内） */
    private function buildSmaregiCustomerBody(string $memberNumber, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $kana = trim((string) ($data['kana'] ?? ''));
        $parts = $name !== '' ? preg_split('/\s+/u', $name, 2) : ['', ''];
        $lastName = $parts[0] ?? '';
        $firstName = $parts[1] ?? '';
        if ($lastName === '' && $name !== '') {
            $lastName = $name;
        }
        $kanaParts = $kana !== '' ? preg_split('/\s+/u', $kana, 2) : ['', ''];
        $lastKana = trim((string) ($kanaParts[0] ?? ''));
        $firstKana = trim((string) ($kanaParts[1] ?? ''));
        if ($lastKana === '' && $kana !== '') {
            $lastKana = trim($kana);
        }
        $kanaPlaceholder = 'ー'; // カタカナ長音（入力内容に誤りがありますを防ぐ）

        $body = [
            'customerCode' => Str::limit($memberNumber, 20, ''),
            'lastName' => $lastName !== '' ? $lastName : '—',
            'firstName' => $firstName !== '' ? $firstName : '—',
            'lastKana' => $lastKana !== '' ? $lastKana : $kanaPlaceholder,
            'firstKana' => $firstKana !== '' ? $firstKana : $kanaPlaceholder,
        ];
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '') {
            $body['mailAddress'] = $email;
        }
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '') {
            $body['phoneNumber'] = $phone;
        }

        return $body;
    }

    private function syncMemberRank(Member $member): void
    {
        $rankInfo = $this->getRankInfo($member);
        if (($rankInfo['current_rank'] ?? '') !== '' && $rankInfo['current_rank'] !== $member->rank) {
            $member->update(['rank' => $rankInfo['current_rank']]);
        }
    }

    private function getRankInfo(Member $member): array
    {
        $projectId = $member->project_id ?? AdminContext::getCurrentProjectId() ?? 1;
        $ranks = ShopSetting::getMemberRankSettings($projectId);
        $basis = ShopSetting::getRankBasis($projectId);

        $points = $member->getDisplayPoints();
        $stamps = $member->getTotalStamps();
        $metric = $basis === 'stamps' ? $stamps : ($basis === 'max' ? max($points, $stamps) : $points);

        $active = array_values(array_filter($ranks, fn ($r) => ($r['is_active'] ?? false) === true));
        if ($active === []) {
            return [
                'current_rank' => $member->rank ?? 'bronze',
                'current_rank_label' => $member->rank ?? 'bronze',
                'current_rank_icon' => null,
                'current_rank_background' => null,
                'current_rank_color' => null,
                'next_rank' => null,
                'next_rank_label' => null,
                'points_to_next_rank' => null,
                'rank_basis' => $basis,
                'rank_metric' => $metric,
            ];
        }

        usort($active, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));
        $resolved = $active[0];
        foreach ($active as $rank) {
            if ($metric >= ($rank['min_points'] ?? 0)) {
                $resolved = $rank;
            }
        }

        $next = null;
        foreach ($active as $rank) {
            if (($rank['min_points'] ?? 0) > ($resolved['min_points'] ?? 0)) {
                $next = $rank;
                break;
            }
        }

        $pointsToNext = null;
        if ($next !== null) {
            $pointsToNext = max(0, ($next['min_points'] ?? 0) - $metric);
        }

        return [
            'current_rank' => $resolved['key'] ?? 'bronze',
            'current_rank_label' => $resolved['label'] ?? ($resolved['key'] ?? 'bronze'),
            'current_rank_icon' => $resolved['icon_url'] ?? null,
            'current_rank_background' => $resolved['stamp_background_url'] ?? null,
            'current_rank_color' => $resolved['card_color'] ?? null,
            'next_rank' => $next !== null ? ($next['key'] ?? null) : null,
            'next_rank_label' => $next !== null ? ($next['label'] ?? ($next['key'] ?? null)) : null,
            'points_to_next_rank' => $pointsToNext,
            'rank_basis' => $basis,
            'rank_metric' => $metric,
        ];
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
        $type = ShopSetting::getMemberCodeType($projectId);
        $format = ShopSetting::getMemberCodeFormat($projectId);
        $length = max(1, min(20, ShopSetting::getMemberCodeLength($projectId)));
        $prefix = ShopSetting::getMemberCodePrefix($projectId);
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix);
        $prefixLen = strlen($prefix);
        $bodyLen = min($length, 20 - $prefixLen);
        if ($bodyLen < 1) {
            $bodyLen = 1;
        }

        $charsNumeric = '0123456789';
        $charsAlpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 英大文字+数字（紛らわしいI,O,1,0を除く）

        if ($type === 'sequential') {
            $next = ShopSetting::getMemberCodeNextNumber($projectId);
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
            ShopSetting::setMemberCodeNextNumber($next + 1, $projectId);
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

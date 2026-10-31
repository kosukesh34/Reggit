<?php

namespace App\Services\Member;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Models\Member;
use App\Models\MemberRegistrationAttempt;
use Illuminate\Validation\ValidationException;

/** 会員コード設定（連番・ランダム、接頭辞、桁数）に従って未使用の会員番号を払い出す。 */
class MemberNumberAssigner
{
    public function __construct(
        private MemberSettings $shopSettingRepository,
    ) {}

    public function next(int $projectId): string
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
            $reserved = MemberRegistrationAttempt::where('project_id', $projectId)->pluck('member_number');
            $maxExisting = Member::withoutGlobalScopes()->where('project_id', $projectId)
                ->whereNotNull('member_number')->where('member_number', '!=', '')
                ->pluck('member_number')->concat($reserved)
                ->map(function (string $num) use ($prefix): int {
                    if ($prefix !== '') {
                        if (! str_starts_with($num, $prefix)) {
                            return 0;
                        }
                        $num = substr($num, strlen($prefix));
                    }

                    return ctype_digit($num) ? (int) $num : 0;
                })->max();
            $next = max($next, ($maxExisting ?? 0) + 1);
            $body = sprintf('%0'.$bodyLen.'d', $next);
            if (strlen($body) > $bodyLen) {
                throw ValidationException::withMessages(['member_number' => '会員番号を発行できません。会員コードの桁数を増やしてください。']);
            }
            $this->shopSettingRepository->setMemberCodeNextNumber($next + 1, $projectId);
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
            $exists = Member::withoutGlobalScopes()->where('project_id', $projectId)->where('member_number', $candidate)->exists();
            if (! $exists && ! MemberRegistrationAttempt::where('project_id', $projectId)->where('member_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages(['member_number' => '未使用の会員番号を確保できません。再度お試しいただくか、会員コードの桁数を増やしてください。']);
    }
}

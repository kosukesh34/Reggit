<?php

namespace App\Services\Member;

use App\Models\MemberTransferCode;
use App\Models\User;
use App\Services\Member\Data\IssuedTransferCode;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** 会員の引き継ぎコードを発行する。未使用の旧コードは無効にする。 */
class MemberTransferCodeIssuer
{
    private const CODE_LENGTH = 8;

    private const EXPIRES_MINUTES = 30;

    public function issue(User $user, ?int $adminUserId): IssuedTransferCode
    {
        return DB::transaction(function () use ($user, $adminUserId): IssuedTransferCode {
            $projectId = (int) $user->project_id;
            $owner = User::query()->where('project_id', $projectId)->whereKey($user->id)->lockForUpdate()->first();
            if ($owner === null || ($adminUserId === null
                && (int) $owner->authentication_version !== (int) $user->authentication_version)) {
                throw new InvalidArgumentException('会員を確認できません。ログインし直してください');
            }
            $member = $owner->member()->where('project_id', $projectId)->lockForUpdate()->first();
            if ($member === null) {
                throw new InvalidArgumentException('会員ではないため引き継ぎコードを発行できません');
            }

            $plain = $this->generateCode();
            $expiresAt = now()->addMinutes(self::EXPIRES_MINUTES);
            MemberTransferCode::query()
                ->where('project_id', $projectId)->where('user_id', $owner->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);
            MemberTransferCode::query()->create([
                'project_id' => (int) $owner->project_id,
                'user_id' => (int) $owner->id,
                'issued_by_admin_user_id' => $adminUserId,
                'code_hash' => hash('sha256', $plain),
                'expires_at' => $expiresAt,
            ]);

            LogHelper::info('[Member] transfer code issued', [
                'project_id' => AdminContext::getCurrentProjectId() ?? (int) $owner->project_id,
                'user_id' => $owner->id,
                'admin_user_id' => $adminUserId,
            ]);

            return new IssuedTransferCode($plain, $expiresAt->format('Y-m-d H:i'), (int) $member->id);
        }, 3);
    }

    private function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($index = 0; $index < self::CODE_LENGTH; $index++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}

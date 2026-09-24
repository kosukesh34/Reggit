<?php

namespace App\UseCases\Member;

use App\Models\MemberTransferCode;
use App\Models\User;
use App\UseCases\Member\Data\IssuedTransferCode;
use App\Util\AdminContext;
use App\Util\LogHelper;
use InvalidArgumentException;

class IssueMemberTransferCodeUseCase
{
    private const CODE_LENGTH = 8;

    private const EXPIRES_MINUTES = 30;

    public function handle(User $user, ?int $adminUserId): IssuedTransferCode
    {
        $member = $user->member;
        if ($member === null) {
            throw new InvalidArgumentException('会員ではないため引き継ぎコードを発行できません');
        }

        $plain = $this->generateCode();
        $expiresAt = now()->addMinutes(self::EXPIRES_MINUTES);
        MemberTransferCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
        MemberTransferCode::query()->create([
            'project_id' => (int) $user->project_id,
            'user_id' => (int) $user->id,
            'issued_by_admin_user_id' => $adminUserId,
            'code_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
        ]);

        LogHelper::info('[Member] transfer code issued', [
            'project_id' => AdminContext::getCurrentProjectId() ?? (int) $user->project_id,
            'user_id' => $user->id,
            'admin_user_id' => $adminUserId,
        ]);

        return new IssuedTransferCode($plain, $expiresAt->format('Y-m-d H:i'), (int) $member->id);
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

<?php

namespace App\UseCases\Member;

use App\Models\MemberTransferCode;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\MemberChannel;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClaimMemberTransferCodeUseCase
{
    public function handle(User $currentUser, ?UserDevice $device, string $code, int $memberId): User
    {
        $normalized = strtoupper(str_replace([' ', '-'], '', trim($code)));
        if ($normalized === '' || $memberId <= 0) {
            throw new InvalidArgumentException('会員IDと引き継ぎコードを入力してください');
        }

        return DB::transaction(function () use ($currentUser, $device, $normalized, $memberId): User {
            $row = MemberTransferCode::query()
                ->where('code_hash', hash('sha256', $normalized))
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->first();
            if ($row === null || $row->expires_at->lt(now())) {
                throw new InvalidArgumentException('引き継ぎコードが無効か、期限切れです');
            }

            $source = User::query()->whereKey($row->user_id)->first();
            if ($source === null || $source->member === null || (int) $source->member->id !== $memberId) {
                throw new InvalidArgumentException('会員IDか引き継ぎコードが違います');
            }
            if ((int) $currentUser->id !== (int) $source->id && $currentUser->member !== null) {
                throw new InvalidArgumentException('このアカウントは別の会員です。退会してから引き継いでください');
            }

            $this->moveIdentity($currentUser, $source, $device);
            $row->forceFill(['consumed_at' => now()])->save();

            LogHelper::info('[Member] transfer code claimed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? (int) $source->project_id,
                'user_id' => $source->id,
            ]);

            return $source;
        });
    }

    private function moveIdentity(User $currentUser, User $source, ?UserDevice $device): void
    {
        if ((int) $currentUser->id === (int) $source->id) {
            return;
        }

        $lineUserId = is_string($currentUser->line_user_id) ? $currentUser->line_user_id : '';
        if (MemberChannel::isValidLineUserId($lineUserId)) {
            $currentUser->line_user_id = 'guest-moved-'.$currentUser->id;
            $currentUser->save();
            $source->line_user_id = $lineUserId;
            $source->save();
        }

        if ($device !== null) {
            $device->user_id = (int) $source->id;
            $device->save();
            UserDevice::query()
                ->where('user_id', $source->id)
                ->whereKeyNot($device->id)
                ->delete();
        }

        if ($currentUser->member === null) {
            $currentUser->delete();
        }
    }
}

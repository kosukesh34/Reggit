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

        $projectId = AdminContext::requireCurrentProjectId();
        if ((int) $currentUser->project_id !== $projectId) {
            throw new InvalidArgumentException('引き継ぎコードが無効か、期限切れです');
        }

        return DB::transaction(function () use ($currentUser, $device, $normalized, $memberId, $projectId): User {
            $snapshot = MemberTransferCode::query()
                ->where('project_id', $projectId)
                ->where('code_hash', hash('sha256', $normalized))
                ->whereNull('consumed_at')->first();
            if ($snapshot === null) {
                throw new InvalidArgumentException('引き継ぎコードが無効か、期限切れです');
            }

            $users = User::query()->where('project_id', $projectId)
                ->whereIn('id', [$currentUser->id, $snapshot->user_id])->orderBy('id')
                ->lockForUpdate()->get()->keyBy('id');
            $actor = $users->get((int) $currentUser->id);
            $source = $users->get((int) $snapshot->user_id);
            if ($actor === null || $source === null
                || (int) $actor->authentication_version !== (int) $currentUser->authentication_version) {
                throw new InvalidArgumentException('引き継ぎコードが無効か、期限切れです');
            }

            $row = MemberTransferCode::query()->where('project_id', $projectId)
                ->whereKey($snapshot->id)->where('user_id', $source->id)
                ->where('code_hash', hash('sha256', $normalized))->whereNull('consumed_at')
                ->lockForUpdate()->first();
            if ($row === null || $row->expires_at->lt(now())) {
                throw new InvalidArgumentException('引き継ぎコードが無効か、期限切れです');
            }

            $member = $source->member()->where('project_id', $projectId)->lockForUpdate()->first();
            if ($member === null || (int) $member->id !== $memberId) {
                throw new InvalidArgumentException('会員IDか引き継ぎコードが違います');
            }
            $source->setRelation('member', $member);
            $actorMember = (int) $actor->id === (int) $source->id
                ? $member
                : $actor->member()->where('project_id', $projectId)->lockForUpdate()->first();
            if ((int) $actor->id !== (int) $source->id && $actorMember !== null) {
                throw new InvalidArgumentException('このアカウントは別の会員です。退会してから引き継いでください');
            }
            $actor->setRelation('member', $actorMember);

            $lockedDevice = null;
            if ($device !== null) {
                $lockedDevice = UserDevice::query()->where('project_id', $projectId)->whereKey($device->id)
                    ->where('user_id', $actor->id)->lockForUpdate()->first();
                if ($lockedDevice === null || ! $lockedDevice->authenticates($device->auth_token)) {
                    throw new InvalidArgumentException('端末を確認できません。アプリを開き直してください');
                }
            }

            $this->moveIdentity($actor, $source, $lockedDevice);
            $row->forceFill(['consumed_at' => now()])->save();

            LogHelper::info('[Member] transfer code claimed', [
                'project_id' => AdminContext::getCurrentProjectId() ?? (int) $source->project_id,
                'user_id' => $source->id,
            ]);

            return $source;
        }, 3);
    }

    private function moveIdentity(User $currentUser, User $source, ?UserDevice $device): void
    {
        $sameUser = (int) $currentUser->id === (int) $source->id;
        if ($sameUser && $device === null) {
            return;
        }

        $source->increment('authentication_version');

        $lineUserId = is_string($currentUser->line_user_id) ? $currentUser->line_user_id : '';
        if (! $sameUser && MemberChannel::isValidLineUserId($lineUserId)) {
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

        if (! $sameUser && $currentUser->member === null) {
            $currentUser->delete();
        }
    }
}

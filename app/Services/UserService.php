<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class UserService
{
    private const SESSION_USER_ID_KEY_PREFIX = 'mobile_order_user_id';

    public function findUser(Request $request): ?User
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $lineUserId = $this->normalizeLineUserId($request->header('X-Line-User-Id'));
        $deviceIdentifier = $this->normalizeDeviceIdentifier($request->header('X-Device-Identifier'));

        if ($lineUserId !== null) {
            $user = $this->findUserByLineId($lineUserId, $projectId);
            if ($user !== null) {
                return $user;
            }
        }

        if ($deviceIdentifier !== null) {
            $device = UserDevice::where('project_id', $projectId)
                ->where('device_identifier', $deviceIdentifier)
                ->orderByDesc('last_used_at')
                ->first();
            if ($device !== null) {
                $deviceUser = User::where('id', $device->user_id)->where('project_id', $projectId)->first();
                if ($deviceUser !== null) {
                    if ($lineUserId !== null) {
                        return $this->resolveStoredUserForLineId($deviceUser, $lineUserId, $projectId);
                    }
                    Session::put($this->getSessionKey($projectId), $deviceUser->id);
                    $device->forceFill(['last_used_at' => now()])->save();

                    return $deviceUser;
                }
            }
        }

        $user = $request->user();
        if ($user !== null && (int) $user->project_id === $projectId) {
            Session::put($this->getSessionKey($projectId), $user->id);

            return $user;
        }

        $sessionUser = $this->findSessionUser($projectId);
        if ($sessionUser === null) {
            return null;
        }

        if ($lineUserId !== null) {
            return $this->resolveStoredUserForLineId($sessionUser, $lineUserId, $projectId);
        }

        Session::put($this->getSessionKey($projectId), $sessionUser->id);

        return $sessionUser;
    }

    public function getOrCreateUser(Request $request): User
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $lineUserId = $this->normalizeLineUserId($request->header('X-Line-User-Id'));

        if ($lineUserId !== null) {
            $byLine = $this->findUserByLineId($lineUserId, $projectId);
            if ($byLine !== null) {
                return $byLine;
            }
        }

        $existing = $this->findUser($request);
        if ($existing !== null) {
            if ($lineUserId !== null) {
                $this->linkLineUserIdIfPossible($existing, $lineUserId, $projectId);
            }

            return $existing;
        }

        // 退会済み（ソフトデリート）の同一 LINE ユーザーがいれば復元する。前回の会員（Member）は復元せず、再登録時に新規 Member になる。
        if ($lineUserId !== null) {
            $trashed = User::withTrashed()
                ->where('project_id', $projectId)
                ->where('line_user_id', $lineUserId)
                ->first();
            if ($trashed !== null && $trashed->trashed()) {
                $trashed->restore();
                Session::put($this->getSessionKey($projectId), $trashed->id);

                return $trashed;
            }
        }

        if ($lineUserId !== null) {
            $user = $this->createGuestUser($lineUserId, $projectId);
            Session::put($this->getSessionKey($projectId), $user->id);

            return $user;
        }

        $sessionUser = $this->findSessionUser($projectId);
        if ($sessionUser !== null) {
            return $sessionUser;
        }

        $user = $this->createGuestUser('guest-'.uniqid(), $projectId);
        Session::put($this->getSessionKey($projectId), $user->id);

        return $user;
    }

    private function findSessionUser(int $projectId): ?User
    {
        $sessionUserId = Session::get($this->getSessionKey($projectId));
        if ($sessionUserId === null) {
            return null;
        }

        return User::where('id', $sessionUserId)->where('project_id', $projectId)->first();
    }

    private function isPlaceholderLineUserId(?string $lineUserId): bool
    {
        if ($lineUserId === null || $lineUserId === '') {
            return true;
        }

        return str_starts_with($lineUserId, 'guest-') || ! $this->isValidLineUserId($lineUserId);
    }

    private function createGuestUser(string $lineUserId, int $projectId): User
    {
        return User::create([
            'project_id' => $projectId,
            'line_user_id' => $lineUserId,
            'name' => 'Guest User',
        ]);
    }

    private function getSessionKey(int $projectId): string
    {
        return self::SESSION_USER_ID_KEY_PREFIX.'_'.$projectId;
    }

    /** 現在プロジェクトのユーザーセッションを削除（退会時など） */
    public function clearUserSession(Request $request): void
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        Session::forget($this->getSessionKey($projectId));
    }

    private function normalizeLineUserId(?string $lineUserId): ?string
    {
        if (! is_string($lineUserId)) {
            return null;
        }
        $lineUserId = trim($lineUserId);
        if ($lineUserId === '' || ! $this->isValidLineUserId($lineUserId)) {
            return null;
        }

        return $lineUserId;
    }

    private function normalizeDeviceIdentifier(?string $deviceIdentifier): ?string
    {
        if (! is_string($deviceIdentifier)) {
            return null;
        }
        $deviceIdentifier = trim($deviceIdentifier);

        return $deviceIdentifier === '' ? null : $deviceIdentifier;
    }

    private function isValidLineUserId(string $lineUserId): bool
    {
        return preg_match('/^U[0-9a-f]{32}$/i', $lineUserId) === 1;
    }

    private function linkLineUserIdIfPossible(User $user, string $lineUserId, int $projectId): void
    {
        if ($user->line_user_id === $lineUserId) {
            return;
        }

        $exists = User::where('project_id', $projectId)
            ->where('line_user_id', $lineUserId)
            ->where('id', '!=', $user->id)
            ->exists();
        if ($exists) {
            return;
        }

        $user->line_user_id = $lineUserId;
        $user->save();
    }

    private function findUserByLineId(string $lineUserId, int $projectId): ?User
    {
        $user = User::where('line_user_id', $lineUserId)->where('project_id', $projectId)->first();
        if ($user === null) {
            return null;
        }

        Session::put($this->getSessionKey($projectId), $user->id);

        return $user;
    }

    private function resolveStoredUserForLineId(User $user, string $lineUserId, int $projectId): ?User
    {
        if ($this->isPlaceholderLineUserId($user->line_user_id)) {
            $this->linkLineUserIdIfPossible($user, $lineUserId, $projectId);
            $user->refresh();

            if ($user->line_user_id === $lineUserId) {
                Session::put($this->getSessionKey($projectId), $user->id);

                return $user;
            }

            $lineOwner = $this->findUserByLineId($lineUserId, $projectId);
            if ($lineOwner !== null) {
                return $lineOwner;
            }

            Session::put($this->getSessionKey($projectId), $user->id);

            return $user;
        }

        if ($user->line_user_id !== $lineUserId) {
            return $this->findUserByLineId($lineUserId, $projectId);
        }

        Session::put($this->getSessionKey($projectId), $user->id);

        return $user;
    }
}

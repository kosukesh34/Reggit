<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserDeviceRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\Line\LineIdTokenVerifier;
use App\Support\LineIdentityPolicy;
use App\Support\MemberChannel;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class UserService
{
    private const SESSION_USER_ID_KEY_PREFIX = 'mobile_order_user_id';

    public function __construct(
        private UserDeviceRepository $userDeviceRepository,
        private UserRepository $userRepository,
        private LineIdTokenVerifier $lineIdTokenVerifier,
    ) {}

    public function findUser(Request $request): ?User
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $lineUserId = $this->resolveVerifiedLineUserId($request);
        $deviceIdentifier = $this->normalizeDeviceIdentifier($request->header('X-Device-Identifier'));

        if ($lineUserId !== null) {
            $user = $this->findActiveUserByLineId($lineUserId, $projectId);
            if ($user !== null) {
                Session::put($this->getSessionKey($projectId), $user->id);
                $this->logResolution('line', $user, $lineUserId, $deviceIdentifier);

                return $user;
            }
        }

        if ($deviceIdentifier !== null) {
            $device = $this->userDeviceRepository->findLatestByProjectAndIdentifier($projectId, $deviceIdentifier);
            if ($device !== null && $device->authenticates($request->header('X-Device-Token'))) {
                $deviceUser = $this->userRepository->findByIdAndProject((int) $device->user_id, $projectId);
                if ($deviceUser !== null) {
                    if ($lineUserId !== null) {
                        $resolved = $this->resolveStoredUserForLineId($deviceUser, $lineUserId, $projectId);
                        $this->logResolution($resolved ? 'device_line_resolved' : 'device_line_unresolved', $resolved, $lineUserId, $deviceIdentifier);

                        return $resolved;
                    }
                    Session::put($this->getSessionKey($projectId), $deviceUser->id);
                    $this->userDeviceRepository->touchLastUsedAt($device);
                    $this->logResolution('device', $deviceUser, $lineUserId, $deviceIdentifier);

                    return $deviceUser;
                }
            }
        }

        $user = $request->user();
        if ($user !== null && (int) $user->project_id === $projectId) {
            Session::put($this->getSessionKey($projectId), $user->id);
            $this->logResolution('auth_user', $user, $lineUserId, $deviceIdentifier);

            return $user;
        }

        $sessionUser = $this->findSessionUser($projectId);
        if ($sessionUser === null) {
            $this->logResolution('none', null, $lineUserId, $deviceIdentifier);

            return null;
        }

        if ($lineUserId !== null) {
            $resolved = $this->resolveStoredUserForLineId($sessionUser, $lineUserId, $projectId);
            $this->logResolution($resolved ? 'session_line_resolved' : 'session_line_unresolved', $resolved, $lineUserId, $deviceIdentifier);

            return $resolved;
        }

        Session::put($this->getSessionKey($projectId), $sessionUser->id);
        $this->logResolution('session', $sessionUser, $lineUserId, $deviceIdentifier);

        return $sessionUser;
    }

    public function getOrCreateUser(Request $request): User
    {
        $projectId = AdminContext::requireCurrentProjectId();
        $lineUserId = $this->resolveVerifiedLineUserId($request);
        $deviceIdentifier = $this->normalizeDeviceIdentifier($request->header('X-Device-Identifier'));

        if ($lineUserId !== null) {
            $byLine = $this->findActiveUserByLineId($lineUserId, $projectId);
            if ($byLine !== null) {
                Session::put($this->getSessionKey($projectId), $byLine->id);
                $this->logResolution('get_or_create_line', $byLine, $lineUserId, $deviceIdentifier);

                return $byLine;
            }
        }

        $existing = $this->findUser($request);
        if ($existing !== null) {
            if ($lineUserId !== null) {
                $this->releaseLineUserIdHeldByWithdrawnUsers($lineUserId, $projectId);
                $this->linkLineUserIdIfPossible($existing, $lineUserId, $projectId);
            }
            Session::put($this->getSessionKey($projectId), $existing->id);
            $this->logResolution('get_or_create_existing', $existing, $lineUserId, $deviceIdentifier);

            return $existing;
        }

        if ($lineUserId !== null) {
            $this->releaseLineUserIdHeldByWithdrawnUsers($lineUserId, $projectId);
            $user = $this->createGuestUser($lineUserId, $projectId);
            Session::put($this->getSessionKey($projectId), $user->id);
            $this->logResolution('get_or_create_new_line', $user, $lineUserId, $deviceIdentifier);

            return $user;
        }

        $sessionUser = $this->findSessionUser($projectId);
        if ($sessionUser !== null) {
            $this->logResolution('get_or_create_session', $sessionUser, $lineUserId, $deviceIdentifier);

            return $sessionUser;
        }

        $user = $this->createGuestUser('guest-'.uniqid(), $projectId);
        Session::put($this->getSessionKey($projectId), $user->id);
        $this->logResolution('get_or_create_guest', $user, $lineUserId, $deviceIdentifier);

        return $user;
    }

    private function logResolution(string $route, ?User $user, ?string $lineUserId, ?string $deviceIdentifier): void
    {
        LogHelper::info('[UserService] user resolved', [
            'route' => $route,
            'user_id' => $user?->id,
            'line_user_tail' => $lineUserId !== null ? substr($lineUserId, -4) : null,
            'device_prefix' => $deviceIdentifier !== null ? substr($deviceIdentifier, 0, 8) : null,
            'resolved_line_tail' => ($user !== null && is_string($user->line_user_id))
                ? substr($user->line_user_id, -4)
                : null,
        ]);
    }

    private function findSessionUser(int $projectId): ?User
    {
        $sessionUserId = Session::get($this->getSessionKey($projectId));
        if ($sessionUserId === null) {
            return null;
        }

        return $this->userRepository->findByIdAndProject((int) $sessionUserId, $projectId);
    }

    private function isPlaceholderLineUserId(?string $lineUserId): bool
    {
        if ($lineUserId === null || $lineUserId === '') {
            return true;
        }

        return str_starts_with($lineUserId, 'guest-') || ! MemberChannel::isValidLineUserId($lineUserId);
    }

    private function createGuestUser(string $lineUserId, int $projectId): User
    {
        return User::create([
            'project_id' => $projectId,
            'line_user_id' => $lineUserId,
        ]);
    }

    private function getSessionKey(int $projectId): string
    {
        return self::SESSION_USER_ID_KEY_PREFIX.'_'.$projectId;
    }

    /**
     * 退会時に LINE ID を退会済みユーザーから切り離す（復元はしない）。
     * 同一 LINE で再ログインしたときに新規 users 行を作れるようにする。
     */
    public function releaseLineUserIdForWithdraw(User $user): void
    {
        $lineUserId = is_string($user->line_user_id) ? trim($user->line_user_id) : '';
        if ($lineUserId === '' || str_starts_with($lineUserId, 'guest-') || str_starts_with($lineUserId, 'withdrawn-')) {
            return;
        }

        $user->line_user_id = 'withdrawn-'.$user->id;
        $user->save();
    }

    /** 現在プロジェクトのユーザーセッションを削除（退会時など） */
    public function clearUserSession(Request $request): void
    {
        $projectId = AdminContext::requireCurrentProjectId();
        Session::forget($this->getSessionKey($projectId));
    }

    private function resolveVerifiedLineUserId(Request $request): ?string
    {
        $headerLineUserId = $this->normalizeLineUserId($request->header('X-Line-User-Id'));
        if (! LineIdentityPolicy::requiresVerifiedToken()) {
            return $headerLineUserId;
        }

        $idToken = trim((string) $request->header('X-Line-Id-Token', ''));
        if ($idToken === '') {
            return null;
        }

        return $this->lineIdTokenVerifier->verify($idToken, AdminContext::requireCurrentProjectId());
    }

    private function normalizeLineUserId(?string $lineUserId): ?string
    {
        if (! is_string($lineUserId)) {
            return null;
        }
        $lineUserId = trim($lineUserId);
        if ($lineUserId === '' || ! MemberChannel::isValidLineUserId($lineUserId)) {
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

    private function linkLineUserIdIfPossible(User $user, string $lineUserId, int $projectId): void
    {
        if ($user->line_user_id === $lineUserId) {
            return;
        }

        $conflict = User::withTrashed()
            ->where('project_id', $projectId)
            ->where('line_user_id', $lineUserId)
            ->where('id', '!=', $user->id)
            ->exists();
        if ($conflict) {
            return;
        }

        $user->line_user_id = $lineUserId;
        $user->save();
    }

    /** 退会済み行が保持している LINE ID を解放する（users.restore は行わない）。 */
    private function releaseLineUserIdHeldByWithdrawnUsers(string $lineUserId, int $projectId): void
    {
        User::onlyTrashed()
            ->where('project_id', $projectId)
            ->where('line_user_id', $lineUserId)
            ->each(function (User $withdrawn) {
                $this->releaseLineUserIdForWithdraw($withdrawn);
            });
    }

    private function findActiveUserByLineId(string $lineUserId, int $projectId): ?User
    {
        $user = $this->userRepository->findByLineUserIdAndProject($lineUserId, $projectId);
        if ($user === null) {
            return null;
        }

        Session::put($this->getSessionKey($projectId), $user->id);

        return $user;
    }

    private function resolveStoredUserForLineId(User $user, string $lineUserId, int $projectId): ?User
    {
        $this->releaseLineUserIdHeldByWithdrawnUsers($lineUserId, $projectId);

        $lineOwner = $this->findActiveUserByLineId($lineUserId, $projectId);
        if ($lineOwner !== null && $lineOwner->id !== $user->id) {
            return $lineOwner;
        }

        if ($this->isPlaceholderLineUserId($user->line_user_id)) {
            $this->linkLineUserIdIfPossible($user, $lineUserId, $projectId);
            $user->refresh();

            if ($user->line_user_id === $lineUserId) {
                Session::put($this->getSessionKey($projectId), $user->id);

                return $user;
            }

            $lineOwner = $this->findActiveUserByLineId($lineUserId, $projectId);
            if ($lineOwner !== null) {
                return $lineOwner;
            }

            Session::put($this->getSessionKey($projectId), $user->id);

            return $user;
        }

        if ($user->line_user_id !== $lineUserId) {
            return $this->findActiveUserByLineId($lineUserId, $projectId);
        }

        Session::put($this->getSessionKey($projectId), $user->id);

        return $user;
    }
}

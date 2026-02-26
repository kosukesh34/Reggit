<?php

namespace App\Services;

use App\Models\User;
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

        if ($lineUserId !== null) {
            $user = User::where('line_user_id', $lineUserId)->where('project_id', $projectId)->first();
            if ($user !== null) {
                Session::put($this->getSessionKey($projectId), $user->id);
                return $user;
            }
        }

        $user = $request->user();
        if ($user !== null && (int) $user->project_id === $projectId) {
            Session::put($this->getSessionKey($projectId), $user->id);
            return $user;
        }

        $sessionUserId = Session::get($this->getSessionKey($projectId));
        if ($sessionUserId !== null) {
            $sessionUser = User::where('id', $sessionUserId)->where('project_id', $projectId)->first();
            if ($sessionUser !== null) {
                $rawLineId = $this->normalizeLineUserId($request->header('X-Line-User-Id'));
                $requestLineId = is_string($request->header('X-Line-User-Id')) ? trim($request->header('X-Line-User-Id')) : '';
                if ($requestLineId !== '' && $sessionUser->line_user_id !== $requestLineId && $rawLineId !== $sessionUser->line_user_id) {
                    return null;
                }
                if ($rawLineId !== null) {
                    $this->linkLineUserIdIfPossible($sessionUser, $rawLineId, $projectId);
                }
                return $sessionUser;
            }
        }

        return null;
    }

    public function getOrCreateUser(Request $request): User
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $lineUserId = $this->normalizeLineUserId($request->header('X-Line-User-Id'));
        $existing = $this->findUser($request);
        if ($existing !== null) {
            if ($lineUserId !== null) {
                $this->linkLineUserIdIfPossible($existing, $lineUserId, $projectId);
            }
            return $existing;
        }

        if ($lineUserId !== null) {
            $user = $this->createGuestUser($lineUserId, $projectId);
            Session::put($this->getSessionKey($projectId), $user->id);
            return $user;
        }

        $user = $this->createGuestUser('guest-' . uniqid(), $projectId);
        Session::put($this->getSessionKey($projectId), $user->id);
        return $user;
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
        return self::SESSION_USER_ID_KEY_PREFIX . '_' . $projectId;
    }

    private function normalizeLineUserId(?string $lineUserId): ?string
    {
        if (!is_string($lineUserId)) {
            return null;
        }
        $lineUserId = trim($lineUserId);
        if ($lineUserId === '' || !$this->isValidLineUserId($lineUserId)) {
            return null;
        }
        return $lineUserId;
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
}

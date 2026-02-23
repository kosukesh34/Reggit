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
        $lineUserId = $request->header('X-Line-User-Id');

        if ($lineUserId !== null && $lineUserId !== '') {
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
            return User::where('id', $sessionUserId)->where('project_id', $projectId)->first();
        }

        return null;
    }

    public function getOrCreateUser(Request $request): User
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;
        $lineUserId = $request->header('X-Line-User-Id');
        $existing = $this->findUser($request);
        if ($existing !== null) {
            return $existing;
        }

        if ($lineUserId !== null && $lineUserId !== '') {
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
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class UserService
{
    public function getOrCreateUser(Request $request): User
    {
        $userId = $request->header('X-Line-User-Id');
        
        if ($userId) {
            $user = User::where('line_user_id', $userId)->first();
            if ($user) {
                return $user;
            }
            return $this->createGuestUser($userId);
        }

        $user = $request->user();
        if ($user) {
            return $user;
        }

        return $this->createGuestUser('guest-' . uniqid());
    }

    private function createGuestUser(string $lineUserId): User
    {
        return User::create([
            'line_user_id' => $lineUserId,
            'name' => 'Guest User',
        ]);
    }
}


<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class UserService
{
    private const SESSION_USER_ID_KEY = 'mobile_order_user_id';

    public function getOrCreateUser(Request $request): User
    {
        $lineUserId = $request->header('X-Line-User-Id');
        
        if ($lineUserId !== null && $lineUserId !== '') {
            $user = User::where('line_user_id', $lineUserId)->first();
            if ($user !== null) {
                Session::put(self::SESSION_USER_ID_KEY, $user->id);
                return $user;
            }
            $user = $this->createGuestUser($lineUserId);
            Session::put(self::SESSION_USER_ID_KEY, $user->id);
            return $user;
        }

        $user = $request->user();
        if ($user !== null) {
            Session::put(self::SESSION_USER_ID_KEY, $user->id);
            return $user;
        }

        $sessionUserId = Session::get(self::SESSION_USER_ID_KEY);
        if ($sessionUserId !== null) {
            $user = User::find($sessionUserId);
            if ($user !== null) {
                return $user;
            }
        }

        $user = $this->createGuestUser('guest-' . uniqid());
        Session::put(self::SESSION_USER_ID_KEY, $user->id);
        return $user;
    }

    private function createGuestUser(string $lineUserId): User
    {
        return User::create([
            'line_user_id' => $lineUserId,
            'name' => 'Guest User',
        ]);
    }
}


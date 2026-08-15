<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;

class LiffBroadcastAuthController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function __invoke(Request $request)
    {
        $user = $this->userService->getOrCreateUser($request);
        if ($user->member === null) {
            return response()->json(['message' => '会員登録が必要です'], 403);
        }

        Auth::guard('web')->setUser($user);
        $request->setUserResolver(static fn () => $user);

        return Broadcast::auth($request);
    }
}

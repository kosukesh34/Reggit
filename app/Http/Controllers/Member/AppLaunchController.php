<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LIFF の起動を記録する（ネイティブアプリは端末登録で記録する）。
 */
class AppLaunchController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->userService->findUser($request)?->forceFill(['last_app_launched_at' => now()])->save();

        return response()->json(['success' => true]);
    }
}

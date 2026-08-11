<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LIFF/ネイティブのセッション解除（会員データは残す）。
 */
class MemberSessionClearController extends Controller
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->userService->clearUserSession($request);

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました',
        ]);
    }
}

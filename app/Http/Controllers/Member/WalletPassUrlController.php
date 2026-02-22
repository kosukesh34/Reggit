<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WalletPassUrlController extends Controller
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';
    private const TOKEN_TTL_MINUTES = 3;

    /**
     * Safari で開く用のワンタイム URL を返す（LINE アプリ内では .pkpass が開けないため）
     */
    public function __invoke(Request $request)
    {
        if (!config('wallet_pass.enabled')) {
            return response()->json(['error' => 'Wallet Pass は無効です'], Response::HTTP_FORBIDDEN);
        }

        $userService = app(UserService::class);
        $user = $userService->getOrCreateUser($request);
        $memberData = app(\App\Services\MemberService::class)->getMemberData($user);
        if (empty($memberData['is_member'])) {
            return response()->json(['error' => '会員登録が必要です'], Response::HTTP_FORBIDDEN);
        }

        $project = AdminContext::getCurrentProject();
        if (!$project) {
            return response()->json(['error' => 'プロジェクトが特定できません'], Response::HTTP_BAD_REQUEST);
        }

        $token = Str::random(64);
        $key = self::TOKEN_CACHE_PREFIX . $token;
        Cache::put($key, [
            'user_id' => $user->id,
            'project_id' => $project->id,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        $url = url('/api/members/me/wallet-pass?' . http_build_query([
            'project_key' => $project->project_key,
            'token' => $token,
        ]));

        return response()->json(['url' => $url]);
    }
}

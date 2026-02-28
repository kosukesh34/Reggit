<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\GoogleWalletPassService;
use App\Services\MemberService;
use App\Services\UserService;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Google Wallet に追加するための URL（JWT 付き）を返す。
 * セッション（会員）またはワンタイム token で認証。
 */
class GoogleWalletUrlController extends Controller
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';

    public function __construct(
        private GoogleWalletPassService $googleWalletPassService,
        private UserService $userService,
        private MemberService $memberService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $token = $request->query('token');
        $projectKey = $request->query('project_key');

        Log::info('google-wallet-url request', [
            'has_token' => $token !== null && $token !== '',
            'project_key' => $projectKey,
        ]);

        if (! $this->googleWalletPassService->isAvailable()) {
            Log::warning('google-wallet-url unavailable', [
                'reasons' => $this->googleWalletPassService->getUnavailabilityReasons(),
            ]);

            return response()->json([
                'error' => 'Google Wallet は利用できません。',
                'reasons' => $this->googleWalletPassService->getUnavailabilityReasons(),
            ], Response::HTTP_FORBIDDEN);
        }

        $url = null;

        if ($token !== null && $token !== '') {
            $url = $this->resolveUrlByToken($token);
            if ($url === null) {
                Log::warning('google-wallet-url resolveUrlByToken returned null', ['project_key' => $projectKey]);
            }
        } else {
            $user = $this->userService->getOrCreateUser($request);
            $memberData = $this->memberService->getMemberData($user);
            if (! isset($memberData['is_member']) || $memberData['is_member'] === false) {
                Log::info('google-wallet-url not a member', ['user_id' => $user->id ?? null]);
                return response()->json(['error' => '会員登録が必要です'], Response::HTTP_FORBIDDEN);
            }
            $url = $this->googleWalletPassService->createSaveUrlForUser($user);
        }

        if ($url === null) {
            Log::warning('google-wallet-url url is null');
            return response()->json(['error' => 'URL を発行できませんでした'], Response::HTTP_BAD_REQUEST);
        }

        Log::info('google-wallet-url success', ['project_key' => $projectKey]);
        return response()->json(['url' => $url]);
    }

    private function resolveUrlByToken(string $token): ?string
    {
        $key = self::TOKEN_CACHE_PREFIX . $token;
        $data = Cache::get($key);
        if ($data === null || ! isset($data['user_id'], $data['project_id'])) {
            Log::info('google-wallet-url token cache miss or invalid', ['has_data' => $data !== null]);
            return null;
        }
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || (int) $data['project_id'] !== (int) $projectId) {
            Log::info('google-wallet-url project mismatch', [
                'token_project_id' => $data['project_id'] ?? null,
                'context_project_id' => $projectId,
            ]);
            return null;
        }
        // トークンは消費しない（Apple / Google 両方で同じトークンを使う）

        return $this->googleWalletPassService->createSaveUrlForUserId((int) $data['user_id']);
    }
}

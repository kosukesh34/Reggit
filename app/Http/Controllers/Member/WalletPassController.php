<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\WalletPassService;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WalletPassController extends Controller
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';
    private const TOKEN_TTL_MINUTES = 3;

    /**
     * Apple Wallet 用パス（.pkpass）をダウンロード
     * 会員証画面・直接URL・どこからでも同じURLで .pkpass を返す
     * ?token=xxx がある場合はワンタイムトークンでユーザーを特定（LINE から Safari で開く用）
     */
    public function __invoke(Request $request)
    {
        $walletPassService = app(WalletPassService::class);
        $pkpassContent = null;

        $token = $request->query('token');
        if ($token !== null && $token !== '') {
            $pkpassContent = $this->generateWithToken($walletPassService, $token);
        } else {
            $pkpassContent = $walletPassService->generate($request);
        }

        if ($pkpassContent === null) {
            $reasons = [];
            if (!config('wallet_pass.enabled', false)) {
                $reasons[] = 'WALLET_PASS_ENABLED is not true in .env';
            }
            if (empty(config('wallet_pass.team_id'))) {
                $reasons[] = 'WALLET_PASS_TEAM_ID is not set in .env';
            }
            if (empty(config('wallet_pass.type_id'))) {
                $reasons[] = 'WALLET_PASS_TYPE_ID is not set in .env';
            }
            $certPath = $this->resolvePath(config('wallet_pass.cert_path', ''));
            $keyPath = $this->resolvePath(config('wallet_pass.key_path', ''));
            $wwdrPath = $this->resolvePath(config('wallet_pass.wwdr_path', ''));
            if ($certPath !== '' && !is_readable($certPath)) {
                $reasons[] = 'pass.cer not found or not readable: ' . $certPath;
            }
            if ($keyPath !== '' && !is_readable($keyPath)) {
                $reasons[] = 'iOS-Passkey-Key.key not found or not readable: ' . $keyPath;
            }
            if ($wwdrPath !== '' && !is_readable($wwdrPath)) {
                $reasons[] = 'AppleWWDRCAG3.cer not found or not readable: ' . $wwdrPath;
            }
            if (empty($reasons)) {
                $reasons[] = WalletPassService::getLastError() ?: 'Build failed. Check storage/logs/laravel.log';
            }
            return response(
                "Wallet pass is not available.\n\n" . implode("\n", $reasons),
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

        $filename = 'membership.pkpass';
        return response($pkpassContent, 200, [
            'Content-Type' => 'application/vnd.apple.pkpass',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => (string) strlen($pkpassContent),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    private function generateWithToken(WalletPassService $walletPassService, string $token): ?string
    {
        $key = self::TOKEN_CACHE_PREFIX . $token;
        $data = Cache::get($key);
        if (!$data || !isset($data['user_id'], $data['project_id'])) {
            return null;
        }
        $projectId = AdminContext::getCurrentProjectId();
        if ((int) $data['project_id'] !== (int) $projectId) {
            return null;
        }
        Cache::forget($key);
        return $walletPassService->generateForUser((int) $data['user_id']);
    }

    private function resolvePath(string $path): string
    {
        if ($path === '' || str_starts_with($path, '/') || (strlen($path) >= 2 && $path[1] === ':')) {
            return $path;
        }
        return base_path($path);
    }
}


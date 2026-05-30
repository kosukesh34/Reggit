<?php

namespace App\UseCases\Member\WalletPass;

use App\UseCases\Member\WalletPass\Data\WalletPassUrlIssueOutput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Safari で開く用のワンタイム URL を発行する UseCase。
 * LINE アプリ内では .pkpass が開けないため、この URL を返して Safari で開かせる。
 */
class WalletPassUrlIssueUseCase
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';

    private const TOKEN_TTL_MINUTES = 3;

    public function handle(int $userId, int $projectId, string $projectKey): WalletPassUrlIssueOutput
    {
        $token = Str::random(64);
        $key = self::TOKEN_CACHE_PREFIX.$token;
        Cache::put($key, [
            'user_id' => $userId,
            'project_id' => $projectId,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        // LIFF の openWindow は API 直リンクを拒否しやすいため、短い中間ページ URL を返す
        $url = $this->buildWalletAddUrl($token, $projectKey);

        return new WalletPassUrlIssueOutput($url);
    }

    public function buildWalletAddUrl(string $token, string $projectKey): string
    {
        return $this->currentOrigin().'/wallet-add?'.http_build_query([
            'pass_token' => $token,
            'project_key' => $projectKey,
            'type' => 'apple',
        ]);
    }

    public function buildPassDownloadUrl(string $token, string $projectKey): ?string
    {
        $cacheKey = self::TOKEN_CACHE_PREFIX.$token;
        $cached = Cache::get($cacheKey);
        if (! is_array($cached)) {
            return null;
        }

        return $this->currentOrigin().'/api/members/me/wallet-pass?'.http_build_query([
            'project_key' => $projectKey,
            'token' => $token,
        ]);
    }

    private function currentOrigin(): string
    {
        $origin = '';
        if (request() !== null) {
            $forwardedHost = request()->header('X-Forwarded-Host') ?? request()->server('HTTP_X_FORWARDED_HOST');
            if (is_string($forwardedHost) && trim($forwardedHost) !== '') {
                $proto = request()->header('X-Forwarded-Proto') ?? request()->server('HTTP_X_FORWARDED_PROTO');
                $scheme = is_string($proto) && trim($proto) !== '' ? trim($proto) : request()->getScheme();
                $origin = $scheme.'://'.trim(explode(',', $forwardedHost)[0]);
            } else {
                $origin = request()->getSchemeAndHttpHost();
            }
        } else {
            $origin = (string) config('app.url', url('/'));
        }

        return $this->ensureHttpsOrigin(rtrim($origin, '/'));
    }

    private function ensureHttpsOrigin(string $origin): string
    {
        $host = parse_url($origin, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return $origin;
        }
        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $origin;
        }
        if (str_starts_with($origin, 'http://')) {
            return 'https://'.substr($origin, 7);
        }

        return $origin;
    }
}

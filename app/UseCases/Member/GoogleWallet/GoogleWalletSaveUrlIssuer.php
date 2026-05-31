<?php

namespace App\UseCases\Member\GoogleWallet;

use App\Util\AdminContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * pay.google.com の長い Save URL を短いリダイレクト URL に包む（LIFF openWindow / DOM 属性向け）。
 */
class GoogleWalletSaveUrlIssuer
{
    private const CACHE_PREFIX = 'google_wallet_save_url:';

    private const TTL_MINUTES = 3;

    public function wrapForClient(string $saveUrl, string $projectKey): string
    {
        $token = Str::random(48);
        Cache::put(self::CACHE_PREFIX.$token, [
            'url' => $saveUrl,
            'project_id' => AdminContext::getCurrentProjectId(),
        ], now()->addMinutes(self::TTL_MINUTES));

        return $this->currentOrigin().'/google-wallet/save/'.$token.'?'.http_build_query([
            'project_key' => $projectKey !== '' ? $projectKey : 'default',
        ]);
    }

    /**
     * @return array{url: string, project_id: int|null}|null
     */
    public function resolveCached(string $token): ?array
    {
        $data = Cache::get(self::CACHE_PREFIX.$token);
        if (! is_array($data) || ! isset($data['url']) || ! is_string($data['url']) || trim($data['url']) === '') {
            return null;
        }

        return [
            'url' => trim($data['url']),
            'project_id' => isset($data['project_id']) ? (int) $data['project_id'] : null,
        ];
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

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

    public function wrapForClient(string $saveUrl, string $projectKey, string $requestOrigin): string
    {
        $token = Str::random(48);
        Cache::put(self::CACHE_PREFIX.$token, [
            'url' => $saveUrl,
            'project_id' => AdminContext::getCurrentProjectId(),
        ], now()->addMinutes(self::TTL_MINUTES));

        return rtrim($requestOrigin, '/').'/google-wallet/save/'.$token.'?'.http_build_query([
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
}

<?php

namespace App\UseCases\Member\WalletPass;

use App\UseCases\Member\WalletPass\Data\WalletPassUrlIssueResult;
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

    public function handle(int $userId, int $projectId, string $projectKey): WalletPassUrlIssueResult
    {
        $token = Str::random(64);
        $key = self::TOKEN_CACHE_PREFIX . $token;
        Cache::put($key, [
            'user_id' => $userId,
            'project_id' => $projectId,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        $url = url('/api/members/me/wallet-pass?' . http_build_query([
            'project_key' => $projectKey,
            'token' => $token,
        ]));

        return new WalletPassUrlIssueResult($url);
    }
}

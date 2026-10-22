<?php

namespace App\Services\Shop;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** LINE Login（Web OAuth）のトークン交換とプロフィール取得。 */
final class LineLoginClient
{
    /**
     * @return array{access_token: string, id_token?: string, refresh_token?: string, expires_in?: int, scope?: string, token_type?: string}
     */
    public function exchangeCode(string $code, string $redirectUri): array
    {
        $response = Http::asForm()->post('https://api.line.me/oauth2/v2.1/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => (string) config('line.channel_id'),
            'client_secret' => (string) config('line.channel_secret'),
        ]);
        if (! $response->successful()) {
            throw new RuntimeException('LINEトークンの取得に失敗しました。');
        }
        /** @var array{access_token?: string} $json */
        $json = $response->json();
        if (! is_string($json['access_token'] ?? null) || $json['access_token'] === '') {
            throw new RuntimeException('LINEアクセストークンが空です。');
        }

        return $json;
    }

    /**
     * @return array{userId: string, displayName?: string, pictureUrl?: string, email?: string}
     */
    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)->get('https://api.line.me/v2/profile');
        if (! $response->successful()) {
            throw new RuntimeException('LINEプロフィールの取得に失敗しました。');
        }
        /** @var array{userId?: string, displayName?: string, pictureUrl?: string} $json */
        $json = $response->json();
        if (! is_string($json['userId'] ?? null) || $json['userId'] === '') {
            throw new RuntimeException('LINEユーザーIDが取得できませんでした。');
        }

        return $json;
    }

    public function authorizeUrl(string $redirectUri, string $state): string
    {
        return 'https://access.line.me/oauth2/v2.1/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => (string) config('line.channel_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'profile openid email',
        ]);
    }
}

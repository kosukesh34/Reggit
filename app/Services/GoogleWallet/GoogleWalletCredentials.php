<?php

namespace App\Services\GoogleWallet;

use App\Enums\GoogleWalletApi;
use App\Util\StrHelper;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Google Wallet のサービスアカウント資格情報（credentials.json）と OAuth2 アクセストークン。 */
class GoogleWalletCredentials
{
    private const WALLET_OBJECT_SCOPE = 'https://www.googleapis.com/auth/wallet_object.issuer';

    private const JWT_ALGORITHM = 'RS256';

    /**
     * サービスアカウント JSON から client_email と private_key を読む。
     *
     * @return array{client_email: string, private_key: string}|null
     */
    public function load(): ?array
    {
        $path = config('google_wallet.credentials_path', '');
        if ($path === '' || ! File::isFile($path) || ! File::isReadable($path)) {
            return null;
        }
        $json = File::get($path);
        $data = json_decode($json, true);
        if (! is_array($data)
            || StrHelper::isEmptyStringOrNull($data['client_email'] ?? null)
            || StrHelper::isEmptyStringOrNull($data['private_key'] ?? null)) {
            return null;
        }

        return $data;
    }

    /**
     * サービスアカウントで OAuth2 アクセストークンを取得する。
     */
    public function accessToken(): ?string
    {
        $credentials = $this->load();
        if ($credentials === null) {
            return null;
        }

        $now = time();
        $assertion = [
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + GoogleWalletApi::OAUTH2_TOKEN_TTL_SECONDS,
            'scope' => self::WALLET_OBJECT_SCOPE,
        ];

        try {
            $jwt = JWT::encode(
                $assertion,
                $credentials['private_key'],
                self::JWT_ALGORITHM
            );
        } catch (\Throwable $e) {
            Log::warning('google-wallet OAuth2 JWT encode failed', ['error' => $e->getMessage()]);

            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            Log::warning('google-wallet OAuth2 token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;

        return is_string($token) ? $token : null;
    }
}

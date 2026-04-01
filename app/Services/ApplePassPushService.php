<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApplePassPushService
{
    private const APNS_PRODUCTION_ENDPOINT = 'https://api.push.apple.com';

    private const APNS_SANDBOX_ENDPOINT = 'https://api.sandbox.push.apple.com';

    private const TOKEN_TTL_SECONDS = 3300;

    private ?string $cachedJwt = null;

    private int $cachedJwtIssuedAt = 0;

    /**
     * @param  array<int, string>  $pushTokens
     */
    public function pushWalletPassUpdated(array $pushTokens): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $tokens = array_values(array_unique(array_filter(array_map('trim', $pushTokens), fn ($v) => $v !== '')));
        if ($tokens === []) {
            return;
        }

        $jwt = $this->getProviderToken();
        if ($jwt === null) {
            return;
        }

        $endpoint = $this->getEndpoint();
        $topic = (string) config('wallet_pass.apple_push_topic', '');
        $timeout = (int) config('wallet_pass.apple_push_timeout_seconds', 10);

        foreach ($tokens as $token) {
            $url = $endpoint.'/3/device/'.rawurlencode($token);
            $response = Http::withHeaders([
                'authorization' => 'bearer '.$jwt,
                'apns-topic' => $topic,
                'apns-push-type' => 'background',
                'apns-priority' => '5',
            ])
                ->timeout($timeout)
                ->withBody('{}', 'application/json')
                ->post($url);

            if (! $response->successful()) {
                Log::warning('apple-wallet apns push failed', [
                    'status' => $response->status(),
                    'reason' => $response->json('reason'),
                    'token_suffix' => substr($token, -8),
                ]);
            }
        }
    }

    public function isEnabled(): bool
    {
        if (config('wallet_pass.apple_push_enabled', false) !== true) {
            return false;
        }
        if ((string) config('wallet_pass.apple_push_team_id', '') === '') {
            return false;
        }
        if ((string) config('wallet_pass.apple_push_key_id', '') === '') {
            return false;
        }
        if ((string) config('wallet_pass.apple_push_topic', '') === '') {
            return false;
        }

        $path = $this->resolvePath((string) config('wallet_pass.apple_push_auth_key_path', ''));

        return $path !== '' && File::exists($path) && File::isReadable($path);
    }

    private function getEndpoint(): string
    {
        return config('wallet_pass.apple_push_use_sandbox', false) === true
            ? self::APNS_SANDBOX_ENDPOINT
            : self::APNS_PRODUCTION_ENDPOINT;
    }

    private function getProviderToken(): ?string
    {
        $now = time();
        if ($this->cachedJwt !== null && ($now - $this->cachedJwtIssuedAt) < self::TOKEN_TTL_SECONDS) {
            return $this->cachedJwt;
        }

        $teamId = (string) config('wallet_pass.apple_push_team_id', '');
        $keyId = (string) config('wallet_pass.apple_push_key_id', '');
        $path = $this->resolvePath((string) config('wallet_pass.apple_push_auth_key_path', ''));
        if ($teamId === '' || $keyId === '' || $path === '' || ! File::exists($path) || ! File::isReadable($path)) {
            Log::warning('apple-wallet apns provider token config invalid');

            return null;
        }

        $privateKey = File::get($path);
        try {
            $this->cachedJwt = JWT::encode([
                'iss' => $teamId,
                'iat' => $now,
            ], $privateKey, 'ES256', $keyId);
            $this->cachedJwtIssuedAt = $now;

            return $this->cachedJwt;
        } catch (\Throwable $e) {
            Log::warning('apple-wallet apns provider token generation failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}

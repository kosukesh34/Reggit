<?php

namespace App\Services\GoogleWallet;

use App\Enums\GoogleWalletApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Wallet Objects API の LoyaltyClass（会員証の種類）の確認・作成・表示更新。
 * 失敗理由が分かったときは takeError() で受け取れる。
 */
class GoogleWalletLoyaltyClassApi
{
    private const WALLET_OBJECTS_BASE = 'https://walletobjects.googleapis.com/walletobjects/v1';

    private const API_HEALTH_CACHE_KEY = 'google_wallet_api_health_ok';

    private ?string $error = null;

    public function __construct(
        private GoogleWalletCredentials $credentials,
        private GoogleWalletLoyaltyPayloadBuilder $payloadBuilder,
    ) {}

    /** 直前の呼び出しで分かった失敗理由を返し、消す */
    public function takeError(): ?string
    {
        $error = $this->error;
        $this->error = null;

        return $error;
    }

    public function exists(string $classId): bool
    {
        $token = $this->credentials->accessToken();
        if ($token === null) {
            return false;
        }

        $response = Http::withToken($token)
            ->get(self::WALLET_OBJECTS_BASE.'/loyaltyClass/'.$classId);

        return $response->successful();
    }

    /** Wallet Objects API に届くか（結果は10分キャッシュ） */
    public function verifyAccess(): bool
    {
        if (Cache::get(self::API_HEALTH_CACHE_KEY) === true) {
            return true;
        }

        $token = $this->credentials->accessToken();
        if ($token === null) {
            $this->fail('OAuth token request failed. credentials.json を確認してください。');

            return false;
        }

        $issuerId = (string) config('google_wallet.issuer_id', '');
        $classSuffix = (string) config('google_wallet.class_suffix', 'membership');
        $classId = $issuerId.'.'.$classSuffix;
        $response = Http::withToken($token)
            ->get(self::WALLET_OBJECTS_BASE.'/loyaltyClass/'.$classId);

        if ($response->status() === 404 || $response->successful()) {
            Cache::put(self::API_HEALTH_CACHE_KEY, true, now()->addMinutes(10));

            return true;
        }

        if ($response->status() === 403) {
            $message = $this->parseWallet403Message($response->body());
            $this->fail($message);
            Log::warning('[GoogleWallet] API access check failed', [
                'class_id' => $classId,
                'status' => $response->status(),
                'service_account' => $this->serviceAccountEmail(),
                'message' => $message,
            ]);

            return false;
        }

        $this->fail('Google Wallet API check failed (HTTP '.$response->status().').');

        return false;
    }

    /**
     * LoyaltyClass が存在すれば true、存在しなければ REST で作成して true、失敗時は false。
     * クラスが既に存在すれば JWT で Object のみ送るため、createSaveUrl 内で毎回呼ぶ。
     */
    public function ensure(string $classId, string $programName, string $programLogoUri): bool
    {
        $token = $this->credentials->accessToken();
        if ($token === null) {
            return false;
        }

        $get = Http::withToken($token)
            ->get(self::WALLET_OBJECTS_BASE.'/loyaltyClass/'.$classId);

        if ($get->successful()) {
            $cacheKey = 'google_wallet_class_patched:'.$classId;
            if (Cache::get($cacheKey) !== true) {
                $this->patchLoyaltyClassDisplayLabels($token, $classId);
                Cache::put($cacheKey, true, now()->addDay());
            }

            return true;
        }

        if ($get->status() !== 404) {
            $body = $get->body();
            Log::warning('google-wallet loyaltyClass get failed', [
                'classId' => $classId,
                'status' => $get->status(),
                'body' => $body,
            ]);
            if ($get->status() === 403 && is_string($body)) {
                $message = $this->parseWallet403Message($body);
                $this->fail($message);
                Log::warning('[GoogleWallet] loyaltyClass access denied', [
                    'class_id' => $classId,
                    'service_account' => $this->serviceAccountEmail(),
                    'message' => $message,
                ]);
            }

            return false;
        }

        $maxLen = GoogleWalletApi::MAX_DISPLAY_LENGTH;
        $body = [
            'id' => $classId,
            'programName' => $this->payloadBuilder->truncateForWallet($programName, $maxLen),
            'issuerName' => $this->payloadBuilder->truncateForWallet($programName, $maxLen),
            'reviewStatus' => GoogleWalletApi::REVIEW_STATUS_UNDER_REVIEW,
            'programLogo' => [
                'sourceUri' => ['uri' => $programLogoUri],
                'contentDescription' => [
                    'defaultValue' => ['language' => GoogleWalletApi::DEFAULT_LANGUAGE, 'value' => $programName.' logo'],
                ],
            ],
            'accountNameLabel' => GoogleWalletApi::LABEL_ACCOUNT_NAME,
            'accountIdLabel' => GoogleWalletApi::LABEL_ACCOUNT_ID,
            'rewardsTierLabel' => GoogleWalletApi::LABEL_RANK,
        ];
        $hexBg = config('google_wallet.hex_background_color', '');
        if ($hexBg !== '' && preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexBg)) {
            $body['hexBackgroundColor'] = $hexBg;
        }

        $insert = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::WALLET_OBJECTS_BASE.'/loyaltyClass', $body);

        if (! $insert->successful()) {
            $insertBody = $insert->body();
            Log::warning('google-wallet loyaltyClass insert failed', [
                'classId' => $classId,
                'status' => $insert->status(),
                'body' => $insertBody,
            ]);
            if ($insert->status() === 403 && is_string($insertBody) && str_contains($insertBody, 'walletobjects.googleapis.com')) {
                $decoded = json_decode($insertBody, true);
                $url = $decoded['error']['details'][0]['metadata']['activationUrl'] ?? null;
                if (is_string($url)) {
                    Log::warning('google-wallet API が無効です。以下の URL で「Google Wallet API」を有効にしてください: '.$url);
                }
            }

            return false;
        }

        Log::info('google-wallet loyaltyClass created', ['classId' => $classId]);

        return true;
    }

    /**
     * 既存の LoyaltyClass に表示用ラベル（日本語）と背景色を PATCH で反映する。
     * 既に追加済みのパスは「削除して再度追加」すると更新された表示になる。
     */
    private function patchLoyaltyClassDisplayLabels(?string $token, string $classId): void
    {
        if ($token === null) {
            return;
        }
        $body = [
            'accountNameLabel' => GoogleWalletApi::LABEL_ACCOUNT_NAME,
            'accountIdLabel' => GoogleWalletApi::LABEL_ACCOUNT_ID,
            'rewardsTierLabel' => GoogleWalletApi::LABEL_RANK,
        ];
        $hexBg = config('google_wallet.hex_background_color', '');
        if ($hexBg !== '' && preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexBg)) {
            $body['hexBackgroundColor'] = $hexBg;
        }
        $patch = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->patch(self::WALLET_OBJECTS_BASE.'/loyaltyClass/'.$classId, $body);

        if ($patch->successful()) {
            Log::info('google-wallet loyaltyClass display labels patched', ['classId' => $classId]);
        } else {
            Log::warning('google-wallet loyaltyClass patch (display labels) failed', [
                'classId' => $classId,
                'status' => $patch->status(),
                'body' => $patch->body(),
            ]);
        }
    }

    private function parseWallet403Message(string $body): string
    {
        $decoded = json_decode($body, true);
        $reason = is_array($decoded)
            ? (string) ($decoded['error']['errors'][0]['reason'] ?? '')
            : '';
        $activationUrl = is_array($decoded)
            ? ($decoded['error']['details'][0]['metadata']['activationUrl'] ?? null)
            : null;
        $serviceAccount = $this->serviceAccountEmail() ?? 'credentials.json の client_email';

        if ($reason === 'permissionDenied') {
            return 'Google Wallet のサービスアカウントが Issuer に未登録です。'
                .'Pay & Wallet Console → Users に '.$serviceAccount
                .' を Developer として追加してください。'
                .' あわせて GCP プロジェクトで Google Wallet API を有効化してください。';
        }

        if (is_string($activationUrl) && $activationUrl !== '') {
            return 'Google Wallet API が無効です。有効化: '.$activationUrl;
        }

        return 'Google Wallet API へのアクセスが拒否されました (HTTP 403)。'
            .' サービスアカウント '.$serviceAccount.' が Issuer ID '
            .config('google_wallet.issuer_id').' に紐付いているか確認してください。';
    }

    private function fail(string $message): void
    {
        $this->error = $message;
    }

    private function serviceAccountEmail(): ?string
    {
        $credentials = $this->credentials->load();

        return is_array($credentials) ? ($credentials['client_email'] ?? null) : null;
    }
}

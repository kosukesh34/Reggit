<?php

namespace App\Services;

use App\Models\Member;
use App\Models\ShopSetting;
use App\Models\User;
use App\Util\AdminContext;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Wallet 用の「Add to Google Wallet」リンク（JWT 付き URL）を生成するサービス。
 * Loyalty Pass（ロイヤルティカード）形式で会員証（会員番号・ポイント・ランク）を発行する。
 * JWT の形式は Google Wallet API の loyaltyObjects / loyaltyClasses に準拠。
 */
class GoogleWalletPassService
{
    private const SAVE_URL_PREFIX = 'https://pay.google.com/gp/v/save/';

    private const WALLET_OBJECT_SCOPE = 'https://www.googleapis.com/auth/wallet_object.issuer';

    private const WALLET_OBJECTS_BASE = 'https://walletobjects.googleapis.com/walletobjects/v1';

    /**
     * 会員用の Add to Google Wallet URL を生成する。
     * 失敗時は null を返す。
     */
    public function createSaveUrlForUser(User $user): ?string
    {
        $data = $this->getMemberService()->getMemberData($user);
        if (! isset($data['is_member']) || $data['is_member'] !== true || ! isset($data['member']) || ! $data['member'] instanceof Member) {
            Log::info('google-wallet createSaveUrlForUser: not a member or no member data', ['user_id' => $user->id]);
            return null;
        }

        $member = $data['member'];
        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', '会員証');
        $barcodeValue = $member->member_number ?? (string) $member->id;
        $objectSuffix = 'member-' . (string) $member->id;
        $points = $data['points'] ?? $member->getTotalPointsFromTransactions();
        $rankLabel = $data['current_rank_label'] ?? $member->rank ?? '';
        $accountName = $user->name ?? '';
        $projectId = $project !== null ? $project->id : (isset($user->project_id) ? (int) $user->project_id : null);

        return $this->createSaveUrl($organizationName, $barcodeValue, $objectSuffix, $points, $rankLabel, $accountName, $projectId);
    }

    /**
     * 指定ユーザー ID で Add to Google Wallet URL を生成する（トークン経由用）。
     */
    public function createSaveUrlForUserId(int $userId): ?string
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || $projectId === false) {
            Log::info('google-wallet createSaveUrlForUserId: no project in context');
            return null;
        }
        $user = User::where('id', $userId)->where('project_id', $projectId)->first();
        if ($user === null) {
            Log::info('google-wallet createSaveUrlForUserId: user not found', ['user_id' => $userId, 'project_id' => $projectId]);
            return null;
        }

        $url = $this->createSaveUrlForUser($user);
        if ($url === null) {
            Log::info('google-wallet createSaveUrlForUserId: createSaveUrlForUser returned null', ['user_id' => $userId]);
        }
        return $url;
    }

    /**
     * Add to Google Wallet 用の署名付き JWT（Loyalty）を組み立て、URL を返す。
     *
     * @param  string  $accountName  会員名（表示用）
     * @param  int|null  $projectId  プロジェクトID（指定時は管理画面で設定した画像URLを優先）
     */
    public function createSaveUrl(
        string $organizationName,
        string $barcodeValue,
        string $objectSuffix,
        int $points = 0,
        string $rankLabel = '',
        string $accountName = '',
        ?int $projectId = null
    ): ?string {
        if (! $this->isAvailable()) {
            return null;
        }

        $issuerId = config('google_wallet.issuer_id');
        $classSuffix = config('google_wallet.class_suffix', 'membership');
        $classId = $issuerId . '.' . $classSuffix;
        $objectId = $issuerId . '.' . $objectSuffix;

        // 推奨文字数以内に収める（API の Recommended maximum length に従う）
        $programName = $this->truncateForWallet(mb_strimwidth($organizationName, 0, 20, ''), 20);
        $issuerName = $this->truncateForWallet($programName, 20);
        $accountNameDisplay = $accountName !== '' ? $this->truncateForWallet($accountName, 20) : ('会員#' . $barcodeValue);
        $accountIdDisplay = $this->truncateForWallet($barcodeValue, 20);

        // 管理画面で設定した画像URLを優先、未設定時は config
        $programLogoUri = $projectId !== null ? (ShopSetting::getWalletGoogleProgramLogoUrl($projectId) ?? config('google_wallet.program_logo_uri', 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg')) : config('google_wallet.program_logo_uri', 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg');
        $heroImageUri = $projectId !== null ? (ShopSetting::getWalletGoogleHeroImageUrl($projectId) ?? config('google_wallet.hero_image_uri', 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg')) : config('google_wallet.hero_image_uri', 'https://storage.googleapis.com/wallet-lab-tools-codelab-artifacts-public/pass_google_logo.jpg');

        // クラスが既に存在すれば JWT では Object のみ送る（class must already exist and be approved に従う）
        $classExists = $this->ensureLoyaltyClassExists($classId, $programName, $programLogoUri);

        $loyaltyClass = null;
        if (! $classExists) {
            $loyaltyClass = [
                'id' => $classId,
                'programName' => $programName,
                'issuerName' => $issuerName,
                'reviewStatus' => 'UNDER_REVIEW',
                'programLogo' => [
                    'sourceUri' => ['uri' => $programLogoUri],
                    'contentDescription' => [
                        'defaultValue' => ['language' => 'en-US', 'value' => $programName . ' logo'],
                    ],
                ],
            ];
        }

        // LoyaltyObject: 公式サンプル準拠（textModulesData に id 必須、barcode は type/value のみ、label は Points）
        $textModules = [];
        if ($rankLabel !== '') {
            $textModules[] = [
                'id' => 'rank',
                'header' => 'Rank',
                'body' => $rankLabel,
            ];
        }
        $textModules[] = [
            'id' => 'points',
            'header' => $programName,
            'body' => (string) $points . ' pts' . ($rankLabel !== '' ? ' · ' . $rankLabel : ''),
        ];
        $textModules[] = [
            'id' => 'member_info',
            'header' => 'Member ID',
            'body' => $barcodeValue . ($rankLabel !== '' ? ' · ' . $rankLabel : ''),
        ];

        $loyaltyObject = [
            'id' => $objectId,
            'classId' => $classId,
            'state' => 'ACTIVE',
            'accountId' => $accountIdDisplay,
            'accountName' => $accountNameDisplay,
            'barcode' => [
                'type' => 'QR_CODE',
                'value' => $barcodeValue,
            ],
            'loyaltyPoints' => [
                'label' => 'Points',
                'balance' => ['int' => (int) $points],
            ],
            'textModulesData' => $textModules,
            'heroImage' => [
                'sourceUri' => ['uri' => $heroImageUri],
                'contentDescription' => [
                    'defaultValue' => ['language' => 'en-US', 'value' => $programName . ' card'],
                ],
            ],
        ];

        $credentials = $this->loadCredentials();
        if ($credentials === null) {
            return null;
        }

        $origins = config('google_wallet.origins', []);
        if (! is_array($origins)) {
            $origins = $origins === '' ? [] : [$origins];
        }
        $origins = array_values(array_unique(array_filter(array_map('trim', $origins))));

        if (! app()->runningInConsole() && request()) {
            $requestOrigin = request()->getSchemeAndHttpHost();
            if ($requestOrigin !== '' && ! in_array($requestOrigin, $origins, true)) {
                $origins[] = $requestOrigin;
            }
        }
        if ($origins === []) {
            $appUrl = config('app.url', 'http://localhost');
            $host = parse_url($appUrl, PHP_URL_HOST);
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
            $port = parse_url($appUrl, PHP_URL_PORT);
            $fallback = $host ? ($scheme . '://' . $host . ($port ? ':' . $port : '')) : 'http://localhost';
            $origins = [$fallback];
        }

        $payload = ['loyaltyObjects' => [$loyaltyObject]];
        if ($loyaltyClass !== null) {
            $payload['loyaltyClasses'] = [$loyaltyClass];
        }

        $claims = [
            'iss' => $credentials['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => time(),
            'origins' => $origins,
            'payload' => $payload,
        ];

        try {
            $token = JWT::encode(
                $claims,
                $credentials['private_key'],
                'RS256'
            );

            return self::SAVE_URL_PREFIX . $token;
        } catch (\Throwable $e) {
            Log::error('google-wallet JWT encode failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            report($e);

            return null;
        }
    }

    public function isAvailable(): bool
    {
        if (config('google_wallet.enabled', false) !== true) {
            return false;
        }
        if (config('google_wallet.issuer_id', '') === '') {
            return false;
        }
        $path = config('google_wallet.credentials_path', '');
        if ($path === '' || ! File::isFile($path) || ! File::isReadable($path)) {
            return false;
        }

        return true;
    }

    /**
     * 利用できない理由を返す（管理画面・503 用）。
     *
     * @return array<int, string>
     */
    public function getUnavailabilityReasons(): array
    {
        $reasons = [];
        if (config('google_wallet.enabled', false) !== true) {
            $reasons[] = 'Google Wallet is disabled (GOOGLE_WALLET_ENABLED).';

            return $reasons;
        }
        if (config('google_wallet.issuer_id', '') === '') {
            $reasons[] = 'GOOGLE_WALLET_ISSUER_ID is not set.';
        }
        $path = config('google_wallet.credentials_path', '');
        if ($path === '' || ! File::isFile($path) || ! File::isReadable($path)) {
            $reasons[] = 'Service account credentials file not found or not readable: ' . $path;
        }

        return $reasons;
    }

    private function loadCredentials(): ?array
    {
        $path = config('google_wallet.credentials_path', '');
        if ($path === '' || ! File::isFile($path) || ! File::isReadable($path)) {
            return null;
        }
        $json = File::get($path);
        $data = json_decode($json, true);
        if (! is_array($data) || empty($data['client_email']) || empty($data['private_key'])) {
            return null;
        }

        return $data;
    }

    private function getMemberService(): MemberService
    {
        return app(MemberService::class);
    }

    /**
     * 表示用文字列を指定長以内に収める（マルチバイト考慮）。
     */
    private function truncateForWallet(string $s, int $maxLen): string
    {
        if (mb_strlen($s) <= $maxLen) {
            return $s;
        }

        return mb_substr($s, 0, $maxLen);
    }

    /**
     * サービスアカウントで OAuth2 アクセストークンを取得する。
     */
    private function getWalletApiAccessToken(): ?string
    {
        $credentials = $this->loadCredentials();
        if ($credentials === null) {
            return null;
        }

        $now = time();
        $assertion = [
            'iss' => $credentials['client_email'],
            'sub' => $credentials['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => self::WALLET_OBJECT_SCOPE,
        ];

        try {
            $jwt = JWT::encode(
                $assertion,
                $credentials['private_key'],
                'RS256'
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

    /**
     * LoyaltyClass が存在すれば true、存在しなければ REST で作成して true、失敗時は false。
     * クラスが既に存在すれば JWT で Object のみ送るため、createSaveUrl 内で毎回呼ぶ。
     */
    public function ensureLoyaltyClassExists(string $classId, string $programName, string $programLogoUri): bool
    {
        $token = $this->getWalletApiAccessToken();
        if ($token === null) {
            return false;
        }

        $get = Http::withToken($token)
            ->get(self::WALLET_OBJECTS_BASE . '/loyaltyClass/' . $classId);

        if ($get->successful()) {
            return true;
        }

        if ($get->status() !== 404) {
            $body = $get->body();
            Log::warning('google-wallet loyaltyClass get failed', [
                'classId' => $classId,
                'status' => $get->status(),
                'body' => $body,
            ]);
            if ($get->status() === 403 && is_string($body) && str_contains($body, 'walletobjects.googleapis.com')) {
                $decoded = json_decode($body, true);
                $url = $decoded['error']['details'][0]['metadata']['activationUrl'] ?? null;
                if (is_string($url)) {
                    Log::warning('google-wallet API が無効です。以下の URL で「Google Wallet API」を有効にしてください: ' . $url);
                }
            }

            return false;
        }

        $body = [
            'id' => $classId,
            'programName' => $this->truncateForWallet($programName, 20),
            'issuerName' => $this->truncateForWallet($programName, 20),
            'reviewStatus' => 'UNDER_REVIEW',
            'programLogo' => [
                'sourceUri' => ['uri' => $programLogoUri],
                'contentDescription' => [
                    'defaultValue' => ['language' => 'en-US', 'value' => $programName . ' logo'],
                ],
            ],
        ];

        $insert = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::WALLET_OBJECTS_BASE . '/loyaltyClass', $body);

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
                    Log::warning('google-wallet API が無効です。以下の URL で「Google Wallet API」を有効にしてください: ' . $url);
                }
            }

            return false;
        }

        Log::info('google-wallet loyaltyClass created', ['classId' => $classId]);

        return true;
    }
}

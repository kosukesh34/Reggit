<?php

namespace App\Services;

use App\Models\Member;
use App\Models\ShopSetting;
use App\Models\User;
use App\Util\AdminContext;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
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
        // オブジェクトIDに -b を付与し、既存のQRオブジェクトと区別してバーコードの新オブジェクトとして発行する
        $objectSuffix = 'member-' . (string) $member->id . '-b';
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

        // クラスが存在しなければ REST で先に作成する（JWT 発行時は class が存在している必要がある）
        $this->ensureLoyaltyClassExists($classId, $programName, $programLogoUri);

        // JWT には常にクラス定義を含め、表示（日本語ラベル・背景色・バーコード等）を確実に反映させる
        $logoImage = [
            'sourceUri' => ['uri' => $programLogoUri],
            'contentDescription' => [
                'defaultValue' => ['language' => 'en-US', 'value' => $programName . ' logo'],
            ],
        ];
        $loyaltyClass = [
            'id' => $classId,
            'programName' => $programName,
            'issuerName' => $issuerName,
            'reviewStatus' => 'UNDER_REVIEW',
            'programLogo' => $logoImage,
            'wideProgramLogo' => $logoImage,
            'accountNameLabel' => '会員名',
            'accountIdLabel' => '会員番号',
            'rewardsTierLabel' => 'ランク',
        ];
        $hexBg = config('google_wallet.hex_background_color', '');
        if ($hexBg !== '' && preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexBg)) {
            $loyaltyClass['hexBackgroundColor'] = $hexBg;
        }

        // Android / Web / iOS で同じレイアウトにするため classTemplateInfo でカード・リスト・詳細の表示順を統一
        $loyaltyClass['classTemplateInfo'] = [
            'cardTemplateOverride' => [
                'cardRowTemplateInfos' => [
                    [
                        'twoItems' => [
                            'startItem' => ['firstValue' => ['fields' => [['fieldPath' => 'object.accountName']]]],
                            'endItem' => ['firstValue' => ['fields' => [['fieldPath' => 'object.loyaltyPoints']]]],
                        ],
                    ],
                    [
                        'oneItem' => [
                            'item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.accountId']]]],
                        ],
                    ],
                ],
            ],
            'listTemplateOverride' => [
                'firstRowOption' => ['fieldOption' => ['fields' => [['fieldPath' => 'object.accountName']]]],
                'secondRowOption' => ['fields' => [['fieldPath' => 'object.loyaltyPoints.balance']]],
            ],
            'detailsTemplateOverride' => [
                'detailsItemInfos' => [
                    ['item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.textModulesData[\'rank\']']]]]],
                    ['item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.textModulesData[\'points\']']]]]],
                    ['item' => ['firstValue' => ['fields' => [['fieldPath' => 'object.textModulesData[\'member_info\']']]]]],
                ],
            ],
        ];

        // LoyaltyObject: ポイントカードUIに合わせる（会員証・ランク・ポイント pt・会員番号、バーコードは CODE_128）
        $textModules = [];
        if ($rankLabel !== '') {
            $textModules[] = [
                'id' => 'rank',
                'header' => 'ランク',
                'body' => $rankLabel,
            ];
        }
        $textModules[] = [
            'id' => 'points',
            'header' => 'ポイント',
            'body' => (string) $points . ' pt' . ($rankLabel !== '' ? ' · ' . $rankLabel : ''),
        ];
        $textModules[] = [
            'id' => 'member_info',
            'header' => '会員番号',
            'body' => $barcodeValue,
        ];

        $loyaltyObject = [
            'id' => $objectId,
            'classId' => $classId,
            'state' => 'ACTIVE',
            'accountId' => $accountIdDisplay,
            'accountName' => $accountNameDisplay,
            'barcode' => [
                'type' => 'CODE_128',
                'value' => $barcodeValue,
                'alternateText' => $barcodeValue,
            ],
            'loyaltyPoints' => [
                'label' => 'ポイント',
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

        $payload = [
            'loyaltyClasses' => [$loyaltyClass],
            'loyaltyObjects' => [$loyaltyObject],
        ];

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
            $cacheKey = 'google_wallet_class_patched:' . $classId;
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
            'accountNameLabel' => '会員名',
            'accountIdLabel' => '会員番号',
            'rewardsTierLabel' => 'ランク',
        ];
        $hexBg = config('google_wallet.hex_background_color', '');
        if ($hexBg !== '' && preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexBg)) {
            $body['hexBackgroundColor'] = $hexBg;
        }

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
            'accountNameLabel' => '会員名',
            'accountIdLabel' => '会員番号',
            'rewardsTierLabel' => 'ランク',
        ];
        $hexBg = config('google_wallet.hex_background_color', '');
        if ($hexBg !== '' && preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $hexBg)) {
            $body['hexBackgroundColor'] = $hexBg;
        }
        $patch = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->patch(self::WALLET_OBJECTS_BASE . '/loyaltyClass/' . $classId, $body);

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
}

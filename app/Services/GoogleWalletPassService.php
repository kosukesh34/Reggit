<?php

namespace App\Services;

use App\Enums\GoogleWalletApi;
use App\Models\Member;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\GoogleWallet\GoogleWalletLoyaltyPayloadBuilder;
use App\Util\AdminContext;
use App\Util\StrHelper;
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

    private const JWT_AUDIENCE = 'google';

    private const JWT_TYPE_SAVE_TO_WALLET = 'savetowallet';

    private const JWT_ALGORITHM = 'RS256';

    private const API_HEALTH_CACHE_KEY = 'google_wallet_api_health_ok';

    /** Google Wallet API が取得できる公開 HTTPS 画像（旧 codelab URL は 2025 以降無効） */
    private const DEFAULT_LOGO_URI = 'https://www.gstatic.com/images/branding/product/2x/wallet_96dp.png';

    private GoogleWalletLoyaltyPayloadBuilder $payloadBuilder;

    private ?string $lastError = null;

    public function __construct(GoogleWalletLoyaltyPayloadBuilder $payloadBuilder)
    {
        $this->payloadBuilder = $payloadBuilder;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getServiceAccountEmail(): ?string
    {
        $credentials = $this->loadCredentials();

        return is_array($credentials) ? ($credentials['client_email'] ?? null) : null;
    }

    private function setLastError(string $message): void
    {
        $this->lastError = $message;
    }

    /**
     * 会員用の Add to Google Wallet URL を生成する。
     * 失敗時は null を返す。
     */
    public function createSaveUrlForUser(User $user): ?string
    {
        $data = $this->getMemberService()->getMemberData($user);
        if (! $this->isValidMemberData($data)) {
            $projectId = AdminContext::getCurrentProjectId();
            Log::info('google-wallet createSaveUrlForUser: not a member or no member data', [
                'user_id' => $user->id,
                'project_id' => $projectId,
            ]);
            $this->setLastError('会員情報が見つかりません。');

            return null;
        }

        $member = $data['member'];
        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', 'Reggit');
        $barcodeValue = $member->member_number ?? (string) $member->id;
        // オブジェクトIDに -b を付与し、既存のQRオブジェクトと区別してバーコードの新オブジェクトとして発行する
        $objectSuffix = 'member-'.(string) $member->id.'-b';
        $points = $data['points'] ?? $member->getTotalPointsFromTransactions();
        $rankLabel = $data['current_rank_label'] ?? $member->rank ?? '';
        $accountName = $user->name ?? '';
        $projectId = $project !== null ? $project->id : (isset($user->project_id) ? (int) $user->project_id : null);
        $appearance = app(MemberCardAppearanceService::class)->resolveForMember($member);

        return $this->createSaveUrl(
            $organizationName,
            $barcodeValue,
            $objectSuffix,
            $points,
            $rankLabel,
            $accountName,
            $projectId,
            (string) ($appearance['hex_background'] ?? '')
        );
    }

    /**
     * 会員情報の変更を Google Wallet 側へ同期する。
     * 既存オブジェクトがあれば更新し、存在しなければ新規作成する。
     */
    public function syncLoyaltyObjectForMember(Member $member): bool
    {
        if (! $this->canSyncExistingObjects()) {
            Log::info('google-wallet loyaltyObject sync skipped: service unavailable', [
                'member_id' => (int) $member->id,
            ]);

            return false;
        }

        $user = $member->user;
        if (! $user instanceof User) {
            return false;
        }

        $appearance = app(MemberCardAppearanceService::class)->resolveForMember($member);
        $project = $member->project;
        $projectId = (int) ($member->project_id ?? 0);
        $organizationName = $project !== null ? $project->name : config('app.name', 'Reggit');
        $barcodeValue = $member->member_number ?? (string) $member->id;
        $objectSuffix = 'member-'.(string) $member->id.'-b';
        $points = (int) $member->getTotalPointsFromTransactions();
        $rankLabel = (string) ($appearance['rank_label'] ?? $member->rank ?? '');
        $accountName = (string) ($user->name ?? '');

        $issuerId = config('google_wallet.issuer_id');
        $classSuffix = config('google_wallet.class_suffix', 'membership');
        $classId = $issuerId.'.'.$classSuffix;
        $objectId = $issuerId.'.'.$objectSuffix;

        $programLogoUri = $this->resolveProgramLogoUri($projectId > 0 ? $projectId : null);
        $heroImageUri = $this->resolveHeroImageUri($projectId > 0 ? $projectId : null);

        if (! $this->ensureLoyaltyClassExists($classId, $organizationName, $programLogoUri)) {
            return false;
        }

        $maxLen = GoogleWalletApi::MAX_DISPLAY_LENGTH;
        $payloadParams = [
            'classId' => $classId,
            'objectId' => $objectId,
            'programName' => $this->payloadBuilder->truncateForWallet($organizationName, $maxLen),
            'programLogoUri' => $programLogoUri,
            'heroImageUri' => $heroImageUri,
            'accountNameDisplay' => $accountName !== ''
                ? $this->payloadBuilder->truncateForWallet($accountName, $maxLen)
                : (GoogleWalletApi::ACCOUNT_NAME_FALLBACK_PREFIX.$barcodeValue),
            'accountIdDisplay' => $this->payloadBuilder->truncateForWallet($barcodeValue, $maxLen),
            'barcodeValue' => $barcodeValue,
            'points' => $points,
            'rankLabel' => $rankLabel,
            'hexBackgroundColor' => (string) ($appearance['hex_background'] ?? ''),
        ];
        $walletObject = $this->payloadBuilder->build($payloadParams)['loyaltyObject'];

        $token = $this->getWalletApiAccessToken();
        if ($token === null) {
            return false;
        }

        $endpoint = self::WALLET_OBJECTS_BASE.'/loyaltyObject/'.$objectId;
        $update = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->put($endpoint, $walletObject);

        if ($update->successful()) {
            Log::info('google-wallet loyaltyObject updated', [
                'objectId' => $objectId,
                'member_id' => (int) $member->id,
                'points' => $points,
            ]);

            return true;
        }

        if ($update->status() !== 404) {
            Log::warning('google-wallet loyaltyObject update failed', [
                'objectId' => $objectId,
                'status' => $update->status(),
                'body' => $update->body(),
            ]);

            return false;
        }

        $insert = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::WALLET_OBJECTS_BASE.'/loyaltyObject', $walletObject);

        if (! $insert->successful()) {
            Log::warning('google-wallet loyaltyObject insert failed', [
                'objectId' => $objectId,
                'status' => $insert->status(),
                'body' => $insert->body(),
            ]);

            return false;
        }

        Log::info('google-wallet loyaltyObject created', [
            'objectId' => $objectId,
            'member_id' => (int) $member->id,
            'points' => $points,
        ]);

        return true;
    }

    /**
     * 指定ユーザー ID で Add to Google Wallet URL を生成する（トークン経由用）。
     */
    public function createSaveUrlForUserId(int $userId): ?string
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || $projectId === false) {
            Log::info('google-wallet createSaveUrlForUserId: no project in context', ['user_id' => $userId]);

            return null;
        }
        $user = User::where('id', $userId)->where('project_id', $projectId)->first();
        if ($user === null) {
            Log::info('google-wallet createSaveUrlForUserId: user not found', ['user_id' => $userId, 'project_id' => $projectId]);

            return null;
        }

        $url = $this->createSaveUrlForUser($user);
        if ($url === null) {
            Log::info('google-wallet createSaveUrlForUserId: createSaveUrlForUser returned null', ['user_id' => $userId, 'project_id' => $projectId]);
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
        ?int $projectId = null,
        string $hexBackgroundColor = ''
    ): ?string {
        $this->lastError = null;
        if (! $this->isAvailable()) {
            $this->setLastError('Google Wallet API is unavailable.');

            return null;
        }

        if (! $this->verifyWalletApiAccess()) {
            if ($this->lastError === null || $this->lastError === '') {
                $this->setLastError('Google Wallet API に接続できません。しばらくしてから再度お試しください。');
            }

            return null;
        }

        $issuerId = config('google_wallet.issuer_id');
        $classSuffix = config('google_wallet.class_suffix', 'membership');
        $classId = $issuerId.'.'.$classSuffix;
        $objectId = $issuerId.'.'.$objectSuffix;
        $maxLen = GoogleWalletApi::MAX_DISPLAY_LENGTH;

        $programName = $this->payloadBuilder->truncateForWallet(
            mb_strimwidth($organizationName, 0, $maxLen, ''),
            $maxLen
        );
        $accountNameDisplay = $accountName !== ''
            ? $this->payloadBuilder->truncateForWallet($accountName, $maxLen)
            : (GoogleWalletApi::ACCOUNT_NAME_FALLBACK_PREFIX.$barcodeValue);
        $accountIdDisplay = $this->payloadBuilder->truncateForWallet($barcodeValue, $maxLen);

        $programLogoUri = $this->resolveProgramLogoUri($projectId);
        $heroImageUri = $this->resolveHeroImageUri($projectId);

        $this->ensureLoyaltyClassExists($classId, $programName, $programLogoUri);

        $payloadParams = [
            'classId' => $classId,
            'objectId' => $objectId,
            'programName' => $programName,
            'programLogoUri' => $programLogoUri,
            'heroImageUri' => $heroImageUri,
            'accountNameDisplay' => $accountNameDisplay,
            'accountIdDisplay' => $accountIdDisplay,
            'barcodeValue' => $barcodeValue,
            'points' => $points,
            'rankLabel' => $rankLabel,
            'hexBackgroundColor' => $hexBackgroundColor,
        ];
        $classExistsOnServer = $this->loyaltyClassExistsOnServer($classId);
        $built = $this->payloadBuilder->build($payloadParams, forSaveJwt: true);

        $credentials = $this->loadCredentials();
        if ($credentials === null) {
            return null;
        }

        $origins = $this->buildOrigins();

        $payload = [
            'loyaltyObjects' => [$built['loyaltyObject']],
        ];
        if (! $classExistsOnServer) {
            $payload['loyaltyClasses'] = [$built['loyaltyClass']];
        }

        Log::info('google-wallet JWT payload mode', [
            'class_id' => $classId,
            'object_id' => $objectId,
            'includes_class' => ! $classExistsOnServer,
            'origins' => $origins,
            'project_id' => $projectId,
        ]);

        $claims = [
            'iss' => $credentials['client_email'],
            'aud' => self::JWT_AUDIENCE,
            'typ' => self::JWT_TYPE_SAVE_TO_WALLET,
            'iat' => time(),
            'origins' => $origins,
            'payload' => $payload,
        ];

        return $this->encodeJwtAndBuildSaveUrl($claims, $credentials['private_key'], $projectId);
    }

    /**
     * JWT を署名し、Add to Google Wallet の URL を返す。失敗時は null。
     *
     * @param  array{
     *     iss: string,
     *     aud: string,
     *     typ: string,
     *     iat: int,
     *     origins: array<int, string>,
     *     payload: array<string, array<int, array<string, string|int|array<string, string|int|array|null>|null>>>
     * }  $claims
     */
    private function encodeJwtAndBuildSaveUrl(array $claims, string $privateKey, ?int $projectId): ?string
    {
        try {
            $token = JWT::encode($claims, $privateKey, self::JWT_ALGORITHM);

            return self::SAVE_URL_PREFIX.$token;
        } catch (\Throwable $e) {
            Log::error('google-wallet JWT encode failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'project_id' => $projectId,
            ]);
            report($e);

            return null;
        }
    }

    /**
     * getMemberData の戻り値が会員かつ member インスタンスを持つか。
     *
     * @param  array{is_member: bool, member?: Member, ...}  $data  MemberService::getMemberData の戻り値
     */
    private function isValidMemberData(array $data): bool
    {
        return isset($data['is_member'])
            && $data['is_member'] === true
            && isset($data['member'])
            && $data['member'] instanceof Member;
    }

    /**
     * Save URL（JWT）発行・UI 表示可否。Wallet Objects API の有効化は必須にしない。
     * JWT に class/object を同梱するため、API 403 でも追加リンク自体は発行できる。
     */
    public function isAvailable(): bool
    {
        if (config('google_wallet.enabled', false) !== true) {
            return false;
        }

        return $this->canSyncExistingObjects();
    }

    /** 既存 LoyaltyObject の REST 更新が可能か（API 有効化が必要）。 */
    public function isWalletApiAccessible(): bool
    {
        if (! $this->canSyncExistingObjects()) {
            return false;
        }

        return $this->verifyWalletApiAccess();
    }

    /** 既存 LoyaltyObject の更新（ポイント同期）が可能か。UI 向け enabled フラグは見ない。 */
    public function canSyncExistingObjects(): bool
    {
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
            $reasons[] = 'Service account credentials file not found or not readable: '.$path;
        }
        if ($reasons === [] && ! $this->isWalletApiAccessible()) {
            if ($this->lastError !== null && $this->lastError !== '') {
                $reasons[] = $this->lastError;
            } else {
                $reasons[] = 'Google Wallet API is inaccessible. Check credentials and Pay & Wallet Console setup.';
            }
        }

        return $reasons;
    }

    private function loyaltyClassExistsOnServer(string $classId): bool
    {
        $token = $this->getWalletApiAccessToken();
        if ($token === null) {
            return false;
        }

        $response = Http::withToken($token)
            ->get(self::WALLET_OBJECTS_BASE.'/loyaltyClass/'.$classId);

        return $response->successful();
    }

    private function verifyWalletApiAccess(): bool
    {
        if (Cache::get(self::API_HEALTH_CACHE_KEY) === true) {
            return true;
        }

        $token = $this->getWalletApiAccessToken();
        if ($token === null) {
            $this->setLastError('OAuth token request failed. credentials.json を確認してください。');

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
            $this->setLastError($message);
            Log::warning('[GoogleWallet] API access check failed', [
                'class_id' => $classId,
                'status' => $response->status(),
                'service_account' => $this->getServiceAccountEmail(),
                'message' => $message,
            ]);

            return false;
        }

        $this->setLastError('Google Wallet API check failed (HTTP '.$response->status().').');

        return false;
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
        $serviceAccount = $this->getServiceAccountEmail() ?? 'credentials.json の client_email';

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

    /**
     * サービスアカウント JSON から client_email と private_key を読む。
     *
     * @return array{client_email: string, private_key: string}|null
     */
    private function loadCredentials(): ?array
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

    private function getMemberService(): MemberService
    {
        return app(MemberService::class);
    }

    private function resolveProgramLogoUri(?int $projectId): string
    {
        $default = config('google_wallet.program_logo_uri', self::DEFAULT_LOGO_URI);

        return $projectId !== null
            ? (ShopSetting::getWalletGoogleProgramLogoUrl($projectId) ?? $default)
            : $default;
    }

    private function resolveHeroImageUri(?int $projectId): string
    {
        $default = config('google_wallet.hero_image_uri', self::DEFAULT_LOGO_URI);

        return $projectId !== null
            ? (ShopSetting::getWalletGoogleHeroImageUrl($projectId) ?? $default)
            : $default;
    }

    /**
     * @return array<int, string>
     */
    private function buildOrigins(): array
    {
        $origins = config('google_wallet.origins', []);
        if (! is_array($origins)) {
            $origins = $origins === '' ? [] : [$origins];
        }
        $origins = array_values(array_unique(array_filter(array_map('trim', $origins))));

        if (! app()->runningInConsole() && request() !== null) {
            $requestOrigin = request()->getSchemeAndHttpHost();
            if ($requestOrigin !== '' && ! in_array($requestOrigin, $origins, true)) {
                $origins[] = $requestOrigin;
            }
        }
        if ($origins === []) {
            $origins = [$this->getFallbackOrigin()];
        }

        foreach (['https://liff.line.me', 'https://miniapp.line.me'] as $liffOrigin) {
            if (! in_array($liffOrigin, $origins, true)) {
                $origins[] = $liffOrigin;
            }
        }

        return $origins;
    }

    /**
     * config(app.url) からオリジン文字列を組み立てる。
     */
    private function getFallbackOrigin(): string
    {
        $appUrl = config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST);
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);

        if (is_string($host) && $host !== '') {
            return $scheme.'://'.$host.($port !== null && $port !== false ? ':'.$port : '');
        }

        return 'http://localhost';
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
                $this->setLastError($message);
                Log::warning('[GoogleWallet] loyaltyClass access denied', [
                    'class_id' => $classId,
                    'service_account' => $this->getServiceAccountEmail(),
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
}

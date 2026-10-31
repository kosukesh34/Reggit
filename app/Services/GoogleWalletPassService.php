<?php

namespace App\Services;

use App\Domain\Settings\Repositories\WalletSettings;
use App\Enums\GoogleWalletApi;
use App\Models\Member;
use App\Models\User;
use App\Services\GoogleWallet\GoogleWalletCredentials;
use App\Services\GoogleWallet\GoogleWalletLoyaltyClassApi;
use App\Services\GoogleWallet\GoogleWalletLoyaltyPayloadBuilder;
use App\Services\Member\MemberDataReader;
use App\Services\Member\MemberPointLedger;
use App\Util\AdminContext;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\ConnectionException;
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

    private const WALLET_OBJECTS_BASE = 'https://walletobjects.googleapis.com/walletobjects/v1';

    private const JWT_AUDIENCE = 'google';

    private const JWT_TYPE_SAVE_TO_WALLET = 'savetowallet';

    private const JWT_ALGORITHM = 'RS256';

    /** Google Wallet API が取得できる公開 HTTPS 画像（旧 codelab URL は 2025 以降無効） */
    private const DEFAULT_LOGO_URI = 'https://www.gstatic.com/images/branding/product/2x/wallet_96dp.png';

    private ?string $lastError = null;

    public function __construct(
        private readonly GoogleWalletLoyaltyPayloadBuilder $payloadBuilder,
        private readonly WalletSettings $walletSettings,
        private readonly MemberDataReader $memberData,
        private readonly MemberCardAppearanceService $memberCardAppearanceService,
        private readonly GoogleWalletCredentials $credentials,
        private readonly GoogleWalletLoyaltyClassApi $loyaltyClasses,
        private readonly MemberPointLedger $pointLedger,
    ) {}

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getServiceAccountEmail(): ?string
    {
        $credentials = $this->credentials->load();

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
        $data = $this->memberData->getMemberData($user);
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
        $points = $data['points'] ?? $this->pointLedger->total($member);
        $rankLabel = $data['current_rank_label'] ?? $member->rank ?? '';
        $accountName = $user->name ?? '';
        $projectId = $project !== null ? $project->id : (isset($user->project_id) ? (int) $user->project_id : null);
        $appearance = $this->memberCardAppearanceService->resolveForMember($member);

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

        $appearance = $this->memberCardAppearanceService->resolveForMember($member);
        $project = $member->project;
        $projectId = (int) ($member->project_id ?? 0);
        $organizationName = $project !== null ? $project->name : config('app.name', 'Reggit');
        $barcodeValue = $member->member_number ?? (string) $member->id;
        $objectSuffix = 'member-'.(string) $member->id.'-b';
        $points = (int) $this->pointLedger->total($member);
        $rankLabel = (string) ($appearance['rank_label'] ?? $member->rank ?? '');
        $accountName = (string) ($user->name ?? '');

        $classId = $this->classIdForProject($projectId);
        $objectId = $this->objectIdForProject($objectSuffix, $projectId);

        $programLogoUri = $this->resolveProgramLogoUri($projectId > 0 ? $projectId : null);
        $heroImageUri = $this->resolveHeroImageUri($projectId > 0 ? $projectId : null);

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

        $token = $this->credentials->accessToken();
        if ($token === null) {
            return false;
        }

        // Saved legacy passes keep their class; only new passes use tenant-specific IDs.
        $legacyPayload = $walletObject;
        unset($legacyPayload['id'], $legacyPayload['classId']);
        $legacyId = config('google_wallet.issuer_id').'.'.$objectSuffix;
        try {
            $legacy = Http::withToken($token)->patch(self::WALLET_OBJECTS_BASE.'/loyaltyObject/'.$legacyId, $legacyPayload);
            $legacySucceeded = $legacy->successful() || $legacy->status() === 404;
            if (! $legacySucceeded) {
                Log::warning('google-wallet legacy object update failed', ['objectId' => $legacyId, 'status' => $legacy->status()]);
            }
        } catch (ConnectionException) {
            $legacySucceeded = false;
            Log::warning('google-wallet legacy object connection failed', ['objectId' => $legacyId]);
        }
        if (! $this->ensureLoyaltyClassExists($classId, $organizationName, $programLogoUri)) {
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

            return $legacySucceeded;
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

        return $legacySucceeded;
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

        $classId = $this->classIdForProject($projectId);
        $objectId = $this->objectIdForProject($objectSuffix, $projectId);
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

        $credentials = $this->credentials->load();
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
     * @param  array{is_member: bool, member?: Member, ...}  $data  MemberDataReader::getMemberData の戻り値
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

    private function classIdForProject(?int $projectId): string
    {
        return config('google_wallet.issuer_id').'.'.config('google_wallet.class_suffix', 'membership')
            .($projectId !== null && $projectId > 0 ? '-project-'.$projectId : '');
    }

    private function objectIdForProject(string $suffix, ?int $projectId): string
    {
        return config('google_wallet.issuer_id').'.'.$suffix
            .($projectId !== null && $projectId > 0 ? '-p'.$projectId : '');
    }

    private function resolveProgramLogoUri(?int $projectId): string
    {
        $default = config('google_wallet.program_logo_uri', self::DEFAULT_LOGO_URI);

        return $projectId !== null
            ? ($this->walletSettings->getWalletGoogleProgramLogoUrl($projectId) ?? $default)
            : $default;
    }

    private function resolveHeroImageUri(?int $projectId): string
    {
        $default = config('google_wallet.hero_image_uri', self::DEFAULT_LOGO_URI);

        return $projectId !== null
            ? ($this->walletSettings->getWalletGoogleHeroImageUrl($projectId) ?? $default)
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

    private function loyaltyClassExistsOnServer(string $classId): bool
    {
        return $this->loyaltyClasses->exists($classId);
    }

    private function verifyWalletApiAccess(): bool
    {
        $ok = $this->loyaltyClasses->verifyAccess();
        $this->adoptApiError();

        return $ok;
    }

    /**
     * LoyaltyClass が存在すれば true、存在しなければ REST で作成して true、失敗時は false。
     * クラスが既に存在すれば JWT で Object のみ送るため、createSaveUrl 内で毎回呼ぶ。
     */
    public function ensureLoyaltyClassExists(string $classId, string $programName, string $programLogoUri): bool
    {
        $ok = $this->loyaltyClasses->ensure($classId, $programName, $programLogoUri);
        $this->adoptApiError();

        return $ok;
    }

    /** API 側で失敗理由が分かったときだけ、この呼び出しのエラーとして残す */
    private function adoptApiError(): void
    {
        $error = $this->loyaltyClasses->takeError();
        if ($error !== null) {
            $this->setLastError($error);
        }
    }
}

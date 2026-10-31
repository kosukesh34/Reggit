<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Services\AppleWallet\PassAssets;
use App\Services\AppleWallet\PassCertificates;
use App\Services\AppleWallet\PassPackager;
use App\Services\Member\MemberDataReader;
use App\Util\AdminContext;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Support\Facades\File;

/**
 * Apple Wallet 用 .pkpass を組み立てるサービス。
 * .pkpass は Apple の仕様で「署名付き ZIP アーカイブ」（pass.json + manifest.json + signature + 画像）のため、
 * 最終的に ZIP 形式で出力する必要があります。
 */
class WalletPassService
{
    private ?string $lastError = null;

    public function __construct(
        private MemberDataReader $memberData,
        private WalletPassAuthTokenService $walletPassAuthTokenService,
        private MemberCardAppearanceService $memberCardAppearanceService,
        private PassCertificates $certificates,
        private PassAssets $assets,
        private PassPackager $packager,
    ) {}

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function isAvailable(): bool
    {
        if (config('wallet_pass.enabled', false) !== true) {
            return false;
        }

        return $this->getUnavailabilityReasons() === [];
    }

    /**
     * エンドユーザー向けの短いメッセージ（サーバーパス等は含めない）。
     *
     * @return array<int, string>
     */
    public function getPublicUnavailabilityMessages(): array
    {
        $reasons = $this->getUnavailabilityReasons();
        if ($reasons === []) {
            $lastError = $this->getLastError();
            if ($lastError !== null && $lastError !== '') {
                LogHelper::warning('[WalletPass] generation failed', ['error' => $lastError]);

                return ['会員証の生成に失敗しました。しばらくしてから再度お試しください。'];
            }

            return ['会員証の生成に失敗しました。しばらくしてから再度お試しください。'];
        }

        LogHelper::warning('[WalletPass] unavailable', ['reasons' => $reasons]);

        return ['Apple Wallet を現在ご利用いただけません。しばらくしてから再度お試しください。'];
    }

    private function setLastError(string $message): void
    {
        $this->lastError = $message;
    }

    /**
     * Apple Wallet 用パス（.pkpass）を組み立ててバイナリで返す。
     * 失敗時は null を返し、getLastError() で理由を取得できる。
     *
     * @param  array<string, string|int|float|bool|null>  $extraFields  例: ['points' => 100, 'rank_label' => 'ゴールド', 'rank_key' => 'gold']
     * @param  int|null  $projectId  プロジェクトID（指定時は管理画面でアップロードした画像を優先）
     */
    public function buildMembershipPass(
        string $barcodeMessage,
        string $organizationName,
        string $serialNumber,
        array $extraFields = [],
        ?int $projectId = null,
        ?Member $member = null
    ): ?string {
        $this->lastError = null;
        $workDir = $this->createWorkDir();
        try {
            $this->writePassJsonFromParams($workDir, $barcodeMessage, $organizationName, $serialNumber, $extraFields, $member);
            $appearance = is_array($extraFields['appearance'] ?? null) ? $extraFields['appearance'] : null;
            $this->assets->copyInto(
                $workDir,
                isset($extraFields['rank_key']) ? (string) $extraFields['rank_key'] : 'bronze',
                $appearance['gradient_stops'] ?? null,
                $projectId,
            );

            return $this->packager->package($workDir);
        } catch (\Throwable $e) {
            $this->setLastError($e->getMessage());

            return null;
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /**
     * 既に解決済みの User で .pkpass を生成する（UseCase から呼ぶ用）。project は AdminContext に設定済みであること。
     */
    public function generateFromUser(User $user): ?string
    {
        $data = $this->memberData->getMemberData($user);

        if (! isset($data['is_member']) || $data['is_member'] === false || ! isset($data['member']) || $data['member'] === null) {
            $this->setLastError('会員情報が見つかりません。');

            return null;
        }

        $member = $data['member'];
        if (! ($member instanceof Member)) {
            $this->setLastError('会員データが不正です。');

            return null;
        }

        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', 'Reggit');
        $barcodeMessage = $member->member_number ?? (string) $member->id;
        $serialNumber = 'member-'.(string) $member->id;

        $extraFields = [
            'points' => $data['points'] ?? $member->getTotalPointsFromTransactions(),
            'rank_label' => $data['current_rank_label'] ?? $member->rank ?? '',
            'rank_key' => $data['current_rank'] ?? $member->rank ?? 'bronze',
            'appearance' => $this->memberCardAppearanceService->resolveForMember($member),
        ];

        $projectId = $project !== null ? $project->id : null;

        return $this->buildMembershipPass($barcodeMessage, $organizationName, $serialNumber, $extraFields, $projectId, $member);
    }

    /**
     * 指定ユーザーで .pkpass を生成する（トークン経由の外部ブラウザ用。project は AdminContext に設定済みであること）
     */
    public function generateForUser(int $userId): ?string
    {
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || $projectId === false) {
            $this->setLastError('プロジェクトが特定できません。');

            return null;
        }
        $user = User::where('id', $userId)->where('project_id', $projectId)->first();
        if ($user === null) {
            $this->setLastError('ユーザーが見つかりません。');

            return null;
        }
        $data = $this->memberData->getMemberData($user);
        if (! isset($data['is_member']) || $data['is_member'] === false || ! isset($data['member']) || $data['member'] === null) {
            $this->setLastError('会員情報が見つかりません。');

            return null;
        }
        $member = $data['member'];
        if (! ($member instanceof Member)) {
            $this->setLastError('会員データが不正です。');

            return null;
        }
        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', 'Reggit');
        $barcodeMessage = $member->member_number ?? (string) $member->id;
        $serialNumber = 'member-'.(string) $member->id;
        $extraFields = [
            'points' => $data['points'] ?? $member->getTotalPointsFromTransactions(),
            'rank_label' => $data['current_rank_label'] ?? $member->rank ?? '',
            'rank_key' => $data['current_rank'] ?? $member->rank ?? 'bronze',
            'appearance' => $this->memberCardAppearanceService->resolveForMember($member),
        ];

        return $this->buildMembershipPass($barcodeMessage, $organizationName, $serialNumber, $extraFields, (int) $projectId, $member);
    }

    public function generateForMember(Member $member): ?string
    {
        $member = $member->fresh(['user', 'project']);
        if ($member === null) {
            $this->setLastError('会員が見つかりません。');

            return null;
        }

        $appearance = $this->memberCardAppearanceService->resolveForMember($member);
        $organizationName = $member->project?->name ?? config('app.name', 'Reggit');
        $barcodeMessage = $member->member_number ?? (string) $member->id;
        $serialNumber = 'member-'.(string) $member->id;
        $extraFields = [
            'points' => $member->getTotalPointsFromTransactions(),
            'rank_label' => $appearance['rank_label'],
            'rank_key' => $appearance['rank_key'],
            'appearance' => $appearance,
        ];

        return $this->buildMembershipPass(
            $barcodeMessage,
            $organizationName,
            $serialNumber,
            $extraFields,
            (int) $member->project_id,
            $member
        );
    }

    /** ランク別カード色（ウェブ会員証のグラデーション末尾色に合わせた背景・文字・ラベル） @return array{bg: string, fg: string, label: string} */
    private function getRankColors(string $rankKey): array
    {
        $map = [
            'bronze' => ['bg' => 'rgb(92, 46, 10)', 'fg' => 'rgb(255, 255, 255)', 'label' => 'rgb(255, 235, 210)'],
            'silver' => ['bg' => 'rgb(144, 144, 144)', 'fg' => 'rgb(33, 33, 33)', 'label' => 'rgb(80, 80, 80)'],
            'gold' => ['bg' => 'rgb(184, 134, 11)', 'fg' => 'rgb(50, 42, 25)', 'label' => 'rgb(255, 248, 220)'],
            'platinum' => ['bg' => 'rgb(168, 168, 176)', 'fg' => 'rgb(35, 40, 50)', 'label' => 'rgb(95, 105, 120)'],
        ];
        $key = strtolower($rankKey);

        return $map[$key] ?? $map['bronze'];
    }

    /**
     * buildMembershipPass 用の pass.json を書き出す（Member に依存しない）
     *
     * @param  array<string, string|int|float|bool|null>  $extraFields  パス用追加フィールド（points, rank_label, rank_key 等）
     */
    private function writePassJsonFromParams(
        string $dir,
        string $barcodeMessage,
        string $organizationName,
        string $serialNumber,
        array $extraFields,
        ?Member $member = null
    ): void {
        $points = isset($extraFields['points']) ? (string) $extraFields['points'] : '0';
        $rankLabel = isset($extraFields['rank_label']) ? (string) $extraFields['rank_label'] : '';
        $rankKey = isset($extraFields['rank_key']) ? (string) $extraFields['rank_key'] : 'bronze';
        $appearance = is_array($extraFields['appearance'] ?? null) ? $extraFields['appearance'] : null;
        $colors = $appearance['colors'] ?? $this->getRankColors($rankKey);

        $pass = [
            'formatVersion' => 1,
            'passTypeIdentifier' => config('wallet_pass.type_id'),
            'teamIdentifier' => config('wallet_pass.team_id'),
            'organizationName' => $organizationName,
            'serialNumber' => $serialNumber,
            'description' => '会員証',
            'backgroundColor' => $colors['bg'],
            'foregroundColor' => $colors['fg'],
            'labelColor' => $colors['label'],
            'storeCard' => [
                'primaryFields' => [
                    ['key' => 'points', 'label' => 'ポイント', 'value' => $points],
                ],
                'secondaryFields' => [
                    ['key' => 'rank', 'label' => 'ランク', 'value' => $rankLabel],
                ],
                'auxiliaryFields' => [
                    ['key' => 'member_number', 'label' => '会員番号', 'value' => $barcodeMessage],
                ],
            ],
            'barcode' => [
                'message' => $barcodeMessage,
                'format' => 'PKBarcodeFormatCode128',
                'messageEncoding' => 'iso-8859-1',
                'altText' => $barcodeMessage,
            ],
        ];

        $webServiceUrl = trim((string) config('wallet_pass.web_service_url', ''));
        if ($webServiceUrl !== '' && $member instanceof Member) {
            $normalizedWebServiceUrl = rtrim($webServiceUrl, '/').'/';
            if (filter_var($normalizedWebServiceUrl, FILTER_VALIDATE_URL)
                && str_starts_with(strtolower($normalizedWebServiceUrl), 'https://')) {
                $pass['webServiceURL'] = $normalizedWebServiceUrl;
                $pass['authenticationToken'] = $this->walletPassAuthTokenService->createForMember($member);
            }
        }

        File::put($dir.'/pass.json', json_encode($pass, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function createWorkDir(): string
    {
        $dir = storage_path('app/wallet_pass/build/'.uniqid('pass_', true));
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Wallet Pass が利用できない理由の一覧を返す（503 用）。config やファイルの状態をチェックする。
     *
     * @return array<int, string>
     */
    public function getUnavailabilityReasons(): array
    {
        $reasons = [];
        if (config('wallet_pass.enabled', false) !== true) {
            $reasons[] = 'WALLET_PASS_ENABLED is not true in .env';
        }
        if (StrHelper::isEmptyStringOrNull(config('wallet_pass.team_id', null))) {
            $reasons[] = 'WALLET_PASS_TEAM_ID is not set in .env';
        }
        if (StrHelper::isEmptyStringOrNull(config('wallet_pass.type_id', null))) {
            $reasons[] = 'WALLET_PASS_TYPE_ID is not set in .env';
        }

        return array_merge($reasons, $this->certificates->unavailabilityReasons());
    }
}

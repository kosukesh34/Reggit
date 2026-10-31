<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
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
            $this->ensureAssets($workDir, $extraFields, $projectId);

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

    /** ウェブ会員証と同じ 4 段階グラデーション（0%, 35%, 65%, 100%）@return array<int, array{0: float, 1: int, 2: int, 3: int}> */
    private function getRankStripGradientStops(string $rankKey): array
    {
        $map = [
            'bronze' => [
                [0.00, 201, 139, 74],   // #c98b4a
                [0.35, 160, 82, 45],    // #a0522d
                [0.65, 139, 69, 19],    // #8b4513
                [1.00, 92, 46, 10],     // #5c2e0a
            ],
            'silver' => [
                [0.00, 240, 240, 240], // #f0f0f0
                [0.35, 216, 216, 216],  // #d8d8d8
                [0.65, 192, 192, 192],  // #c0c0c0
                [1.00, 144, 144, 144], // #909090
            ],
            'gold' => [
                [0.00, 255, 244, 176], // #fff4b0
                [0.35, 255, 215, 0],   // #ffd700
                [0.65, 218, 165, 32],  // #daa520
                [1.00, 184, 134, 11],  // #b8860b
            ],
            'platinum' => [
                [0.00, 250, 250, 250], // #fafafa
                [0.35, 232, 232, 232], // #e8e8e8
                [0.65, 212, 212, 216], // #d4d4d8
                [1.00, 168, 168, 176], // #a8a8b0
            ],
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

    /** プロジェクト別アセット（管理画面アップロード）があればそのパス、なければ config の assets_path */
    private function resolveProjectAssetsPath(?int $projectId): string
    {
        if ($projectId !== null) {
            $projectDir = storage_path('app/public/wallet_assets/'.(string) $projectId);
            if (File::isDirectory($projectDir) && (File::isFile($projectDir.'/icon.png') || File::isFile($projectDir.'/logo.png'))) {
                return $projectDir;
            }
        }

        return $this->certificates->resolvePath(config('wallet_pass.assets_path'));
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $extraFields  パス用追加フィールド
     * @param  int|null  $projectId  プロジェクトID（指定時は管理画面アップロード画像を優先）
     */
    private function ensureAssets(string $dir, array $extraFields = [], ?int $projectId = null): void
    {
        $assetsPath = $this->resolveProjectAssetsPath($projectId);
        $rankKey = isset($extraFields['rank_key']) ? (string) $extraFields['rank_key'] : 'bronze';
        $appearance = is_array($extraFields['appearance'] ?? null) ? $extraFields['appearance'] : null;
        $gradientStops = $appearance['gradient_stops'] ?? null;

        $required = [
            'icon.png' => [29, 29],
            'icon@2x.png' => [58, 58],
            'icon@3x.png' => [87, 87],
            'logo.png' => [160, 50],
            'logo@2x.png' => [320, 100],
        ];

        foreach ($required as $name => $size) {
            $src = $assetsPath.'/'.$name;
            $dst = $dir.'/'.$name;
            if (File::isFile($src)) {
                File::copy($src, $dst);
            } else {
                $this->createPlaceholderPng($dst, $size[0], $size[1]);
            }
        }

        $stripSizes = ['strip.png' => [375, 123], 'strip@2x.png' => [750, 246]];
        foreach ($stripSizes as $name => $size) {
            $src = $assetsPath.'/'.$name;
            $srcRank = $assetsPath.'/strip_'.$rankKey.'.png';
            $dst = $dir.'/'.$name;
            if (File::isFile($src)) {
                File::copy($src, $dst);
            } elseif (File::isFile($srcRank)) {
                File::copy($srcRank, $dst);
            } else {
                $this->createStripPlaceholder($dst, $size[0], $size[1], $rankKey, $gradientStops);
            }
        }
    }

    private function createPlaceholderPng(string $path, int $width, int $height): void
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('Wallet Pass の画像生成に GD 拡張が必要です。');
        }
        $im = imagecreatetruecolor($width, $height);
        if ($im === false) {
            throw new \RuntimeException('画像の作成に失敗しました。');
        }
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg);
        imagepng($im, $path);
        imagedestroy($im);
    }

    /** ランク色のストリップ画像を生成（ウェブ会員証と同じ 4 段階グラデーション＋上部光沢で高級感） */
    private function createStripPlaceholder(string $path, int $width, int $height, string $rankKey, ?array $gradientStops = null): void
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('Wallet Pass の画像生成に GD 拡張が必要です。');
        }
        $stops = $gradientStops ?? $this->getRankStripGradientStops($rankKey);
        $im = imagecreatetruecolor($width, $height);
        if ($im === false) {
            throw new \RuntimeException('画像の作成に失敗しました。');
        }
        for ($y = 0; $y < $height; $y++) {
            $t = $height > 0 ? $y / $height : 0;
            $r = $g = $b = 0;
            for ($i = 0; $i < count($stops) - 1; $i++) {
                $a = $stops[$i][0];
                $b0 = $stops[$i + 1][0];
                if ($t >= $a && $t <= $b0) {
                    $local = $b0 > $a ? ($t - $a) / ($b0 - $a) : 0;
                    $r = (int) ($stops[$i][1] + ($stops[$i + 1][1] - $stops[$i][1]) * $local);
                    $g = (int) ($stops[$i][2] + ($stops[$i + 1][2] - $stops[$i][2]) * $local);
                    $b = (int) ($stops[$i][3] + ($stops[$i + 1][3] - $stops[$i][3]) * $local);
                    break;
                }
            }
            if ($r === 0 && $g === 0 && $b === 0) {
                $last = $stops[count($stops) - 1];
                $r = $last[1];
                $g = $last[2];
                $b = $last[3];
            }
            $c = imagecolorallocate($im, min(255, max(0, $r)), min(255, max(0, $g)), min(255, max(0, $b)));
            imageline($im, 0, $y, $width, $y, $c);
        }
        $glossTop = (int) ($height * 0.28);
        for ($y = 0; $y < $glossTop; $y++) {
            $ratio = $glossTop > 0 ? 1 - ($y / $glossTop) : 1;
            $base = $stops[0];
            $r = (int) min(255, $base[1] + (255 - $base[1]) * $ratio * 0.45);
            $g = (int) min(255, $base[2] + (255 - $base[2]) * $ratio * 0.45);
            $b = (int) min(255, $base[3] + (255 - $base[3]) * $ratio * 0.45);
            $c = imagecolorallocate($im, $r, $g, $b);
            imageline($im, 0, $y, $width, $y, $c);
        }
        imagepng($im, $path);
        imagedestroy($im);
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

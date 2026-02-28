<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use App\Util\AdminContext;
use App\Util\StrHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Apple Wallet 用 .pkpass を組み立てるサービス。
 * .pkpass は Apple の仕様で「署名付き ZIP アーカイブ」（pass.json + manifest.json + signature + 画像）のため、
 * 最終的に ZIP 形式で出力する必要があります。
 */
class WalletPassService
{
    private static ?string $lastError = null;

    public function __construct(
        private UserService $userService,
        private MemberService $memberService
    ) {
    }

    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    private function setLastError(string $message): void
    {
        self::$lastError = $message;
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
        ?int $projectId = null
    ): ?string {
        self::$lastError = null;
        $workDir = $this->createWorkDir();
        try {
            $this->writePassJsonFromParams($workDir, $barcodeMessage, $organizationName, $serialNumber, $extraFields);
            $this->ensureAssets($workDir, $extraFields, $projectId);
            $this->writeManifest($workDir);
            $this->signManifest($workDir);
            return $this->createZip($workDir);
        } catch (\Throwable $e) {
            $this->setLastError($e->getMessage());
            return null;
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    /**
     * 会員用の .pkpass バイナリを生成する（リクエストから会員を解決して buildMembershipPass を呼ぶ）
     */
    public function generate(Request $request): ?string
    {
        $user = $this->userService->getOrCreateUser($request);
        $data = $this->memberService->getMemberData($user);

        if (!isset($data['is_member']) || $data['is_member'] === false || !isset($data['member']) || $data['member'] === null) {
            $this->setLastError('会員情報が見つかりません。');
            return null;
        }

        $member = $data['member'];
        if (!($member instanceof Member)) {
            $this->setLastError('会員データが不正です。');
            return null;
        }

        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', '会員証');
        $barcodeMessage = $member->member_number ?? (string) $member->id;
        $serialNumber = 'member-' . (string) $member->id;

        $extraFields = [
            'points' => $data['points'] ?? $member->getTotalPointsFromTransactions(),
            'rank_label' => $data['current_rank_label'] ?? $member->rank ?? '',
            'rank_key' => $data['current_rank'] ?? $member->rank ?? 'bronze',
        ];

        $projectId = $project !== null ? $project->id : null;

        return $this->buildMembershipPass($barcodeMessage, $organizationName, $serialNumber, $extraFields, $projectId);
    }

    /**
     * 既に解決済みの User で .pkpass を生成する（UseCase から呼ぶ用）。project は AdminContext に設定済みであること。
     */
    public function generateFromUser(User $user): ?string
    {
        $data = $this->memberService->getMemberData($user);

        if (!isset($data['is_member']) || $data['is_member'] === false || !isset($data['member']) || $data['member'] === null) {
            $this->setLastError('会員情報が見つかりません。');
            return null;
        }

        $member = $data['member'];
        if (!($member instanceof Member)) {
            $this->setLastError('会員データが不正です。');
            return null;
        }

        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', '会員証');
        $barcodeMessage = $member->member_number ?? (string) $member->id;
        $serialNumber = 'member-' . (string) $member->id;

        $extraFields = [
            'points' => $data['points'] ?? $member->getTotalPointsFromTransactions(),
            'rank_label' => $data['current_rank_label'] ?? $member->rank ?? '',
            'rank_key' => $data['current_rank'] ?? $member->rank ?? 'bronze',
        ];

        $projectId = $project !== null ? $project->id : null;

        return $this->buildMembershipPass($barcodeMessage, $organizationName, $serialNumber, $extraFields, $projectId);
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
        $user = \App\Models\User::where('id', $userId)->where('project_id', $projectId)->first();
        if ($user === null) {
            $this->setLastError('ユーザーが見つかりません。');
            return null;
        }
        $data = $this->memberService->getMemberData($user);
        if (!isset($data['is_member']) || $data['is_member'] === false || !isset($data['member']) || $data['member'] === null) {
            $this->setLastError('会員情報が見つかりません。');
            return null;
        }
        $member = $data['member'];
        if (!($member instanceof Member)) {
            $this->setLastError('会員データが不正です。');
            return null;
        }
        $project = AdminContext::getCurrentProject();
        $organizationName = $project !== null ? $project->name : config('app.name', '会員証');
        $barcodeMessage = $member->member_number ?? (string) $member->id;
        $serialNumber = 'member-' . (string) $member->id;
        $extraFields = [
            'points' => $data['points'] ?? $member->getTotalPointsFromTransactions(),
            'rank_label' => $data['current_rank_label'] ?? $member->rank ?? '',
            'rank_key' => $data['current_rank'] ?? $member->rank ?? 'bronze',
        ];
        return $this->buildMembershipPass($barcodeMessage, $organizationName, $serialNumber, $extraFields, (int) $projectId);
    }

    /** ランク別カード色（ウェブ会員証のグラデーション末尾色に合わせた背景・文字・ラベル） @return array{bg: string, fg: string, label: string} */
    private static function getRankColors(string $rankKey): array
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
    private static function getRankStripGradientStops(string $rankKey): array
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
        array $extraFields
    ): void {
        $points = isset($extraFields['points']) ? (string) $extraFields['points'] : '0';
        $rankLabel = isset($extraFields['rank_label']) ? (string) $extraFields['rank_label'] : '';
        $rankKey = isset($extraFields['rank_key']) ? (string) $extraFields['rank_key'] : 'bronze';
        $colors = self::getRankColors($rankKey);

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

        File::put($dir . '/pass.json', json_encode($pass, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function createWorkDir(): string
    {
        $dir = storage_path('app/wallet_pass/build/' . uniqid('pass_', true));
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        return $dir;
    }

    /** プロジェクト別アセット（管理画面アップロード）があればそのパス、なければ config の assets_path */
    private function resolveProjectAssetsPath(?int $projectId): string
    {
        if ($projectId !== null) {
            $projectDir = storage_path('app/public/wallet_assets/' . (string) $projectId);
            if (File::isDirectory($projectDir) && (File::isFile($projectDir . '/icon.png') || File::isFile($projectDir . '/logo.png'))) {
                return $projectDir;
            }
        }
        return $this->resolvePath(config('wallet_pass.assets_path'));
    }

    /**
     * @param array<string, string|int|float|bool|null> $extraFields パス用追加フィールド
     * @param int|null $projectId プロジェクトID（指定時は管理画面アップロード画像を優先）
     */
    private function ensureAssets(string $dir, array $extraFields = [], ?int $projectId = null): void
    {
        $assetsPath = $this->resolveProjectAssetsPath($projectId);
        $rankKey = isset($extraFields['rank_key']) ? (string) $extraFields['rank_key'] : 'bronze';

        $required = [
            'icon.png' => [29, 29],
            'icon@2x.png' => [58, 58],
            'icon@3x.png' => [87, 87],
            'logo.png' => [160, 50],
            'logo@2x.png' => [320, 100],
        ];

        foreach ($required as $name => $size) {
            $src = $assetsPath . '/' . $name;
            $dst = $dir . '/' . $name;
            if (File::isFile($src)) {
                File::copy($src, $dst);
            } else {
                $this->createPlaceholderPng($dst, $size[0], $size[1]);
            }
        }

        $stripSizes = ['strip.png' => [375, 123], 'strip@2x.png' => [750, 246]];
        foreach ($stripSizes as $name => $size) {
            $src = $assetsPath . '/' . $name;
            $srcRank = $assetsPath . '/strip_' . $rankKey . '.png';
            $dst = $dir . '/' . $name;
            if (File::isFile($src)) {
                File::copy($src, $dst);
            } elseif (File::isFile($srcRank)) {
                File::copy($srcRank, $dst);
            } else {
                $this->createStripPlaceholder($dst, $size[0], $size[1], $rankKey);
            }
        }
    }

    private function createPlaceholderPng(string $path, int $width, int $height): void
    {
        if (!extension_loaded('gd')) {
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
    private function createStripPlaceholder(string $path, int $width, int $height, string $rankKey): void
    {
        if (! extension_loaded('gd')) {
            throw new \RuntimeException('Wallet Pass の画像生成に GD 拡張が必要です。');
        }
        $stops = self::getRankStripGradientStops($rankKey);
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

    private function writeManifest(string $dir): void
    {
        $files = ['pass.json'];
        foreach (['icon.png', 'icon@2x.png', 'icon@3x.png', 'logo.png', 'logo@2x.png', 'strip.png', 'strip@2x.png'] as $f) {
            if (File::isFile($dir . '/' . $f)) {
                $files[] = $f;
            }
        }

        $manifest = [];
        foreach ($files as $name) {
            $path = $dir . '/' . $name;
            $manifest[$name] = sha1_file($path);
        }
        File::put($dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    private function signManifest(string $dir): void
    {
        $certPath = $this->resolvePath(config('wallet_pass.cert_path'));
        $keyPath = $this->resolvePath(config('wallet_pass.key_path'));
        $wwdrPath = $this->resolvePath(config('wallet_pass.wwdr_path'));

        if (!File::isFile($certPath) || !File::isReadable($certPath)) {
            throw new \RuntimeException('Wallet Pass 証明書が見つかりません: ' . $certPath);
        }
        if (!File::isFile($keyPath) || !File::isReadable($keyPath)) {
            throw new \RuntimeException('Wallet Pass 秘密鍵が見つかりません: ' . $keyPath);
        }
        if (!File::isFile($wwdrPath) || !File::isReadable($wwdrPath)) {
            throw new \RuntimeException('WWDR 証明書が見つかりません: ' . $wwdrPath);
        }

        $certPem = $this->ensurePemCertificate($certPath);
        $keyPem = $this->ensurePemKey($keyPath);
        $wwdrPem = $this->ensurePemCertificate($wwdrPath);

        $manifestPath = $dir . '/manifest.json';
        $signaturePath = $dir . '/signature';

        $keyPassphrase = config('wallet_pass.key_passphrase', '');
        $keyUri = 'file://' . str_replace('\\', '/', $keyPem);
        $keyForSign = $keyPassphrase !== '' ? [$keyUri, $keyPassphrase] : $keyUri;

        $headers = [
            'signing-time' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        $certUri = 'file://' . str_replace('\\', '/', $certPem);
        $ok = @openssl_pkcs7_sign(
            $manifestPath,
            $signaturePath,
            $certUri,
            $keyForSign,
            $headers,
            PKCS7_BINARY | PKCS7_DETACHED,
            $wwdrPem
        );

        if ($certPem !== $certPath) {
            @unlink($certPem);
        }
        if ($keyPem !== $keyPath) {
            @unlink($keyPem);
        }
        if ($wwdrPem !== $wwdrPath) {
            @unlink($wwdrPem);
        }

        if ($ok === false) {
            $msg = openssl_error_string() ?: '署名に失敗しました。';
            throw new \RuntimeException('Wallet Pass の署名に失敗しました: ' . $msg);
        }

        $this->stripSignatureToDer($signaturePath);
    }

    private function stripSignatureToDer(string $signaturePath): void
    {
        $content = File::get($signaturePath);
        $begin = 'filename="smime.p7s"';
        $end = '------';
        $start = strpos($content, $begin);
        if ($start === false) {
            return;
        }
        $start += strlen($begin);
        $content = substr($content, $start);
        $endPos = strpos($content, $end);
        if ($endPos !== false) {
            $content = substr($content, 0, $endPos);
        }
        $content = trim($content);
        $binary = base64_decode($content, true);
        if ($binary !== false) {
            File::put($signaturePath, $binary);
        }
    }

    private function ensurePemCertificate(string $path): string
    {
        $content = File::get($path);
        if (str_contains($content, '-----BEGIN')) {
            return $path;
        }
        $pem = '-----BEGIN CERTIFICATE-----' . "\n"
            . chunk_split(base64_encode($content), 64, "\n")
            . '-----END CERTIFICATE-----' . "\n";
        $cert = @openssl_x509_read($pem);
        if ($cert === false) {
            throw new \RuntimeException('証明書の読み込みに失敗しました。PEM 形式か DER 形式の証明書を配置してください: ' . $path);
        }
        $tmp = $path . '.pem.' . uniqid();
        openssl_x509_export_to_file($cert, $tmp);
        openssl_x509_free($cert);
        return $tmp;
    }

    private function ensurePemKey(string $path): string
    {
        $content = File::get($path);
        if (str_contains($content, '-----BEGIN')) {
            return $path;
        }
        $key = @openssl_pkey_get_private('file://' . $path);
        if ($key === false) {
            throw new \RuntimeException('秘密鍵の読み込みに失敗しました。PEM 形式の鍵を配置してください: ' . $path);
        }
        $tmp = $path . '.pem.' . uniqid();
        openssl_pkey_export_to_file($key, $tmp);
        openssl_pkey_free($key);
        return $tmp;
    }

    private function createZip(string $dir): string
    {
        $files = ['pass.json', 'manifest.json', 'signature'];
        foreach (['icon.png', 'icon@2x.png', 'icon@3x.png', 'logo.png', 'logo@2x.png', 'strip.png', 'strip@2x.png'] as $f) {
            if (File::isFile($dir . '/' . $f)) {
                $files[] = $f;
            }
        }

        $zipPath = $dir . '/pass.zip';

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIP の作成に失敗しました。');
            }
            foreach ($files as $name) {
                $zip->addFile($dir . '/' . $name, $name);
            }
            $zip->close();
        } else {
            $this->createZipWithCommand($dir, $zipPath, $files);
        }

        $content = File::get($zipPath);
        @unlink($zipPath);
        return $content;
    }

    /**
     * ZipArchive が無い環境用: システムの zip コマンドで .pkpass 用 ZIP を作成
     */
    private function createZipWithCommand(string $dir, string $zipPath, array $files): void
    {
        $zipName = basename($zipPath);
        $list = implode(' ', array_map('escapeshellarg', $files));
        $cmd = sprintf(
            'cd %s && zip -r -q %s %s',
            escapeshellarg($dir),
            escapeshellarg($zipName),
            $list
        );
        $output = [];
        $ret = 0;
        @exec($cmd . ' 2>&1', $output, $ret);
        if ($ret !== 0 || !File::isFile($zipPath)) {
            throw new \RuntimeException(
                'ZIP の作成に失敗しました。PHP の zip 拡張を有効にするか、システムに zip コマンドをインストールしてください。'
            );
        }
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
        $certPath = $this->resolvePath(config('wallet_pass.cert_path', ''));
        $keyPath = $this->resolvePath(config('wallet_pass.key_path', ''));
        $wwdrPath = $this->resolvePath(config('wallet_pass.wwdr_path', ''));
        if ($certPath !== '' && !is_readable($certPath)) {
            $reasons[] = 'pass.cer not found or not readable: ' . $certPath;
        }
        if ($keyPath !== '' && !is_readable($keyPath)) {
            $reasons[] = 'iOS-Passkey-Key.key not found or not readable: ' . $keyPath;
        }
        if ($wwdrPath !== '' && !is_readable($wwdrPath)) {
            $reasons[] = 'AppleWWDRCAG3.cer not found or not readable: ' . $wwdrPath;
        }
        if ($reasons === []) {
            $lastError = self::getLastError();
            $reasons[] = $lastError !== null && $lastError !== '' ? $lastError : 'Build failed. Check storage/logs/laravel.log';
        }
        return $reasons;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }
        if (str_starts_with($path, '/') || (strlen($path) >= 2 && $path[1] === ':')) {
            return $path;
        }
        return base_path($path);
    }
}

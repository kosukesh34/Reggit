<?php

namespace App\Services\AppleWallet;

use Illuminate\Support\Facades\File;

/** .pkpass に入れる画像を用意する。プロジェクトの画像、共通の画像、ランク色のプレースホルダーの順に使う。 */
class PassAssets
{
    public function __construct(
        private PassCertificates $certificates,
    ) {}

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
     * アイコン・ロゴ・ストリップ画像を作業ディレクトリに置く。無ければプレースホルダーを生成する。
     *
     * @param  array<int, array{0: float, 1: int, 2: int, 3: int}>|null  $gradientStops  会員証デザインのグラデーション（未指定ならランク色）
     * @param  int|null  $projectId  プロジェクトID（指定時は管理画面アップロード画像を優先）
     */
    public function copyInto(string $dir, string $rankKey, ?array $gradientStops, ?int $projectId): void
    {
        $assetsPath = $this->resolveProjectAssetsPath($projectId);

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
}

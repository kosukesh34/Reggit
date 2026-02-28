<?php

namespace App\Services\Admin;

use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Apple Wallet / Google Wallet 用画像アセットの保存・リサイズを担当する。
 */
class WalletAssetService
{
    private const APPLE_ASSETS_DIR = 'wallet_assets';

    private const GOOGLE_ASSETS_DIR = 'wallet_google';

    public function hasAppleAssets(int $projectId): bool
    {
        $base = self::APPLE_ASSETS_DIR . '/' . (string) $projectId;

        return Storage::disk('public')->exists($base . '/icon.png')
            || Storage::disk('public')->exists($base . '/logo.png');
    }

    public function saveAppleAssets(int $projectId, Request $request): bool
    {
        if (! extension_loaded('gd')) {
            return false;
        }
        $base = self::APPLE_ASSETS_DIR . '/' . (string) $projectId;
        Storage::disk('public')->makeDirectory($base);

        if ($request->hasFile('wallet_apple_icon')) {
            $path = $request->file('wallet_apple_icon')->getRealPath();
            $this->resizeAndSaveIcon($path, $base);
        }
        if ($request->hasFile('wallet_apple_logo')) {
            $path = $request->file('wallet_apple_logo')->getRealPath();
            $this->resizeAndSaveLogo($path, $base);
        }

        return true;
    }

    /** @return string|null 保存後の URL、失敗時は null */
    public function saveGoogleAsset(int $projectId, UploadedFile $file, string $name): ?string
    {
        $base = self::GOOGLE_ASSETS_DIR . '/' . (string) $projectId;
        Storage::disk('public')->makeDirectory($base);
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'png';
        if (! in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            $ext = 'png';
        }
        $filename = $name . '.' . $ext;
        $path = $file->storeAs($base, $filename, 'public');
        if ($path === false) {
            return null;
        }

        return asset('storage/' . $path);
    }

    private function resizeAndSaveIcon(string $sourcePath, string $baseDir): void
    {
        $sizes = [
            'icon.png' => 29,
            'icon@2x.png' => 58,
            'icon@3x.png' => 87,
        ];
        $fullPath = storage_path('app/public/' . $baseDir);
        if (! File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }
        foreach ($sizes as $name => $size) {
            $dest = $fullPath . '/' . $name;
            $this->resizeSquarePng($sourcePath, $dest, $size, $size);
        }
    }

    private function resizeAndSaveLogo(string $sourcePath, string $baseDir): void
    {
        $sizes = [
            'logo.png' => [160, 50],
            'logo@2x.png' => [320, 100],
        ];
        $fullPath = storage_path('app/public/' . $baseDir);
        if (! File::isDirectory($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }
        foreach ($sizes as $name => $wH) {
            $dest = $fullPath . '/' . $name;
            $this->resizeSquarePng($sourcePath, $dest, $wH[0], $wH[1]);
        }
    }

    private function resizeSquarePng(string $sourcePath, string $destPath, int $width, int $height): void
    {
        $src = $this->imageCreateFromPath($sourcePath);
        if ($src === false) {
            return;
        }
        $dst = imagecreatetruecolor($width, $height);
        if ($dst === false) {
            imagedestroy($src);

            return;
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $trans = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefill($dst, 0, 0, $trans);
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
        imagepng($dst, $destPath);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /** @return \GdImage|false */
    private function imageCreateFromPath(string $path)
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return false;
        }
    }
}

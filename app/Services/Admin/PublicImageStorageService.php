<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;

/** public disk へ画像を保存し、公開 URL を返す（クーポン／お知らせ／LIFFロゴ共通） */
class PublicImageStorageService
{
    public const DIR_COUPON = 'coupon_images';

    public const DIR_ANNOUNCEMENT = 'announcements';

    public const DIR_FRONTEND_LOGO = 'frontend_logos';

    public const DIR_PRODUCT = 'product_images';

    public const DIR_GALLERY = 'gallery_images';

    public const DIR_STAMP_DEFAULT = 'stamp_defaults';

    public const DIR_COUPON_DEFAULT = 'coupon_defaults';

    public function store(UploadedFile $file, int $projectId, string $directory): string
    {
        $path = $file->store($directory.'/'.$projectId, 'public');
        if ($path === false) {
            throw new \RuntimeException('Failed to store image');
        }

        return asset('storage/'.$path);
    }
}

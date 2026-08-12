<?php

namespace App\UseCases\Admin;

use App\Services\Admin\PublicImageStorageService;
use Illuminate\Http\UploadedFile;

/** 管理画面の公開画像アップロード（クーポン・お知らせ・LIFFロゴなど） */
class AdminSavePublicImageUseCase
{
    public function __construct(
        private PublicImageStorageService $publicImageStorage,
    ) {}

    public function handle(UploadedFile $imageFile, int $projectId, string $directory): string
    {
        return $this->publicImageStorage->store($imageFile, $projectId, $directory);
    }
}

<?php

namespace App\Services\Admin\Data;

use Illuminate\Http\UploadedFile;

final readonly class UpdateHomeImagesInput
{
    /** @param list<UploadedFile> $images @param list<int> $removed */
    public function __construct(
        public int $projectId,
        public ?UploadedFile $headerImage,
        public bool $clearHeader,
        public ?UploadedFile $legacyImage,
        public bool $clearLegacy,
        public array $images,
        public array $removed,
        public bool $galleryPresent,
    ) {}
}

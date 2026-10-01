<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Http\UploadedFile;

final readonly class UpdateAppImagesInput
{
    public function __construct(
        public int $projectId,
        public ?string $headerText,
        public ?UploadedFile $logo = null,
        public bool $clearLogo = false,
        public ?UploadedFile $stampImage = null,
        public bool $clearStampImage = false,
        public ?UploadedFile $couponImage = null,
        public bool $clearCouponImage = false,
        public ?UpdateHomeImagesInput $homeAssets = null,
    ) {}
}

<?php

namespace App\UseCases\Admin\Settings\Data;

use Illuminate\Http\UploadedFile;

final readonly class UpdateHomeSettingsInput
{
    /**
     * @param  list<array<string, scalar|null>>  $tabs
     * @param  array<string, list<string>>|null  $navigation
     * @param  list<UploadedFile>  $homeImages
     * @param  list<int>  $removedHomeImages
     * @param  array<string, string>|null  $nativeColors
     */
    public function __construct(
        public int $projectId,
        public array $tabs,
        public string $menuLayout,
        public string $productLayout,
        public ?array $navigation,
        public ?bool $showMemberCard,
        public ?UploadedFile $headerImage,
        public bool $clearHeaderImage,
        public ?UploadedFile $homeImage,
        public bool $clearHomeImage,
        public array $homeImages,
        public array $removedHomeImages,
        public bool $homeImagesPresent,
        public ?string $headerBackground = null,
        public ?array $nativeColors = null,
    ) {}
}

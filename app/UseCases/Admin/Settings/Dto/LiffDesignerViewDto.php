<?php

namespace App\UseCases\Admin\Settings\Dto;

class LiffDesignerViewDto
{
    /**
     * @param array<string, mixed> $layouts
     * @param array<string, mixed> $templates
     * @param array<string, mixed> $tabSettings
     * @param array<string, bool>  $featureFlags
     */
    public function __construct(
        public readonly array $layouts,
        public readonly array $templates,
        public readonly string $customCss,
        public readonly array $tabSettings,
        public readonly array $featureFlags = []
    ) {
    }
}

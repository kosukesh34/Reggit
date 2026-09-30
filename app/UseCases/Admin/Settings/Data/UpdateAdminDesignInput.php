<?php

namespace App\UseCases\Admin\Settings\Data;

final readonly class UpdateAdminDesignInput
{
    public function __construct(
        public int $projectId,
        public string $sidebarColor,
        public string $headerColor,
        public string $primaryColor,
    ) {}
}

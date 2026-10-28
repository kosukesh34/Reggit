<?php

namespace App\UseCases\Admin\AcquisitionSource;

use App\Services\Admin\AcquisitionSourceAdminService;

class AdminAcquisitionSourceStoreUseCase
{
    public function __construct(private readonly AcquisitionSourceAdminService $sources) {}

    public function handle(int $projectId, string $name, string $landingTab): void
    {
        $this->sources->create($projectId, $name, $landingTab);
    }
}

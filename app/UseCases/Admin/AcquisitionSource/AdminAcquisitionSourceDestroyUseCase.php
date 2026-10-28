<?php

namespace App\UseCases\Admin\AcquisitionSource;

use App\Services\Admin\AcquisitionSourceAdminService;

class AdminAcquisitionSourceDestroyUseCase
{
    public function __construct(private readonly AcquisitionSourceAdminService $sources) {}

    public function handle(int $projectId, int $sourceId): void
    {
        $this->sources->delete($projectId, $sourceId);
    }
}

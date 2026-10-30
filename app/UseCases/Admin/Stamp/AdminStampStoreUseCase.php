<?php

namespace App\UseCases\Admin\Stamp;

use App\Models\StampCard;
use App\Services\Admin\StampAdminService;

class AdminStampStoreUseCase
{
    private StampAdminService $stampAdminService;

    public function __construct(StampAdminService $stampAdminService)
    {
        $this->stampAdminService = $stampAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $projectId, array $validated): StampCard
    {
        return $this->stampAdminService->create($projectId, $validated);
    }
}

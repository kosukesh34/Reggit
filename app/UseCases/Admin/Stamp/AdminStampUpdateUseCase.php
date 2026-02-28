<?php

namespace App\UseCases\Admin\Stamp;

use App\Services\Admin\StampAdminService;

class AdminStampUpdateUseCase
{
    private StampAdminService $stampAdminService;

    public function __construct(StampAdminService $stampAdminService)
    {
        $this->stampAdminService = $stampAdminService;
    }

    /** @param array<string, mixed> $validated */
    public function handle(int $id, array $validated): void
    {
        $this->stampAdminService->update($id, $validated);
    }
}

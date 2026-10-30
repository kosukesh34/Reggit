<?php

namespace App\UseCases\Admin\Stamp;

use App\Services\Admin\StampAdminService;

class AdminStampImagesUpdateUseCase
{
    public function __construct(
        private StampAdminService $stampAdminService,
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(int $id, array $validated): void
    {
        $this->stampAdminService->updateImages($id, $validated);
    }
}

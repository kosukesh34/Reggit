<?php

namespace App\UseCases\Admin\Stamp;

use App\Services\Admin\StampAdminService;

class AdminStampDeleteUseCase
{
    private StampAdminService $stampAdminService;

    public function __construct(StampAdminService $stampAdminService)
    {
        $this->stampAdminService = $stampAdminService;
    }

    public function handle(int $id): void
    {
        $this->stampAdminService->delete($id);
    }
}

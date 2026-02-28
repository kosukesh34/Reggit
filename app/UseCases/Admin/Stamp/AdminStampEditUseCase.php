<?php

namespace App\UseCases\Admin\Stamp;

use App\Models\StampCard;
use App\Services\Admin\StampAdminService;

class AdminStampEditUseCase
{
    private StampAdminService $stampAdminService;

    public function __construct(StampAdminService $stampAdminService)
    {
        $this->stampAdminService = $stampAdminService;
    }

    public function handle(int $id): StampCard
    {
        return $this->stampAdminService->findOrFail($id);
    }
}

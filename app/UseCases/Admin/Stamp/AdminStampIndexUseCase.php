<?php

namespace App\UseCases\Admin\Stamp;

use App\Services\Admin\StampAdminService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminStampIndexUseCase
{
    private const PER_PAGE = 20;

    private StampAdminService $stampAdminService;

    public function __construct(StampAdminService $stampAdminService)
    {
        $this->stampAdminService = $stampAdminService;
    }

    public function handle(): LengthAwarePaginator
    {
        return $this->stampAdminService->getPaginatedList(self::PER_PAGE);
    }
}

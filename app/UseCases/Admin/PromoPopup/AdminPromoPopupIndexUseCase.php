<?php

namespace App\UseCases\Admin\PromoPopup;

use App\Models\PromoPopup;
use App\Services\Admin\PromoPopupAdminService;
use Illuminate\Database\Eloquent\Collection;

class AdminPromoPopupIndexUseCase
{
    public function __construct(private readonly PromoPopupAdminService $popups) {}

    /** @return Collection<int, PromoPopup> */
    public function handle(int $projectId): Collection
    {
        return $this->popups->list($projectId);
    }
}

<?php

namespace App\UseCases\Admin\PromoPopup;

use App\Services\Admin\PromoPopupAdminService;

class AdminPromoPopupDestroyUseCase
{
    public function __construct(private readonly PromoPopupAdminService $popups) {}

    public function handle(int $projectId, int $popupId): void
    {
        $this->popups->find($projectId, $popupId)->delete();
    }
}

<?php

namespace App\UseCases\Admin\PromoPopup;

use App\Services\Admin\PromoPopupAdminService;

class AdminPromoPopupDuplicateUseCase
{
    public function __construct(private readonly PromoPopupAdminService $popups) {}

    /** @return int 複製したポップアップのID */
    public function handle(int $projectId, int $popupId): int
    {
        return (int) $this->popups->duplicate($this->popups->find($projectId, $popupId))->id;
    }
}

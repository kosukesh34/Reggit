<?php

namespace App\UseCases\Admin\PromoPopup;

use App\Services\Admin\PromoPopupAdminService;
use App\UseCases\Admin\PromoPopup\Data\PromoPopupInput;

class AdminPromoPopupUpdateUseCase
{
    public function __construct(private readonly PromoPopupAdminService $popups) {}

    public function handle(int $projectId, int $popupId, PromoPopupInput $input): void
    {
        $this->popups->update($this->popups->find($projectId, $popupId), $input->attributes(), $input->image, $input->removeImage);
    }
}

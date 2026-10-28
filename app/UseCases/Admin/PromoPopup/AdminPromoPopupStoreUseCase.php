<?php

namespace App\UseCases\Admin\PromoPopup;

use App\Services\Admin\PromoPopupAdminService;
use App\UseCases\Admin\PromoPopup\Data\PromoPopupInput;

class AdminPromoPopupStoreUseCase
{
    public function __construct(private readonly PromoPopupAdminService $popups) {}

    public function handle(int $projectId, PromoPopupInput $input): void
    {
        $this->popups->create($projectId, $input->attributes(), $input->image, $input->removeImage);
    }
}

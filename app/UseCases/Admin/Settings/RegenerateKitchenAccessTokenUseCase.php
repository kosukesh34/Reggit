<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\KitchenAccessSettings;
use Illuminate\Support\Str;

class RegenerateKitchenAccessTokenUseCase
{
    public function __construct(
        private KitchenAccessSettings $shopSettingRepository,
    ) {}

    public function handle(int $projectId): string
    {
        $token = Str::random(48);
        $this->shopSettingRepository->setKitchenAccessToken($token, $projectId);

        return $token;
    }
}

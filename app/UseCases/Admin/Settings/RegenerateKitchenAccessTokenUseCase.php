<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use Illuminate\Support\Str;

class RegenerateKitchenAccessTokenUseCase
{
    public function handle(int $projectId): string
    {
        $token = Str::random(48);
        ShopSetting::setKitchenAccessToken($token, $projectId);

        return $token;
    }
}

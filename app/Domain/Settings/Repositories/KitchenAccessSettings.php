<?php

namespace App\Domain\Settings\Repositories;

interface KitchenAccessSettings
{
    public function getKitchenAccessToken(int $projectId): string;

    public function setKitchenAccessToken(?string $token, int $projectId): void;
}

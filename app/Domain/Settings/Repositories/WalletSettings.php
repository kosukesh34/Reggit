<?php

namespace App\Domain\Settings\Repositories;

interface WalletSettings
{
    public function getWalletGoogleProgramLogoUrl(int $projectId): ?string;

    public function setWalletGoogleProgramLogoUrl(?string $url, int $projectId): void;

    public function getWalletGoogleHeroImageUrl(int $projectId): ?string;

    public function setWalletGoogleHeroImageUrl(?string $url, int $projectId): void;
}

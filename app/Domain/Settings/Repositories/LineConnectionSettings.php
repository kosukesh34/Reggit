<?php

namespace App\Domain\Settings\Repositories;

interface LineConnectionSettings
{
    public function getLineLiffId(int $projectId): string;

    public function getLineLiffUrl(int $projectId): string;

    public function getLineChannelAccessToken(int $projectId): string;

    public function hasLineChannelAccessToken(int $projectId): bool;

    public function getLineChannelSecret(int $projectId): string;
}

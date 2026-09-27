<?php

namespace App\Domain\Settings\Repositories;

interface QueueSettings extends SettingValueWriter
{
    public function getQueueAvgServeMinutes(int $projectId): int;

    public function getQueueRequireMembership(int $projectId): bool;

    public function getQueueChannelLine(int $projectId): bool;

    public function getQueueChannelNative(int $projectId): bool;
}

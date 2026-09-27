<?php

namespace App\Domain\Settings\Repositories;

interface SettingValueWriter
{
    public function setValue(string $key, mixed $value, string $type, int $projectId): void;
}

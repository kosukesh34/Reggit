<?php

namespace App\Domain\Settings\Repositories;

interface SettingValueReader
{
    public function getValue(string $key, mixed $default, int $projectId): mixed;
}

<?php

namespace App\UseCases\Admin\Settings\Data;

final class UpdateProfileSettingsInput
{
    /**
     * @param  array<string, array{enabled?: mixed, required?: mixed}>  $rawSmaregiFields
     * @param  list<array{key?: string, label?: string, required?: mixed}>|null  $rawCustomFields
     */
    public function __construct(
        public readonly int $projectId,
        public readonly array $rawSmaregiFields,
        public readonly ?array $rawCustomFields,
    ) {}
}

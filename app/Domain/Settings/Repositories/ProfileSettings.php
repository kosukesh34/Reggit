<?php

namespace App\Domain\Settings\Repositories;

interface ProfileSettings
{
    /** @return array{smaregi_fields: array<string, array{label: string, enabled: bool, required: bool}>, custom_fields: list<array<string, scalar|null>>} */
    public function getProfileFieldSettings(int $projectId): array;

    /** @param array{smaregi_fields: array<string, array{enabled?: bool, required?: bool}>, custom_fields?: list<array<string, mixed>>} $settings */
    public function setProfileFieldSettings(array $settings, int $projectId): void;

    /** @return array{smaregi_fields: array<string, array{label: string, enabled: bool, required: bool}>, custom_fields: list<mixed>} */
    public function defaultProfileFieldSettings(): array;

    public function getMemberRegistrationMode(int $projectId): string;

    public function setMemberRegistrationMode(string $mode, int $projectId): void;
}

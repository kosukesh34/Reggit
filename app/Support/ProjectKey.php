<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * プロジェクトキーの正規化。欠落時にリテラル "default" へ落とさない。
 */
final class ProjectKey
{
    public static function require(string $projectKey): string
    {
        $trimmed = trim($projectKey);
        if ($trimmed === '') {
            throw new InvalidArgumentException('project_key is required.');
        }

        return $trimmed;
    }

    public static function normalize(?string $projectKey): ?string
    {
        if ($projectKey === null) {
            return null;
        }
        $trimmed = trim($projectKey);

        return $trimmed !== '' ? $trimmed : null;
    }

    public static function fromConfig(): ?string
    {
        $configured = config('app.default_project_key');
        if (! is_string($configured)) {
            return null;
        }
        $trimmed = trim($configured);

        return $trimmed !== '' ? $trimmed : null;
    }
}

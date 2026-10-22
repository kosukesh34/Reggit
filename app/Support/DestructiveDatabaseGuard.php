<?php

namespace App\Support;

/**
 * Blocks migrate:fresh / db:wipe etc. against the shared dev DB and production.
 */
final class DestructiveDatabaseGuard
{
    public static function shouldProhibit(?string $database, string $environment, bool $allowDestructiveDb): bool
    {
        if ($allowDestructiveDb) {
            return false;
        }

        if ($environment === 'production') {
            return true;
        }

        return $database === 'reggit';
    }
}

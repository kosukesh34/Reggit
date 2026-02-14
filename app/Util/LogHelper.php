<?php

namespace App\Util;

use Illuminate\Support\Facades\Log;

final class LogHelper
{
    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /** @param array<string, mixed> $context */
    private static function write(string $level, string $message, array $context): void
    {
        if (!array_key_exists('project_id', $context)) {
            $context['project_id'] = AdminContext::getCurrentProjectId() ?? 1;
        }

        Log::{$level}($message, $context);
    }
}

<?php

namespace App\Util;

use Illuminate\Support\Facades\Log;

final class LogHelper
{
    /** @param array<string, string|int|float|bool|array|null> $context ログコンテキスト（キーに対する値はスカラーまたは配列） */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /** @param array<string, string|int|float|bool|array|null> $context ログコンテキスト */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /** @param array<string, string|int|float|bool|array|null> $context ログコンテキスト */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /** @param array<string, string|int|float|bool|array|null> $context ログコンテキスト */
    private static function write(string $level, string $message, array $context): void
    {
        if (!array_key_exists('project_id', $context)) {
            $context['project_id'] = AdminContext::getCurrentProjectId() ?? 1;
        }

        Log::{$level}($message, $context);
    }
}

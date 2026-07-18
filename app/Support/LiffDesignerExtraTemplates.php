<?php

namespace App\Support;

/**
 * 後方互換ラッパー。本体は LiffAppStudioTemplates（ゼロイチ App Studio カタログ）。
 */
class LiffDesignerExtraTemplates
{
    /** @return array<int, array<string, mixed>> */
    public static function tabTemplates(): array
    {
        return array_values(array_filter(
            LiffAppStudioTemplates::catalog(),
            static function (array $tpl): bool {
                return ($tpl['scope'] ?? '') !== 'all_tabs' && isset($tpl['blocks']);
            }
        ));
    }

    /** @return array<int, array<string, mixed>> */
    public static function appleTemplates(): array
    {
        return [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function superPatterns(): array
    {
        return array_values(array_filter(
            LiffAppStudioTemplates::catalog(),
            static function (array $tpl): bool {
                return ($tpl['scope'] ?? '') === 'all_tabs';
            }
        ));
    }
}

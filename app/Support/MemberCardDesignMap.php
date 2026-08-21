<?php

namespace App\Support;

/**
 * 旧 member_card_design キーと新 membership.template の対応。
 */
final class MemberCardDesignMap
{
    /** @var list<string> */
    public const DESIGNS = [
        'default',
        'compact',
        'ticket',
        'minimal_line',
        'split_banner',
        'logo_header',
        'logo_top',
        'wallet',
        'emblem',
        'logo_center',
    ];

    /** @var array<string, string> */
    private const DESIGN_TO_TEMPLATE = [
        'default' => 'classic',
        'compact' => 'simple',
        'ticket' => 'ticket',
        'minimal_line' => 'minimal',
        'split_banner' => 'split',
        'logo_header' => 'barcodeFocus',
        'logo_top' => 'pointFocus',
        'wallet' => 'wallet',
        'emblem' => 'pass',
        'logo_center' => 'pass',
    ];

    /** @var array<string, string> */
    private const TEMPLATE_TO_DESIGN = [
        'classic' => 'default',
        'simple' => 'compact',
        'ticket' => 'ticket',
        'minimal' => 'minimal_line',
        'split' => 'split_banner',
        'barcodeFocus' => 'logo_header',
        'pointFocus' => 'logo_top',
        'wallet' => 'wallet',
        'pass' => 'emblem',
    ];

    public static function templateForDesign(string $design): ?string
    {
        return self::DESIGN_TO_TEMPLATE[$design] ?? null;
    }

    public static function designForTemplate(string $template): ?string
    {
        return self::TEMPLATE_TO_DESIGN[$template] ?? null;
    }

    public static function isValidDesign(string $design): bool
    {
        return in_array($design, self::DESIGNS, true);
    }
}

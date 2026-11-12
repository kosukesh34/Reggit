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

    public static function templateForDesign(string $design): ?string
    {
        return self::DESIGN_TO_TEMPLATE[$design] ?? null;
    }

    /**
     * LIFF 設定 _global の app_customization.membership.template を会員証デザインに合わせる。
     *
     * @param  array<string, mixed>  $global
     * @return array<string, mixed>
     */
    public static function withTemplate(array $global, string $design): array
    {
        $template = self::templateForDesign($design);
        if ($template === null) {
            return $global;
        }
        $customization = $global['app_customization'] ?? [];
        if (is_string($customization)) {
            $decoded = json_decode($customization, true);
            $customization = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($customization)) {
            $customization = [];
        }
        $membership = isset($customization['membership']) && is_array($customization['membership'])
            ? $customization['membership']
            : [];
        $membership['template'] = $template;
        $customization['membership'] = $membership;
        $global['app_customization'] = $customization;

        return $global;
    }

    public static function isValidDesign(string $design): bool
    {
        return in_array($design, self::DESIGNS, true);
    }
}

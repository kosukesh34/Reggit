<?php

namespace App\UseCases\Admin\Settings\Data;

use App\Support\LiffDesignerSanitizer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * LIFFデザイン更新ユースケースへの入力。
 * 検証済み・JSON デコード済み。不変。
 *
 * @param  array<string, string|int|float|bool|array|null>  $layouts  レイアウト設定（JSON由来）
 * @param  array<string, string|int|float|bool|array|null>|null  $tabSettings  タブ設定（JSON由来）
 */
class UpdateLiffDesignerInput
{
    public function __construct(
        public readonly array $layouts,
        public readonly string $customCss,
        public readonly ?array $tabSettings,
        public readonly ?array $menuItems = null
    ) {}

    /**
     * Request から検証・サニタイズして Input を生成する。失敗時は ValidationException を投げる。
     */
    public static function fromRequest(Request $request): self
    {
        $layoutsJson = $request->input('layouts_json');
        $decoded = is_string($layoutsJson) || is_array($layoutsJson) ? $layoutsJson : null;
        if (is_string($decoded ?? null)) {
            $decoded = json_decode($decoded, true);
        }
        if (! is_array($decoded)) {
            throw ValidationException::withMessages(['layouts' => 'レイアウトが不正です']);
        }

        $tabSettings = null;
        $tabSettingsJson = $request->input('tab_settings_json');
        if ($tabSettingsJson !== null && $tabSettingsJson !== '') {
            $parsed = json_decode($tabSettingsJson, true);
            if (is_array($parsed)) {
                $tabSettings = $parsed;
            }
        }

        $menuItems = null;
        $menuItemsJson = $request->input('menu_items_json');
        if ($menuItemsJson !== null && $menuItemsJson !== '') {
            $parsed = json_decode($menuItemsJson, true);
            if (is_array($parsed)) {
                $menuItems = LiffDesignerSanitizer::sanitizeMenuItems($parsed);
            }
        }

        try {
            return new self(
                LiffDesignerSanitizer::sanitizeLayouts($decoded),
                LiffDesignerSanitizer::sanitizeCustomCss((string) $request->input('custom_css', '')),
                LiffDesignerSanitizer::sanitizeTabSettings($tabSettings),
                $menuItems
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['liff_designer' => $e->getMessage()]);
        }
    }
}

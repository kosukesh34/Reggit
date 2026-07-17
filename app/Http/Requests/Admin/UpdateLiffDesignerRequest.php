<?php

namespace App\Http\Requests\Admin;

use App\Support\LiffDesignerSanitizer;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class UpdateLiffDesignerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'layouts_json' => 'required|string',
            'custom_css' => 'nullable|string',
            'tab_settings_json' => 'nullable|string',
            'menu_items_json' => 'nullable|string',
            'detail_layouts_json' => 'nullable|string',
        ];
    }

    public function toInput(): UpdateLiffDesignerInput
    {
        $layoutsJson = $this->input('layouts_json');
        $decoded = is_string($layoutsJson) || is_array($layoutsJson) ? $layoutsJson : null;
        if (is_string($decoded ?? null)) {
            $decoded = json_decode($decoded, true);
        }
        if (! is_array($decoded)) {
            throw ValidationException::withMessages(['layouts' => 'レイアウトが不正です']);
        }

        $tabSettings = null;
        $tabSettingsJson = $this->input('tab_settings_json');
        if ($tabSettingsJson !== null && $tabSettingsJson !== '') {
            $parsed = json_decode($tabSettingsJson, true);
            if (is_array($parsed)) {
                $tabSettings = $parsed;
            }
        }

        $menuItems = null;
        $menuItemsJson = $this->input('menu_items_json');
        if ($menuItemsJson !== null && $menuItemsJson !== '') {
            $parsed = json_decode($menuItemsJson, true);
            if (is_array($parsed)) {
                $menuItems = LiffDesignerSanitizer::sanitizeMenuItems($parsed);
            }
        }

        $detailLayouts = null;
        $detailLayoutsJson = $this->input('detail_layouts_json');
        if ($detailLayoutsJson !== null && $detailLayoutsJson !== '') {
            $parsed = json_decode($detailLayoutsJson, true);
            if (! is_array($parsed)) {
                throw ValidationException::withMessages(['detail_layouts' => '詳細レイアウトが不正です']);
            }
            $detailLayouts = $parsed;
        }

        try {
            return new UpdateLiffDesignerInput(
                LiffDesignerSanitizer::sanitizeLayouts($decoded),
                LiffDesignerSanitizer::sanitizeCustomCss((string) $this->input('custom_css', '')),
                LiffDesignerSanitizer::sanitizeTabSettings($tabSettings),
                $menuItems,
                $detailLayouts !== null
                    ? LiffDesignerSanitizer::sanitizeDetailLayouts($detailLayouts)
                    : null
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['liff_designer' => $e->getMessage()]);
        }
    }
}

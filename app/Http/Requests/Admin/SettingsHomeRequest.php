<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsHomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'design_tab' => 'nullable|in:home,header,footer,menu,products,buttons',
            'home_show_member_card' => 'nullable|boolean',
            'native_chrome_colors' => 'nullable|array:header_icon,footer_background,footer_icon,footer_active_icon,footer_active_background,primary_button_background,primary_button_text,secondary_button_background,secondary_button_text',
            'native_chrome_colors.*' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'tabs' => 'nullable|array',
            'tabs.*.tab' => 'nullable|string|max:64',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.icon' => 'nullable|string|max:64',
            'tabs.*.order' => 'nullable|integer|min:0',
            'tabs.*.enabled' => 'nullable|boolean',
            'navigation_header_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'navigation_header_image_clear' => 'nullable|in:0,1',
            'navigation_header_background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'home_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:8192',
            'home_image_clear' => 'nullable|in:0,1',
            'home_images' => 'nullable|array|max:10',
            'home_images.*' => 'image|mimes:png,jpg,jpeg,webp|max:8192',
            'home_image_remove' => 'nullable|array',
            'home_image_remove.*' => 'integer|min:0',
            'home_images_present' => 'nullable|in:0,1',
            'home_menu_layout' => 'required|in:list,grid_3x2,grid_2_columns',
            'product_display_layout' => 'required|in:list_with_image,grid_2_columns,name_only',
            'native_navigation' => 'nullable|array',
            'native_navigation_present' => 'nullable|boolean',
            'native_navigation.header' => 'nullable|array|max:1',
            'native_navigation.header.*' => 'string|max:64',
            'native_navigation.footer' => 'nullable|array|max:5',
            'native_navigation.footer.*' => 'string|max:64',
            'native_navigation.hamburger' => 'nullable|array',
            'native_navigation.hamburger.*' => 'string|max:64',
        ];
    }
}

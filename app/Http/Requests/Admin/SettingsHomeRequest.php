<?php

namespace App\Http\Requests\Admin;

use App\UseCases\Admin\Settings\Data\UpdateHomeSettingsInput;
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
            'home_gallery' => 'nullable|array',
            'home_gallery.*' => 'array:title,description,sort_order',
            'home_gallery.*.title' => 'nullable|string|max:150',
            'home_gallery.*.description' => 'nullable|string|max:1000',
            'home_gallery.*.sort_order' => 'required|integer|min:0|max:999',
            'home_content' => 'nullable|array:layout,image_limit,actions',
            'home_content.layout' => 'required_with:home_content|in:legacy,gallery,member_news,buttons_gallery',
            'home_content.image_limit' => 'nullable|integer|min:1|max:1000',
            'home_content.actions' => 'nullable|array',
            'home_content.actions.*' => 'array:mode,url',
            'home_content.actions.*.mode' => 'required|in:detail,webview,external',
            'home_content.actions.*.url' => 'nullable|required_unless:home_content.actions.*.mode,detail|url:https|max:1000',
            'native_chrome_colors' => 'nullable|array:header_icon,footer_background,footer_icon,footer_active_icon,footer_active_background,primary_button_background,primary_button_text,secondary_button_background,secondary_button_text',
            'native_chrome_colors.*' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'tabs' => 'nullable|array',
            'tabs.*.tab' => 'nullable|string|max:64',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.open' => 'nullable|in:embed,external',
            'tabs.*.url' => 'nullable|url:https|max:1000',
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

    public function toInput(int $projectId): UpdateHomeSettingsInput
    {
        $v = $this->validated();

        return new UpdateHomeSettingsInput(
            projectId: $projectId,
            tabs: $v['tabs'] ?? [],
            menuLayout: $v['home_menu_layout'],
            productLayout: $v['product_display_layout'],
            navigation: ($v['native_navigation_present'] ?? false) ? ($v['native_navigation'] ?? []) : null,
            showMemberCard: array_key_exists('home_show_member_card', $v) ? (bool) $v['home_show_member_card'] : null,
            headerImage: $this->file('navigation_header_image'),
            clearHeaderImage: (bool) ($v['navigation_header_image_clear'] ?? false),
            homeImage: $this->file('home_image'),
            clearHomeImage: (bool) ($v['home_image_clear'] ?? false),
            homeImages: $this->file('home_images', []),
            removedHomeImages: array_map('intval', $v['home_image_remove'] ?? []),
            homeImagesPresent: (bool) ($v['home_images_present'] ?? false),
            headerBackground: $v['navigation_header_background_color'] ?? null,
            nativeColors: $v['native_chrome_colors'] ?? null,
            homeContent: $v['home_content'] ?? null,
            homeGallery: $v['home_gallery'] ?? null,
        );
    }
}

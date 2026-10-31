<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\Data\UpdateHomeImagesInput;
use App\UseCases\Admin\Settings\Data\UpdateAppImagesInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsAppImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'navigation_header_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'navigation_header_image_clear' => 'nullable|in:0,1',
            'home_images' => 'nullable|array|max:10',
            'home_images.*' => 'image|mimes:png,jpg,jpeg,webp|max:8192',
            'home_image_remove' => 'nullable|array',
            'home_image_remove.*' => 'integer|min:0',
            'home_images_present' => 'nullable|in:0,1',
            'frontend_header_text' => 'nullable|string|max:255',
            'frontend_logo' => 'nullable|image|max:2048',
            'frontend_logo_clear' => 'nullable|in:0,1',
            'stamp_default_image' => 'nullable|image|max:2048',
            'stamp_default_image_clear' => 'nullable|in:0,1',
            'coupon_default_image' => 'nullable|image|max:2048',
            'coupon_default_image_clear' => 'nullable|in:0,1',
        ];
    }

    public function toInput(int $projectId): UpdateAppImagesInput
    {
        $v = $this->validated();

        return new UpdateAppImagesInput(
            projectId: $projectId,
            homeAssets: new UpdateHomeImagesInput($projectId, $this->file('navigation_header_image'), (bool) ($v['navigation_header_image_clear'] ?? false), null, false, $this->file('home_images', []), array_map('intval', $v['home_image_remove'] ?? []), (bool) ($v['home_images_present'] ?? false)),
            headerText: array_key_exists('frontend_header_text', $v) ? (string) $v['frontend_header_text'] : null,
            logo: $this->file('frontend_logo'),
            clearLogo: (bool) ($v['frontend_logo_clear'] ?? false),
            stampImage: $this->file('stamp_default_image'),
            clearStampImage: (bool) ($v['stamp_default_image_clear'] ?? false),
            couponImage: $this->file('coupon_default_image'),
            clearCouponImage: (bool) ($v['coupon_default_image_clear'] ?? false),
        );
    }
}

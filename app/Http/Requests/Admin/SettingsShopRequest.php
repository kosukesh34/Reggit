<?php

namespace App\Http\Requests\Admin;

use App\Support\JapanPrefectures;
use App\UseCases\Admin\Settings\Data\UpdateShopSettingsInput;
use Illuminate\Foundation\Http\FormRequest;

class SettingsShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_shop_enabled' => 'nullable|in:0,1',
            'shop_shipping_fee' => 'required|integer|min:0|max:100000',
            'shop_shipping_fees_by_prefecture' => 'nullable|array',
            'shop_shipping_fees_by_prefecture.*' => 'nullable|integer|min:0|max:100000',
            'shop_hero_headline' => 'nullable|string|max:120',
            'shop_hero_subcopy' => 'nullable|string|max:500',
            'shop_hero_image' => 'nullable|image|max:5120',
            'clear_shop_hero_image' => 'nullable|in:0,1',
            'shop_featured_product_ids' => 'nullable|array',
            'shop_featured_product_ids.*' => 'integer',
        ];
    }

    public function toInput(int $projectId): UpdateShopSettingsInput
    {
        $featured = $this->validated('shop_featured_product_ids', []);
        $featuredIds = is_array($featured)
            ? array_values(array_map('intval', $featured))
            : [];

        $rawFees = $this->input('shop_shipping_fees_by_prefecture', []);
        $feesByPrefecture = [];
        if (is_array($rawFees)) {
            foreach (JapanPrefectures::names() as $name) {
                if (! array_key_exists($name, $rawFees)) {
                    continue;
                }
                $value = $rawFees[$name];
                if ($value === null || $value === '') {
                    continue;
                }
                if (! is_numeric($value)) {
                    continue;
                }
                $feesByPrefecture[$name] = max(0, (int) $value);
            }
        }

        return new UpdateShopSettingsInput(
            projectId: $projectId,
            shopEnabled: $this->boolean('feature_shop_enabled'),
            shippingFee: (int) $this->validated('shop_shipping_fee'),
            shippingFeesByPrefecture: $feesByPrefecture,
            heroHeadline: trim((string) ($this->validated('shop_hero_headline') ?? '')),
            heroSubcopy: trim((string) ($this->validated('shop_hero_subcopy') ?? '')),
            heroImage: $this->file('shop_hero_image'),
            clearHeroImage: $this->boolean('clear_shop_hero_image'),
            featuredProductIds: $featuredIds,
        );
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Services\Shop\ShopOperations;
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
            // 公開中のショップは特定商取引法の表記をすべて入力する
            'shop_legal_notice' => 'nullable|array',
            ...collect(ShopOperations::LEGAL_FIELDS)->keys()->mapWithKeys(fn (string $key): array => [
                'shop_legal_notice.'.$key => 'nullable|required_if_accepted:feature_shop_enabled|string|max:'.($key === 'email' ? '255' : '1000').($key === 'email' ? '|email' : ''),
            ])->all(),
            'shop_free_shipping_threshold' => 'nullable|integer|min:1|max:10000000',
            'shop_delivery_date_enabled' => 'nullable|in:0,1',
            'shop_delivery_lead_days' => 'nullable|integer|min:1|max:60',
            'shop_delivery_range_days' => 'nullable|integer|min:1|max:90',
            'shop_delivery_time_slots' => 'nullable|string|max:1000',
            'shop_invoice_registration_number' => ['nullable', 'regex:/^T\d{13}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'shop_legal_notice.*.required_if_accepted' => 'ショップを公開するには特定商取引法に基づく表記をすべて入力してください。',
            'shop_legal_notice.email.email' => 'メールアドレスの形式が正しくありません。',
            'shop_invoice_registration_number.regex' => '登録番号は「T」と13桁の数字で入力してください。',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return collect(ShopOperations::LEGAL_FIELDS)->mapWithKeys(fn (string $label, string $key): array => ['shop_legal_notice.'.$key => $label])->all();
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
            operations: [
                'legal_notice' => collect(ShopOperations::LEGAL_FIELDS)
                    ->map(fn (string $label, string $key): string => trim((string) $this->input('shop_legal_notice.'.$key, '')))->all(),
                'free_shipping_threshold' => $this->filled('shop_free_shipping_threshold') ? (int) $this->validated('shop_free_shipping_threshold') : null,
                'delivery_date_enabled' => $this->boolean('shop_delivery_date_enabled'),
                'delivery_lead_days' => (int) ($this->validated('shop_delivery_lead_days') ?? 3),
                'delivery_range_days' => (int) ($this->validated('shop_delivery_range_days') ?? 14),
                'delivery_time_slots' => collect(preg_split('/\R/', (string) $this->input('shop_delivery_time_slots', '')) ?: [])
                    ->map(fn (string $slot): string => mb_substr(trim($slot), 0, 32))->filter()->unique()->values()->all(),
                'invoice_registration_number' => trim((string) $this->input('shop_invoice_registration_number', '')),
            ],
        );
    }
}

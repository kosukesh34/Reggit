<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\OperationalSettings;
use App\Models\Product;
use App\Models\Project;
use App\Services\Shop\ShopOperations;
use App\Support\JapanPrefectures;
use App\UseCases\Admin\Settings\Data\ShopSettingsView;

class GetShopSettingsUseCase
{
    public function __construct(
        private FeatureSettings $features,
        private OperationalSettings $operations,
        private ShopOperations $shopOperations,
    ) {}

    public function handle(int $projectId): ShopSettingsView
    {
        $project = Project::query()->findOrFail($projectId);
        $featured = $this->features->getValue('shop_featured_product_ids', [], $projectId);
        $featuredIds = is_array($featured)
            ? array_values(array_map('intval', array_filter($featured, static fn ($id): bool => is_numeric($id))))
            : [];

        $products = Product::query()
            ->where('project_id', $projectId)
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price'])
            ->map(static fn (Product $product): array => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'price' => (int) $product->price,
            ])
            ->all();

        $domain = is_string($project->custom_shop_domain) ? trim($project->custom_shop_domain) : '';

        return new ShopSettingsView(
            shopEnabled: $this->features->isFeatureEnabled('feature_shop_enabled', $projectId),
            shippingFee: $this->operations->getShopShippingFee($projectId),
            shippingFeesByPrefecture: $this->operations->getShopShippingFeesByPrefecture($projectId),
            prefectures: JapanPrefectures::names(),
            heroHeadline: trim((string) $this->features->getValue('shop_hero_headline', '', $projectId)),
            heroSubcopy: trim((string) $this->features->getValue('shop_hero_subcopy', '', $projectId)),
            heroImageUrl: trim((string) $this->features->getValue('shop_hero_image_url', '', $projectId)),
            featuredProductIds: $featuredIds,
            products: $products,
            publicShopUrl: route('shop.show', ['project_key' => $project->project_key]),
            customDomainUrl: $domain !== '' ? 'https://'.$domain.'/shop' : '',
            operations: [
                'legal_notice' => $this->shopOperations->legalNotice($projectId),
                'free_shipping_threshold' => $this->shopOperations->freeShippingThreshold($projectId),
                'delivery_date_enabled' => (bool) $this->features->getValue('shop_delivery_date_enabled', false, $projectId),
                'delivery_lead_days' => (int) $this->features->getValue('shop_delivery_lead_days', 3, $projectId),
                'delivery_range_days' => (int) $this->features->getValue('shop_delivery_range_days', 14, $projectId),
                'delivery_time_slots' => $this->shopOperations->deliveryOptions($projectId)['time_slots'],
                'invoice_registration_number' => $this->shopOperations->invoiceRegistrationNumber($projectId),
            ],
            legalNoticeUrl: route('shop.legal', ['project_key' => $project->project_key]),
        );
    }
}

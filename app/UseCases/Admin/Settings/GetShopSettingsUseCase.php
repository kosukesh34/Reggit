<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\OperationalSettings;
use App\Models\Product;
use App\Models\Project;
use App\Support\JapanPrefectures;
use App\UseCases\Admin\Settings\Data\ShopSettingsView;

class GetShopSettingsUseCase
{
    public function __construct(
        private FeatureSettings $features,
        private OperationalSettings $operations,
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
        );
    }
}

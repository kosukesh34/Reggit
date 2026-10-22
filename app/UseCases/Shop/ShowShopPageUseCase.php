<?php

namespace App\UseCases\Shop;

use App\Domain\Settings\Repositories\FeatureSettings;
use App\Domain\Settings\Repositories\OperationalSettings;
use App\Models\User;
use App\Support\JapanPrefectures;
use App\Support\ShopMemberSession;
use App\UseCases\Shop\Data\ShopPageView;
use App\UseCases\ShopAuth\Data\ShopAuthUserView;
use App\Util\AdminContext;

class ShowShopPageUseCase
{
    public function __construct(
        private ShopMemberSession $session,
        private FeatureSettings $features,
        private OperationalSettings $operations,
    ) {}

    public function handle(string $loginUrl, string $logoutUrl, ?string $routeProjectKey): ShopPageView
    {
        $project = AdminContext::getCurrentProject();
        abort_unless($project !== null, 404);
        $projectId = (int) $project->id;
        $user = $this->session->current();
        $theme = $this->operations->getLineThemeColors($projectId);
        $featured = $this->features->getValue('shop_featured_product_ids', [], $projectId);
        $featuredIds = is_array($featured)
            ? array_values(array_map('intval', array_filter($featured, static fn ($id): bool => is_numeric($id))))
            : [];

        $projectKey = (string) ($routeProjectKey ?? $project->project_key);
        $hasRouteKey = $routeProjectKey !== null && $routeProjectKey !== '';

        return new ShopPageView(
            projectKey: (string) $project->project_key,
            projectName: (string) $project->name,
            loginUrl: $loginUrl,
            logoutUrl: $logoutUrl,
            user: $user !== null ? $this->toView($user) : null,
            heroHeadline: trim((string) $this->features->getValue('shop_hero_headline', '', $projectId)),
            heroSubcopy: trim((string) $this->features->getValue('shop_hero_subcopy', '', $projectId)),
            heroImageUrl: trim((string) $this->features->getValue('shop_hero_image_url', '', $projectId)),
            logoUrl: trim((string) ($this->features->getFrontendLogoUrl($projectId) ?? '')),
            shippingFee: $this->operations->getShopShippingFee($projectId),
            shippingFeesByPrefecture: $this->operations->getShopShippingFeesByPrefecture($projectId),
            prefectures: JapanPrefectures::names(),
            featuredProductIds: $featuredIds,
            theme: [
                'primary' => (string) ($theme['primary'] ?? '#0E7C66'),
                'primaryDark' => (string) ($theme['primary_dark'] ?? '#0A6B58'),
            ],
            passkeyRegisterOptionsUrl: $hasRouteKey
                ? route('shop.passkey.register.options', ['project_key' => $projectKey])
                : route('shop.domain.passkey.register.options'),
            passkeyRegisterSubmitUrl: $hasRouteKey
                ? route('shop.passkey.register', ['project_key' => $projectKey])
                : route('shop.domain.passkey.register'),
        );
    }

    private function toView(User $user): ShopAuthUserView
    {
        $name = trim((string) ($user->name ?? ''));
        if ($name === '') {
            $name = trim((string) ($user->email ?? ''));
        }
        if ($name === '') {
            $name = '会員';
        }

        return new ShopAuthUserView(
            id: (int) $user->id,
            name: $name,
            email: (string) ($user->email ?? ''),
            hasPasskey: $user->memberPasskeys()->exists(),
        );
    }
}

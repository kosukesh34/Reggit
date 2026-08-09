<?php

namespace App\UseCases\Admin;

use App\Support\ShopAppUiResearchCatalog;
use App\UseCases\Admin\Data\ShopAppUiResearchView;

class GetShopAppUiResearchUseCase
{
    public function handle(): ShopAppUiResearchView
    {
        return new ShopAppUiResearchView(
            ShopAppUiResearchCatalog::summary(),
            ShopAppUiResearchCatalog::principles(),
            ShopAppUiResearchCatalog::featuredShots(),
            ShopAppUiResearchCatalog::templateMappings(),
            ShopAppUiResearchCatalog::highlightBrands(40),
            ShopAppUiResearchCatalog::learningsMarkdownHtml(),
        );
    }
}

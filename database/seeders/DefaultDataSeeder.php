<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductDeduplicationService;
use Illuminate\Database\Seeder;

/**
 * デフォルトプロジェクト用の商品・クーポンを投入する。
 * スマレジ未連携時や「デフォルトデータで上書き」したいときに使用。
 */
class DefaultDataSeeder extends Seeder
{
    public function runForProject(Project $project, bool $forceProducts = false, bool $forceCoupons = false): void
    {
        $this->seedCouponsOnly($project, $forceCoupons);
        app(ProductDeduplicationService::class)->deduplicateForProject((int) $project->id);
        $this->enablePublicShop((int) $project->id);

        if ($forceProducts) {
            Product::withoutGlobalScopes()->where('project_id', $project->id)->delete();
            $this->seedProductsOnly($project);

            return;
        }

        if ($this->hasAllDefaultProducts($project)) {
            return;
        }

        $this->seedProductsOnly($project);
    }

    /** PC向け公開ショップ（/web/{project_key}/shop）をサンプルで使えるようにする。 */
    private function enablePublicShop(int $projectId): void
    {
        app(ShopSettingStore::class)->setValue('feature_shop_enabled', true, 'boolean', $projectId);
        app(ShopSettingStore::class)->setValue('shop_shipping_fee', 500, 'integer', $projectId);
        app(ShopSettingStore::class)->setValue('shop_hero_headline', '自宅で楽しむ、お店の味', 'string', $projectId);
        app(ShopSettingStore::class)->setValue('shop_hero_subcopy', '人気メニューをオンラインでお届け。今だけのラインナップをチェック。', 'string', $projectId);
    }

    /** デフォルトのサンプルクーポンのみ投入（既存がある場合はスキップ。$force で上書き） */
    public function seedCouponsOnly(Project $project, bool $force = false): void
    {
        if (Coupon::where('project_id', $project->id)->exists() && ! $force) {
            return;
        }

        if ($force) {
            Coupon::where('project_id', $project->id)->delete();
        }

        $this->createSampleCoupons($project);
    }

    /** デフォルト商品のみ投入（同名は updateOrCreate で上書きし、重複を作らない） */
    public function seedProductsOnly(Project $project): void
    {
        foreach ($this->getDefaultProducts() as $product) {
            Product::withoutGlobalScopes()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'name' => $product['name'],
                ],
                $product
            );
        }

        app(ProductDeduplicationService::class)->deduplicateForProject((int) $project->id);
    }

    private function hasAllDefaultProducts(Project $project): bool
    {
        $names = array_column($this->getDefaultProducts(), 'name');

        return Product::withoutGlobalScopes()
            ->where('project_id', $project->id)
            ->whereIn('name', $names)
            ->count() === count($names);
    }

    /** @return list<array<string, mixed>> */
    private function getDefaultProducts(): array
    {
        return [
            [
                'name' => 'アイスラテ',
                'description' => 'エスプレッソとミルクを氷で冷やした定番ラテ',
                'price' => 520,
                'image_url' => '/images/products/iced-latte.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => '抹茶ラテ',
                'description' => '濃厚な抹茶とミルクのアイスラテ',
                'price' => 580,
                'image_url' => '/images/products/matcha-latte.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'カプチーノ',
                'description' => 'きめ細かいフォームミルクのカプチーノ',
                'price' => 480,
                'image_url' => '/images/products/cappuccino.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'オレンジジュース',
                'description' => 'フレッシュ感のあるオレンジジュース',
                'price' => 420,
                'image_url' => '/images/products/orange-juice.jpg',
                'category' => 'drink',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'クロワッサンサンド',
                'description' => 'ふわふわ卵とチーズのクロワッサンサンド',
                'price' => 680,
                'image_url' => '/images/products/croissant-sandwich.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'アボカドトースト',
                'description' => '完熟アボカドをのせたサワードウトースト',
                'price' => 720,
                'image_url' => '/images/products/avocado-toast.jpg',
                'category' => 'food',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'ベリーチーズケーキ',
                'description' => 'ミックスベリーをのせた濃厚チーズケーキ',
                'price' => 620,
                'image_url' => '/images/products/berry-cheesecake.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
            [
                'name' => 'チョコチップクッキー',
                'description' => 'シーソルト入りチョコチップクッキー',
                'price' => 380,
                'image_url' => '/images/products/chocolate-cookie.jpg',
                'category' => 'dessert',
                'is_available' => true,
                'stock' => 100,
            ],
        ];
    }

    private function createSampleCoupons(Project $project): void
    {
        $now = now();
        $coupons = [
            [
                'code' => 'SAMPLE100',
                'name' => 'サンプル 100円オフ',
                'type' => 'fixed',
                'value' => 100,
                'min_order_amount' => 500,
                'usage_limit' => null,
                'used_count' => 0,
                'start_at' => $now,
                'end_at' => $now->copy()->addMonths(3),
                'is_active' => true,
                'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
            ],
            [
                'code' => 'SAMPLE10PCT',
                'name' => 'サンプル 10%オフ',
                'type' => 'percent',
                'value' => 10,
                'min_order_amount' => 300,
                'usage_limit' => 100,
                'used_count' => 0,
                'start_at' => $now,
                'end_at' => $now->copy()->addMonths(1),
                'is_active' => true,
                'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
            ],
        ];
        foreach ($coupons as $c) {
            Coupon::create(array_merge($c, ['project_id' => $project->id]));
        }
    }
}

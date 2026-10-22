<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShopSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ショップ設定を保存できること(): void
    {
        [$admin, $project, $product] = $this->seedShopAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.shop.update', ['project_key' => $project->project_key]), [
                'feature_shop_enabled' => '1',
                'shop_shipping_fee' => 800,
                'shop_shipping_fees_by_prefecture' => [
                    '東京都' => 800,
                    '沖縄県' => 1500,
                ],
                'shop_hero_headline' => '春の配送キャンペーン',
                'shop_hero_subcopy' => '期間限定でお届けします',
                'shop_featured_product_ids' => [$product->id],
            ])
            ->assertRedirect(route('admin.settings.shop', ['project_key' => $project->project_key]));

        $this->assertTrue((bool) ShopSettingStore::getValue('feature_shop_enabled', false, $project->id));
        $this->assertSame(800, (int) ShopSettingStore::getValue('shop_shipping_fee', 0, $project->id));
        $this->assertSame([
            '東京都' => 800,
            '沖縄県' => 1500,
        ], ShopSettingStore::getValue('shop_shipping_fees_by_prefecture', [], $project->id));
        $this->assertSame('春の配送キャンペーン', ShopSettingStore::getValue('shop_hero_headline', '', $project->id));
        $this->assertSame([$product->id], ShopSettingStore::getValue('shop_featured_product_ids', [], $project->id));

        $this->withoutVite()
            ->get(route('admin.settings.shop', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee(route('shop.show', ['project_key' => $project->project_key]))
            ->assertSee('オンラインショップのQRコード', false);

        $this->withoutVite()
            ->get(route('shop.show', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('春の配送キャンペーン');
    }

    public function test_ショップ注文一覧は配送のみ表示すること(): void
    {
        [$admin, $project] = $this->seedShopAdmin();
        ShopSettingStore::setValue('feature_shop_enabled', true, 'boolean', $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('b', 32),
        ]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'order_type' => OrderType::MOBILE,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'total_amount' => 1000,
            'order_number' => 'M-1001',
        ]);
        $shipping = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'order_type' => OrderType::SHIPPING,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'total_amount' => 2000,
            'order_number' => 'S-2001',
            'shipping_name' => '配送太郎',
            'shipping_fee' => 500,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.shop.orders', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee($shipping->formattedOrderNumber())
            ->assertDontSee('M-1001');
    }

    /**
     * @return array{0: AdminUser, 1: Project, 2: Product}
     */
    private function seedShopAdmin(): array
    {
        $project = Project::factory()->create(['is_active' => true, 'service_status' => 'active']);
        $admin = AdminUser::factory()->create([
            'project_id' => $project->id,
            'role' => AdminUser::ROLE_ADMIN,
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => '特選ブレンド',
            'price' => 1200,
            'is_available' => true,
        ]);

        return [$admin, $project, $product];
    }
}

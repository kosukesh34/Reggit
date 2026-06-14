<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER = 'line-order-v1';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    /** @return array{user: User, product: Product, sizeGroup: ProductOptionGroup, optM: ProductOption, optL: ProductOption} */
    private function createUserWithSizedProduct(int $projectId = 1): array
    {
        $user = User::factory()->create(['line_user_id' => self::LINE_USER, 'project_id' => $projectId]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $projectId]);
        $product = Product::factory()->create([
            'project_id' => $projectId,
            'price' => 500,
            'stock' => 10,
            'name' => 'テストバーガー',
        ]);
        $sizeGroup = ProductOptionGroup::create([
            'project_id' => $projectId,
            'product_id' => $product->id,
            'name' => 'サイズ',
            'selection_type' => ProductOptionGroup::TYPE_SINGLE,
            'is_required' => true,
            'min_select' => 1,
            'max_select' => 1,
            'sort_order' => 0,
        ]);
        $optM = ProductOption::create([
            'project_id' => $projectId,
            'group_id' => $sizeGroup->id,
            'name' => 'M',
            'price_delta' => 0,
            'is_default' => true,
            'sort_order' => 0,
        ]);
        $optL = ProductOption::create([
            'project_id' => $projectId,
            'group_id' => $sizeGroup->id,
            'name' => 'L',
            'price_delta' => 80,
            'is_default' => false,
            'sort_order' => 1,
        ]);

        return compact('user', 'product', 'sizeGroup', 'optM', 'optL');
    }

    public function test_store_注文タイプがデフォルトで設定されること(): void
    {
        $this->withoutMiddleware(AuthenticateApiToken::class);
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $product = Product::factory()->create(['project_id' => 1, 'stock' => 10]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER])
            ->post('/api/v1/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
            ]);

        $response->assertStatus(201);
        $this->assertSame(OrderType::MOBILE, $response->json('data.order.order_type'));
    }

    public function test_store_オプション付きで追加料金が反映されること(): void
    {
        $this->withoutMiddleware(AuthenticateApiToken::class);
        $this->createProject();
        $fixture = $this->createUserWithSizedProduct();

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER])
            ->post('/api/v1/orders?project_key=default', [
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'quantity' => 1,
                        'option_ids' => [$fixture['optL']->id],
                        'notes' => 'ソース少なめ',
                    ],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
            ]);

        $response->assertStatus(201);
        $this->assertSame(580.0, (float) $response->json('data.order.total_amount'));
        $item = $response->json('data.order.items.0');
        $this->assertSame('L / ソース少なめ', $item['notes']);
    }

    public function test_store_必須オプション未選択は拒否されること(): void
    {
        $this->withoutMiddleware(AuthenticateApiToken::class);
        $this->createProject();
        $fixture = $this->createUserWithSizedProduct();

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER])
            ->post('/api/v1/orders?project_key=default', [
                'items' => [
                    ['product_id' => $fixture['product']->id, 'quantity' => 1, 'option_ids' => []],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('必須', $response->json('message'));
    }

    public function test_store_単一選択グループに複数オプションは拒否されること(): void
    {
        $this->withoutMiddleware(AuthenticateApiToken::class);
        $this->createProject();
        $fixture = $this->createUserWithSizedProduct();

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER])
            ->post('/api/v1/orders?project_key=default', [
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'quantity' => 1,
                        'option_ids' => [$fixture['optM']->id, $fixture['optL']->id],
                    ],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('1つだけ', $response->json('message'));
    }

    public function test_store_他商品のオプション_i_dは拒否されること(): void
    {
        $this->withoutMiddleware(AuthenticateApiToken::class);
        $this->createProject();
        $fixture = $this->createUserWithSizedProduct();
        $otherProduct = Product::factory()->create(['project_id' => 1, 'stock' => 10]);
        $otherGroup = ProductOptionGroup::create([
            'project_id' => 1,
            'product_id' => $otherProduct->id,
            'name' => 'サイズ',
            'selection_type' => ProductOptionGroup::TYPE_SINGLE,
            'is_required' => false,
            'min_select' => 0,
            'max_select' => 1,
            'sort_order' => 0,
        ]);
        $foreignOption = ProductOption::create([
            'project_id' => 1,
            'group_id' => $otherGroup->id,
            'name' => 'XL',
            'price_delta' => 100,
            'sort_order' => 0,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER])
            ->post('/api/v1/orders?project_key=default', [
                'items' => [
                    [
                        'product_id' => $fixture['product']->id,
                        'quantity' => 1,
                        'option_ids' => [$foreignOption->id],
                    ],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
            ]);

        $response->assertStatus(400);
        $this->assertStringContainsString('不正', $response->json('message'));
    }
}

<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsProducts(): void
    {
        Product::factory()->create(['project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['products']]);
    }

    public function testShowReturnsProduct(): void
    {
        $product = Product::factory()->create(['project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.product.id', $product->id);
    }
}

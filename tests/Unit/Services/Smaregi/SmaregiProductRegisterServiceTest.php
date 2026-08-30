<?php

namespace Tests\Unit\Services\Smaregi;

use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiProductRegisterService;
use Mockery;
use Tests\TestCase;

class SmaregiProductRegisterServiceTest extends TestCase
{
    public function test_register_product_image_公開httpsをスマレジへ送ること(): void
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->with(1)->andReturn('contract-1');
        $api->shouldReceive('putProductImage')
            ->once()
            ->with('contract-1', '99', Mockery::on(function (array $body): bool {
                return ($body['imageUrl'] ?? '') === 'https://cdn.example.com/latte.png';
            }))
            ->andReturn(['productId' => '99']);

        $service = new SmaregiProductRegisterService($api);

        $this->assertTrue($service->registerProductImage(1, '99', 'https://cdn.example.com/latte.png'));
    }

    public function test_register_product_image_相対パスをapp_urlで絶対化すること(): void
    {
        config(['app.url' => 'https://app.example.com']);

        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('putProductImage')
            ->once()
            ->with('contract-1', '99', Mockery::on(function (array $body): bool {
                return ($body['imageUrl'] ?? '') === 'https://app.example.com/storage/product_images/1/a.jpg';
            }))
            ->andReturn(['productId' => '99']);

        $service = new SmaregiProductRegisterService($api);

        $this->assertTrue($service->registerProductImage(1, '99', '/storage/product_images/1/a.jpg'));
    }

    public function test_register_product_image_localhostは送らないこと(): void
    {
        config(['app.url' => 'http://localhost']);

        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('putProductImage')->never();

        $service = new SmaregiProductRegisterService($api);

        $this->assertFalse($service->registerProductImage(1, '99', 'http://localhost/storage/a.png'));
    }

    public function test_register_product_image_webpは送らないこと(): void
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('putProductImage')->never();

        $service = new SmaregiProductRegisterService($api);

        $this->assertFalse($service->registerProductImage(1, '99', 'https://cdn.example.com/a.webp'));
    }
}

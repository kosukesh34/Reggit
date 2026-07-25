<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Line\LineService;
use App\Services\OrderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_設定OFFのときLINEを送らないこと(): void
    {
        $project = $this->createProject();
        ShopSetting::setOrderLineNotifyEnabled(false, (int) $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('a', 32),
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE,
            'order_number' => '20260725001',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->never();

        $service = new OrderNotificationService($line);
        $service->notifyConfirmed((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_店内注文ではLINEを送らないこと(): void
    {
        $project = $this->createProject();
        ShopSetting::setOrderLineNotifyEnabled(true, (int) $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('b', 32),
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'order_type' => OrderType::IN_STORE,
            'order_number' => '20260725002',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->never();

        $service = new OrderNotificationService($line);
        $service->notifyConfirmed((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_line_user_idがないときLINEを送らないこと(): void
    {
        $project = $this->createProject();
        ShopSetting::setOrderLineNotifyEnabled(true, (int) $project->id);
        ShopSetting::setLineChannelAccessToken('test-token', (int) $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => '',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE,
            'order_number' => '20260725003',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->never();

        $service = new OrderNotificationService($line);
        $service->notifyConfirmed((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_受取可通知_設定OFFのときLINEを送らないこと(): void
    {
        $project = $this->createProject();
        ShopSetting::setOrderReadyLineNotifyEnabled(false, (int) $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('c', 32),
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::READY,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE,
            'order_number' => '20260725004',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->never();

        $service = new OrderNotificationService($line);
        $service->notifyReady((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }
}

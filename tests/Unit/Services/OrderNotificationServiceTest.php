<?php

namespace Tests\Unit\Services;

use App\Domain\Settings\Repositories\OrderNotificationSettings;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\OrderSettingStore;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Line\LineService;
use App\Services\OrderNotificationService;
use App\Services\PushNotificationService;
use App\Services\Shop\ShopOperations;
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

    public function test_設定_of_fのとき_lin_eを送らないこと(): void
    {
        $project = $this->createProject();
        OrderSettingStore::setOrderLineNotifyEnabled(false, (int) $project->id);
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
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->andReturn(['target_tokens' => 0, 'sent' => 0]);

        $service = new OrderNotificationService($line, app(OrderNotificationSettings::class), $push, app(ShopOperations::class));
        $service->notifyConfirmed((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_店内注文では_lin_eを送らないこと(): void
    {
        $project = $this->createProject();
        OrderSettingStore::setOrderLineNotifyEnabled(true, (int) $project->id);
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
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->never();

        $service = new OrderNotificationService($line, app(OrderNotificationSettings::class), $push, app(ShopOperations::class));
        $service->notifyConfirmed((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_line_user_idがないとき_lin_eを送らないこと(): void
    {
        $project = $this->createProject();
        OrderSettingStore::setOrderLineNotifyEnabled(true, (int) $project->id);
        LineSettingStore::setLineChannelAccessToken('test-token', (int) $project->id);
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
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')
            ->once()
            ->with(
                (int) $project->id,
                (int) $user->id,
                '注文が完了しました',
                Mockery::type('string'),
                Mockery::on(fn (array $data): bool => ($data['type'] ?? '') === 'order_confirmed')
            )
            ->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);

        $service = new OrderNotificationService($line, app(OrderNotificationSettings::class), $push, app(ShopOperations::class));
        $service->notifyConfirmed((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_受取可通知_設定_of_fのとき_lin_eを送らないこと(): void
    {
        $project = $this->createProject();
        OrderSettingStore::setOrderReadyLineNotifyEnabled(false, (int) $project->id);
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
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->with(
            (int) $project->id, (int) $user->id, Mockery::type('string'), Mockery::type('string'),
            Mockery::on(fn (array $data): bool => $data['type'] === 'order_ready' && $data['tab'] === 'orders')
        )->andReturn(['target_tokens' => 1, 'sent' => 1]);

        $service = new OrderNotificationService($line, app(OrderNotificationSettings::class), $push, app(ShopOperations::class));
        $service->notifyReady((int) $order->id, (int) $project->id);
        $this->assertTrue(true);
    }

    public function test_発送通知は受取可設定に関係なく配送注文へ_lin_eとプッシュを送ること(): void
    {
        $project = $this->createProject();
        OrderSettingStore::setOrderReadyLineNotifyEnabled(false, (int) $project->id);
        LineSettingStore::setLineChannelAccessToken('test-token', (int) $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('d', 32),
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::SHIPPED,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::SHIPPING,
            'order_number' => '20260725005',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->once()->andReturn([]);
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->with(
            (int) $project->id, (int) $user->id, 'ご注文の商品を発送しました', Mockery::type('string'),
            Mockery::on(fn (array $data): bool => $data['type'] === 'order_shipped')
        )->andReturn(['target_tokens' => 1, 'sent' => 1]);

        $service = new OrderNotificationService($line, app(OrderNotificationSettings::class), $push, app(ShopOperations::class));
        $service->notifyShipped((int) $order->id, (int) $project->id);
        $service->notifyReady((int) $order->id, (int) $project->id);
    }
}

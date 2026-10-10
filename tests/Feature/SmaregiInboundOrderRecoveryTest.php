<?php

namespace Tests\Feature;

use App\Domain\Order\Repositories\OrderRepository;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderNumberAssigner;
use App\Services\Smaregi\SmaregiInboundOrderRecorder;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmaregiInboundOrderRecoveryTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{bool}> */
    public static function retryPaths(): array
    {
        return ['existing order' => [false], 'unique race recovery' => [true]];
    }

    #[DataProvider('retryPaths')]
    public function test_retry_assigns_missing_number_without_duplicating_order_or_items(bool $race): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $assigner = app(OrderNumberAssigner::class);
        $failing = Mockery::mock(OrderNumberAssigner::class);
        $failing->shouldReceive('assignIfMissing')->once()->andThrow(new \RuntimeException('Simulated numbering failure'));
        $this->app->instance(OrderNumberAssigner::class, $failing);
        $details = [['productName' => 'Cake', 'salesPrice' => 300, 'quantity' => 1]];
        try {
            app(SmaregiInboundOrderRecorder::class)->recordSale($member, 'recovery-1', 300, $details, now());
            $this->fail('The numbering failure must propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated numbering failure', $e->getMessage());
        }
        $order = Order::query()->sole();
        $this->assertNull($order->order_number);
        $itemIds = OrderItem::query()->pluck('id')->all();
        $this->app->instance(OrderNumberAssigner::class, $assigner);
        if ($race) {
            $repository = Mockery::mock(OrderRepository::class);
            $repository->shouldReceive('findIdBySmaregiTransactionHeadId')->with((int) $project->id, 'recovery-1')
                ->once()->ordered()->andReturn(null);
            $repository->shouldReceive('findIdBySmaregiTransactionHeadId')->with((int) $project->id, 'recovery-1')
                ->once()->ordered()->andReturn((int) $order->id);
            $originalRepository = app(OrderRepository::class);
            $this->app->instance(OrderRepository::class, $repository);
        }
        $recovered = app(SmaregiInboundOrderRecorder::class)->recordSale($member, 'recovery-1', 300, $details, now());
        $this->assertSame($order->id, $recovered?->id);
        $this->assertNotEmpty($recovered?->order_number);
        $this->assertSame(1, Order::count());
        $this->assertSame($itemIds, OrderItem::query()->pluck('id')->all());
        if ($race) {
            $this->app->instance(OrderRepository::class, $originalRepository);
        }
        $retry = app(SmaregiInboundOrderRecorder::class)->recordSale($member, 'recovery-1', 300, $details, now());
        $this->assertSame($recovered?->order_number, $retry?->order_number);
    }
}

<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Admin\Analytics\AdminAnalyticsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_カテゴリと時間帯データが含まれること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'category' => 'food']);

        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::COMPLETED,
            'payment_method' => 'cash',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 750,
        ]);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'published_at' => now()->subHours(2),
        ]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now()->subHour(),
        ]);

        $useCase = app(AdminAnalyticsUseCase::class);
        $view = $useCase->handle($project->id, now()->subDay()->toDateString(), now()->toDateString());

        $this->assertArrayHasKey('category_sales', $view->breakdowns);
        $this->assertArrayHasKey('hourly_orders', $view->breakdowns);
        $this->assertArrayHasKey('announcement_reads', $view->breakdowns);
        $this->assertArrayHasKey('announcements_published', $view->summary);
        $this->assertArrayHasKey('announcement_avg_read_rate', $view->summary);
        $this->assertNotEmpty($view->breakdowns['category_sales']);
        $this->assertNotEmpty($view->breakdowns['announcement_reads']['top_announcements']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Services\ReservationService;
use App\Support\DailySequenceNumber;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationNumberMultistoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_stores_share_the_project_daily_sequence(): void
    {
        $project = Project::factory()->create();
        $a = $project->stores()->firstOrFail();
        $b = Store::create(['project_id' => $project->id, 'store_key' => 'branch', 'name' => 'Branch', 'is_active' => true]);
        $user = User::factory()->create(['project_id' => $project->id]);
        AdminContext::setCurrentProject($project);
        $create = fn () => app(ReservationService::class)->createReservation($user, ['reserved_at' => now()->addDay(), 'number_of_people' => 1]);
        $first = app(StoreContext::class)->runForStore($a, $create);
        $second = app(StoreContext::class)->runForStore($b, $create);
        $this->assertSame(DailySequenceNumber::build(1), $first->reservation_number);
        $this->assertSame(DailySequenceNumber::build(2), $second->reservation_number);
    }
}

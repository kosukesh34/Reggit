<?php

namespace Tests\Unit\Services;

use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Domain\Settings\Repositories\QueueAdminSettings;
use App\Enums\QueueStatus;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Admin\QueueAdminService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueueAdminServiceNotifyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public static function lineOutcomes(): array
    {
        return ['LINE succeeds' => [false], 'LINE fails but FCM succeeds' => [true]];
    }

    #[DataProvider('lineOutcomes')]
    public function test_notify_called_sends_native_push_and_line_with_project_token(bool $lineFails): void
    {
        $project = $this->createProject();
        ShopSettingStore::setLineChannelAccessToken('project-queue-token', (int) $project->id);
        $lineUserId = 'U'.str_repeat('c', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'push_token' => 'fcm-token-queue-call',
            'platform' => 'ios',
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => QueueStatus::CALLED,
            'queue_number' => (int) (now()->format('Ymd').'7'),
            'called_at' => now(),
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendQueueCalledToUser')
            ->once()
            ->with(
                (int) $project->id,
                (int) $user->id,
                Mockery::type('string')
            )
            ->andReturn(['target_tokens' => 1, 'sent' => 1, 'failed' => 0, 'skipped' => 0]);

        $lineMock = Mockery::mock(LineService::class);
        $lineExpectation = $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message) && ($message['type'] ?? '') === 'flex'),
                'project-queue-token'
            );
        if ($lineFails) {
            $lineExpectation->andThrow(new \RuntimeException('LINE unavailable'));
        } else {
            $lineExpectation->andReturn([]);
        }

        $service = new QueueAdminService(
            $lineMock,
            $pushMock,
            app(QueueEntryRepository::class),
            app(QueueAdminSettings::class),
        );
        $service->notifyCalled((int) $entry->id);

        $this->assertDatabaseHas('queue_call_logs', [
            'queue_entry_id' => $entry->id,
            'push_status' => 'sent',
            'line_user_id' => $lineUserId,
        ]);
    }
}

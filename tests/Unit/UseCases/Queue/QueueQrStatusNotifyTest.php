<?php

namespace Tests\Unit\UseCases\Queue;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Support\QueueLiffUrlBuilder;
use App\UseCases\Queue\Data\QueueEntryFactory;
use App\UseCases\Queue\Data\QueueQrStatusInput;
use App\UseCases\Queue\QueueQrStatusUseCase;
use App\UseCases\Queue\QueueStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QueueQrStatusNotifyTest extends TestCase
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

    public function test_line_link_sends_completion_with_project_token(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('project-link-token', (int) $project->id);
        $lineUserId = 'U'.str_repeat('e', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-line-notify',
        ]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message) && ($message['type'] ?? '') === 'flex'),
                'project-link-token'
            )
            ->andReturn([]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendToUser')->never();

        $useCase = new QueueQrStatusUseCase(
            app(QueueStatsService::class),
            app(QueueEntryFactory::class),
            $lineMock,
            $pushMock,
            app(DeviceService::class),
            app(LineConnectionSettings::class),
            app(QueueLiffUrlBuilder::class),
        );

        $output = $useCase->handle(new QueueQrStatusInput(
            'token-line-notify',
            (int) $project->id,
            $lineUserId,
            $user,
        ));

        $this->assertNotNull($output);
        $entry->refresh();
        $this->assertSame((int) $user->id, (int) $entry->user_id);
    }

    public function test_native_link_sends_completion_push(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-native-notify-1',
        ]);
        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'device_identifier' => 'native-device-notify-1',
            'push_token' => 'fcm-token-queue-link',
            'platform' => 'ios',
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-native-notify',
        ]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')->never();

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendToUser')
            ->once()
            ->with(
                (int) $project->id,
                (int) $user->id,
                '順番待ちのアプリ連携が完了しました',
                Mockery::type('string'),
                Mockery::on(fn (array $data) => ($data['type'] ?? '') === 'queue_linked'),
                'queue-linked-'.$entry->id
            )
            ->andReturn(['target_tokens' => 1, 'sent' => 1, 'failed' => 0, 'skipped' => 0]);

        $useCase = new QueueQrStatusUseCase(
            app(QueueStatsService::class),
            app(QueueEntryFactory::class),
            $lineMock,
            $pushMock,
            app(DeviceService::class),
            app(LineConnectionSettings::class),
            app(QueueLiffUrlBuilder::class),
        );

        $output = $useCase->handle(new QueueQrStatusInput(
            'token-native-notify',
            (int) $project->id,
            '',
            $user,
            'native-device-notify-1',
            'ios',
        ));

        $this->assertNotNull($output);
        $entry->refresh();
        $this->assertSame((int) $user->id, (int) $entry->user_id);
    }
}

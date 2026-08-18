<?php

namespace Tests\Unit\UseCases\Line;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepository;
use App\Services\Line\LineService;
use App\Services\Line\LineWebhookProjectResolver;
use App\UseCases\Line\Data\LineWebhookInput;
use App\UseCases\Line\LineWebhookHandleUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LineWebhookHandleUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_followでライン名はline_nameのみ更新しnameは触らないこと(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );

        $lineUserId = 'U00000000000000000000000000000011';
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('verifySignature')->once()->andReturn(true);
        $line->shouldReceive('getProfile')->once()->with($lineUserId)->andReturn([
            'displayName' => 'LINE花子',
            'pictureUrl' => 'https://example.com/p.png',
        ]);
        $line->shouldReceive('createTextMessage')->once()->andReturn(['type' => 'text', 'text' => 'ok']);
        $line->shouldReceive('replyMessage')->once()->andReturn([]);

        $resolver = new LineWebhookProjectResolver($line, app(ProjectRepository::class));
        $useCase = new LineWebhookHandleUseCase($line, $resolver, app(ShopSettingRepository::class));
        $result = $useCase->handle(new LineWebhookInput(
            false,
            'sig',
            json_encode([
                'events' => [[
                    'type' => 'follow',
                    'replyToken' => 'reply-1',
                    'source' => ['userId' => $lineUserId],
                ]],
            ], JSON_THROW_ON_ERROR),
        ));

        $this->assertSame(200, $result['status']);

        $user = User::query()->where('line_user_id', $lineUserId)->first();
        $this->assertNotNull($user);
        $this->assertSame('LINE花子', $user->line_name);
        $this->assertNull($user->name);
        $this->assertSame('https://example.com/p.png', $user->profile_image_url);
    }

    public function test_followは署名が一致する店舗にユーザーを作ること(): void
    {
        $projectA = Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);
        $projectB = Project::factory()->create(['project_key' => 'shop-b', 'is_active' => true]);
        ShopSettingStore::setLineChannelSecret('secret-a', (int) $projectA->id);
        ShopSettingStore::setLineChannelSecret('secret-b', (int) $projectB->id);

        $lineUserId = 'U00000000000000000000000000000022';
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('verifySignatureWithSecret')
            ->andReturnUsing(static fn (string $body, ?string $signature, ?string $secret): bool => $secret === 'secret-b');
        $line->shouldReceive('verifySignature')->never();
        $line->shouldReceive('getProfile')->once()->with($lineUserId)->andReturn([
            'displayName' => '店舗B',
            'pictureUrl' => null,
        ]);
        $line->shouldReceive('createTextMessage')->once()->andReturn(['type' => 'text', 'text' => 'ok']);
        $line->shouldReceive('replyMessage')->once()->andReturn([]);

        $resolver = new LineWebhookProjectResolver($line, app(ProjectRepository::class));
        $useCase = new LineWebhookHandleUseCase($line, $resolver, app(ShopSettingRepository::class));
        $result = $useCase->handle(new LineWebhookInput(
            false,
            'sig-b',
            json_encode([
                'events' => [[
                    'type' => 'follow',
                    'replyToken' => 'reply-2',
                    'source' => ['userId' => $lineUserId],
                ]],
            ], JSON_THROW_ON_ERROR),
        ));

        $this->assertSame(200, $result['status']);
        $user = User::query()->where('line_user_id', $lineUserId)->first();
        $this->assertNotNull($user);
        $this->assertSame((int) $projectB->id, (int) $user->project_id);
        $this->assertSame(0, User::query()->where('project_id', $projectA->id)->where('line_user_id', $lineUserId)->count());
    }

    public function test_署名が不正なら401でユーザーを作らないこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        ShopSettingStore::setLineChannelSecret('secret-a', (int) $project->id);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('verifySignatureWithSecret')->andReturn(false);
        $line->shouldReceive('verifySignature')->once()->andReturn(false);
        $line->shouldReceive('getProfile')->never();
        $line->shouldReceive('replyMessage')->never();

        $resolver = new LineWebhookProjectResolver($line, app(ProjectRepository::class));
        $useCase = new LineWebhookHandleUseCase($line, $resolver, app(ShopSettingRepository::class));
        $result = $useCase->handle(new LineWebhookInput(
            false,
            'bad-sig',
            json_encode([
                'events' => [[
                    'type' => 'follow',
                    'replyToken' => 'reply-3',
                    'source' => ['userId' => 'U00000000000000000000000000000033'],
                ]],
            ], JSON_THROW_ON_ERROR),
        ));

        $this->assertSame(401, $result['status']);
        $this->assertSame(0, User::query()->count());
    }
}

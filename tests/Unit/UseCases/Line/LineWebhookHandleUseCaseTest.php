<?php

namespace Tests\Unit\UseCases\Line;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use App\Models\User;
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
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        ShopSettingStore::setLineChannelSecret('secret-default', (int) $project->id);
        ShopSettingStore::setLineChannelAccessToken('token-default', (int) $project->id);

        $lineUserId = 'U00000000000000000000000000000011';
        $line = $this->mockLineService('secret-default');
        $line->shouldReceive('getProfile')->once()->with($lineUserId, 'token-default')->andReturn([
            'displayName' => 'LINE花子',
            'pictureUrl' => 'https://example.com/p.png',
        ]);
        $line->shouldReceive('createTextMessage')->once()->andReturn(['type' => 'text', 'text' => 'ok']);
        $line->shouldReceive('replyMessage')->once()->with('reply-1', Mockery::any(), 'token-default')->andReturn([]);

        $result = $this->makeUseCase($line)->handle($this->followInput($lineUserId, 'reply-1', 'sig'));

        $this->assertSame(200, $result['status']);

        $user = User::query()->where('line_user_id', $lineUserId)->first();
        $this->assertNotNull($user);
        $this->assertSame('LINE花子', $user->line_name);
        $this->assertNull($user->name);
        $this->assertSame('https://example.com/p.png', $user->profile_image_url);
        $this->assertSame((int) $project->id, (int) $user->project_id);
    }

    public function test_followは署名が一致する店舗にユーザーを作ること(): void
    {
        $projectA = Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);
        $projectB = Project::factory()->create(['project_key' => 'shop-b', 'is_active' => true]);
        ShopSettingStore::setLineChannelSecret('secret-a', (int) $projectA->id);
        ShopSettingStore::setLineChannelSecret('secret-b', (int) $projectB->id);

        $lineUserId = 'U00000000000000000000000000000022';
        $line = $this->mockLineService('secret-b');
        $line->shouldReceive('getProfile')->once()->with($lineUserId, Mockery::any())->andReturn([
            'displayName' => '店舗B',
            'pictureUrl' => null,
        ]);
        $line->shouldReceive('createTextMessage')->once()->andReturn(['type' => 'text', 'text' => 'ok']);
        $line->shouldReceive('replyMessage')->once()->andReturn([]);

        $result = $this->makeUseCase($line)->handle($this->followInput($lineUserId, 'reply-2', 'sig-b'));

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
        $line->shouldReceive('verifySignature')->never();
        $line->shouldReceive('getProfile')->never();
        $line->shouldReceive('replyMessage')->never();

        $result = $this->makeUseCase($line)->handle($this->followInput(
            'U00000000000000000000000000000033',
            'reply-3',
            'bad-sig',
        ));

        $this->assertSame(401, $result['status']);
        $this->assertSame(0, User::query()->count());
    }

    public function test_複数店舗が同じsecretに当たる場合は401で作らないこと(): void
    {
        config(['line.channel_secret' => 'shared-secret']);
        Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);
        Project::factory()->create(['project_key' => 'shop-b', 'is_active' => true]);

        $line = $this->mockLineService('shared-secret');
        $line->shouldReceive('getProfile')->never();
        $line->shouldReceive('replyMessage')->never();

        $result = $this->makeUseCase($line)->handle($this->followInput(
            'U00000000000000000000000000000044',
            'reply-4',
            'sig-shared',
        ));

        $this->assertSame(401, $result['status']);
        $this->assertSame(0, User::query()->count());
    }

    private function makeUseCase(LineService $line): LineWebhookHandleUseCase
    {
        $resolver = new LineWebhookProjectResolver($line, app(ShopSettingRepository::class));

        return new LineWebhookHandleUseCase($line, $resolver, app(ShopSettingRepository::class));
    }

    private function mockLineService(string $matchingSecret): LineService
    {
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('verifySignatureWithSecret')
            ->andReturnUsing(static fn (string $body, ?string $signature, ?string $secret): bool => $secret === $matchingSecret);
        $line->shouldReceive('verifySignature')->never();

        return $line;
    }

    private function followInput(string $lineUserId, string $replyToken, string $signature): LineWebhookInput
    {
        return new LineWebhookInput(
            false,
            $signature,
            json_encode([
                'events' => [[
                    'type' => 'follow',
                    'replyToken' => $replyToken,
                    'source' => ['userId' => $lineUserId],
                ]],
            ], JSON_THROW_ON_ERROR),
        );
    }
}

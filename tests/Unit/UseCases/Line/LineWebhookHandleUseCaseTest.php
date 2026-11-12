<?php

namespace Tests\Unit\UseCases\Line;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Models\LineFollowEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\Line\LineReplyMessages;
use App\Services\Line\LineService;
use App\Services\Line\LineWebhookProjectResolver;
use App\Services\Member\MemberPointLedger;
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
        app(LineSettingStore::class)->setLineChannelSecret('secret-default', (int) $project->id);
        app(LineSettingStore::class)->setLineChannelAccessToken('token-default', (int) $project->id);

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
        app(LineSettingStore::class)->setLineChannelSecret('secret-a', (int) $projectA->id);
        app(LineSettingStore::class)->setLineChannelSecret('secret-b', (int) $projectB->id);

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
        app(LineSettingStore::class)->setLineChannelSecret('secret-a', (int) $project->id);

        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('verifySignatureWithSecret')->andReturn(false);
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

    public function test_unfollowでブロック日時を記録しfollowで解除すること(): void
    {
        $project = Project::factory()->create(['project_key' => 'shop-block', 'is_active' => true]);
        app(LineSettingStore::class)->setLineChannelSecret('secret-block', (int) $project->id);
        $lineUserId = 'U00000000000000000000000000000033';
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => $lineUserId]);
        $line = $this->mockLineService('secret-block');

        $unfollow = new LineWebhookInput(false, 'sig', json_encode([
            'events' => [['type' => 'unfollow', 'source' => ['userId' => $lineUserId]]],
        ], JSON_THROW_ON_ERROR));
        $this->assertSame(200, $this->makeUseCase($line)->handle($unfollow)['status']);
        $this->assertNotNull($user->fresh()->line_blocked_at);

        $line->shouldReceive('getProfile')->andReturn(null);
        $line->shouldReceive('createTextMessage')->andReturn(['type' => 'text', 'text' => 'ok']);
        $line->shouldReceive('replyMessage')->andReturn([]);
        $this->makeUseCase($line)->handle($this->followInput($lineUserId, 'reply-3', 'sig'));
        $this->assertNull($user->fresh()->line_blocked_at);
        $this->assertSame(
            ['unfollow', 'follow'],
            LineFollowEvent::query()->where('user_id', $user->id)->orderBy('id')->pluck('type')->all(),
        );
    }

    public function test_同じunfollowが再送されてもブロックは1回だけ数えること(): void
    {
        $project = Project::factory()->create(['project_key' => 'shop-redelivery', 'is_active' => true]);
        app(LineSettingStore::class)->setLineChannelSecret('secret-redelivery', (int) $project->id);
        $lineUserId = 'U00000000000000000000000000000044';
        User::factory()->create(['project_id' => $project->id, 'line_user_id' => $lineUserId]);
        $input = new LineWebhookInput(false, 'sig', json_encode([
            'events' => [['type' => 'unfollow', 'webhookEventId' => '01HXYZREDELIVERY', 'source' => ['userId' => $lineUserId]]],
        ], JSON_THROW_ON_ERROR));

        $useCase = $this->makeUseCase($this->mockLineService('secret-redelivery'));
        $useCase->handle($input);
        $useCase->handle($input);

        $this->assertSame(1, LineFollowEvent::query()->where('project_id', $project->id)->count());
    }

    public function test_project_key付きurlはその店舗のsecretだけで判定し他店舗と同じsecretでも受け付けること(): void
    {
        $projectA = Project::factory()->create(['project_key' => 'shop-key-a', 'is_active' => true]);
        $projectB = Project::factory()->create(['project_key' => 'shop-key-b', 'is_active' => true]);
        app(LineSettingStore::class)->setLineChannelSecret('shared-secret', (int) $projectA->id);
        app(LineSettingStore::class)->setLineChannelSecret('shared-secret', (int) $projectB->id);
        $lineUserId = 'U00000000000000000000000000000055';
        $userA = User::factory()->create(['project_id' => $projectA->id, 'line_user_id' => $lineUserId]);
        $userB = User::factory()->create(['project_id' => $projectB->id, 'line_user_id' => $lineUserId]);

        $result = $this->makeUseCase($this->mockLineService('shared-secret'))
            ->handle($this->unfollowInput($lineUserId, 'shop-key-b'));

        $this->assertSame(200, $result['status']);
        $this->assertNull($userA->fresh()->line_blocked_at);
        $this->assertNotNull($userB->fresh()->line_blocked_at);
    }

    public function test_project_key付きurlで他店舗のsecretによる署名や未知のkeyは401にすること(): void
    {
        $projectA = Project::factory()->create(['project_key' => 'shop-key-c', 'is_active' => true]);
        $projectB = Project::factory()->create(['project_key' => 'shop-key-d', 'is_active' => true]);
        app(LineSettingStore::class)->setLineChannelSecret('secret-c', (int) $projectA->id);
        app(LineSettingStore::class)->setLineChannelSecret('secret-d', (int) $projectB->id);
        $lineUserId = 'U00000000000000000000000000000066';
        $userA = User::factory()->create(['project_id' => $projectA->id, 'line_user_id' => $lineUserId]);
        $useCase = $this->makeUseCase($this->mockLineService('secret-d'));

        $this->assertSame(401, $useCase->handle($this->unfollowInput($lineUserId, 'shop-key-c'))['status']);
        $this->assertSame(401, $useCase->handle($this->unfollowInput($lineUserId, 'no-such-key'))['status']);
        $this->assertNull($userA->fresh()->line_blocked_at);
    }

    public function test_project_key付きurlのルートで実際の署名を検証してブロックを記録すること(): void
    {
        $project = Project::factory()->create(['project_key' => 'shop-route', 'is_active' => true]);
        app(LineSettingStore::class)->setLineChannelSecret('secret-route', (int) $project->id);
        $lineUserId = 'U00000000000000000000000000000077';
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => $lineUserId]);
        $body = $this->unfollowInput($lineUserId, null)->body;
        $send = fn (string $secret) => $this->call('POST', '/api/line/webhook/shop-route', [], [], [], [
            'HTTP_X_LINE_SIGNATURE' => base64_encode(hash_hmac('sha256', $body, $secret, true)),
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $send('wrong-secret')->assertStatus(401);
        $this->assertNull($user->fresh()->line_blocked_at);

        $send('secret-route')->assertOk();
        $this->assertNotNull($user->fresh()->line_blocked_at);
    }

    private function unfollowInput(string $lineUserId, ?string $projectKey): LineWebhookInput
    {
        return new LineWebhookInput(false, 'sig', json_encode([
            'events' => [['type' => 'unfollow', 'source' => ['userId' => $lineUserId]]],
        ], JSON_THROW_ON_ERROR), $projectKey);
    }

    private function makeUseCase(LineService $line): LineWebhookHandleUseCase
    {
        $resolver = new LineWebhookProjectResolver($line, app(LineConnectionSettings::class));

        return new LineWebhookHandleUseCase($line, $resolver, app(LineConnectionSettings::class), new LineReplyMessages($line, app(MemberPointLedger::class)));
    }

    private function mockLineService(string $matchingSecret): LineService
    {
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('verifySignatureWithSecret')
            ->andReturnUsing(static fn (string $body, ?string $signature, ?string $secret): bool => $secret === $matchingSecret);

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

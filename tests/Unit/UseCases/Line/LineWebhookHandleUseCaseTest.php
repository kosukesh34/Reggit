<?php

namespace Tests\Unit\UseCases\Line;

use App\Models\Project;
use App\Models\User;
use App\Services\Line\LineService;
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

        $useCase = new LineWebhookHandleUseCase($line);
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
}

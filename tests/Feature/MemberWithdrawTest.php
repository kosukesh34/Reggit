<?php

namespace Tests\Feature;

use App\Http\Controllers\Member\MemberWithdrawController;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\UserService;
use App\UseCases\Member\MemberWithdrawUseCase;
use App\Util\AdminContext;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MemberWithdrawTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    private const CONTRACT_ID = 'test_contract_withdraw';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    #[TestWith(['revoked'])]
    #[TestWith(['deleted'])]
    public function test_stale_user_cannot_withdraw_or_delete_external_member(string $change): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => 'current-customer']);
        $user->load('member');
        if ($change === 'revoked') {
            $user->fresh()->increment('authentication_version');
        } else {
            $user->fresh()->delete();
        }
        Http::fake();
        $rejected = false;
        try {
            app(MemberWithdrawUseCase::class)->handle($user);
        } catch (AuthenticationException $exception) {
            $rejected = true;
        }
        $this->assertTrue($rejected);
        $this->assertFalse($member->fresh()->trashed());
        Http::assertNothingSent();
    }

    #[TestWith(['legacy'])]
    #[TestWith(['v1'])]
    public function test_withdraw_controller_returns_unauthorized_for_revoked_user(string $endpoint): void
    {
        $project = $this->createProject();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->fresh()->increment('authentication_version');
        $this->mock(UserService::class)->shouldReceive('findUser')->once()->andReturn($user);
        $controller = $endpoint === 'legacy'
            ? app(MemberWithdrawController::class)
            : app(\App\Http\Controllers\Api\V1\Member\MemberWithdrawController::class);
        $response = $controller(Request::create('/withdraw', 'POST'));
        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($user->fresh()->trashed());
    }

    public function test_withdraw_スマレジ会員が既に無い404でも退会できること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.contract_id' => '',
        ]);

        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '132',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response([
                    'access_token' => 'fake_access_token',
                    'expires_in' => 3600,
                ], 200);
            }
            if (strtoupper($request->method()) === 'DELETE'
                && str_contains($url, '/pos/customers/132')) {
                return Http::response([
                    'type' => 'about:blank',
                    'title' => 'NotFound',
                    'detail' => '指定されたIDのリソースが見つかりません',
                    'status' => 404,
                ], 404);
            }

            return Http::response([], 500);
        });

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/withdraw?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($member->fresh()->trashed());
        $this->assertTrue($user->fresh()->trashed());
        $this->assertSame('withdrawn-'.$user->id, $user->fresh()->line_user_id);
    }

    public function test_withdraw_スマレジ未連携ならローカル退会のみで完了すること(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);

        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => null,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/withdraw?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($member->fresh()->trashed());
        $this->assertTrue($user->fresh()->trashed());
    }

    public function test_withdraw_外部連携失敗時は内部情報を返さずローカル会員を保持すること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.contract_id' => '',
        ]);

        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '500',
        ]);

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/token')) {
                return Http::response(['access_token' => 'fake_access_token', 'expires_in' => 3600]);
            }

            return Http::response(['detail' => 'provider internal failure'], 500);
        });

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/withdraw?project_key=default');

        $response->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '外部会員連携の解除に失敗しました。しばらくしてから再度お試しください。');
        $this->assertStringNotContainsString('storage/logs/laravel.log', $response->getContent());
        $this->assertStringNotContainsString('provider internal failure', $response->getContent());

        $this->assertFalse($member->fresh()->trashed());
        $this->assertFalse($user->fresh()->trashed());
        $this->assertSame(self::LINE_USER_ID, $user->fresh()->line_user_id);
    }
}

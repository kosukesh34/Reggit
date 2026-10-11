<?php

namespace Tests\Feature;

use App\Mail\ExistingMemberLinkMail;
use App\Models\AdminUser;
use App\Models\ExistingMemberLinkRequest;
use App\Models\ExistingMemberLinkSetting;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\UseCases\Member\ConfirmExistingMemberLinkUseCase;
use App\UseCases\Member\RequestExistingMemberLinkUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ExistingMemberLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_条件が一致してもメール確認後に会員を結びつけること(): void
    {
        Mail::fake();
        $project = $this->createProject();
        $this->enable($project, ['customer_code', 'phone', 'name']);
        $api = $this->mockCustomers([$this->customer()]);
        $api->shouldReceive('getCustomer')->once()->andReturn($this->customer());
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000031']);

        $response = $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson('/api/members/existing-link?project_key=default', [
                'customer_code' => 'C100',
                'phone' => '090-1111-2222',
                'name' => '山田太郎',
            ]);

        $response->assertOk()->assertJsonPath('data.linked', false);
        Mail::assertSent(ExistingMemberLinkMail::class);
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);

        $token = ExistingMemberLinkRequest::query()->where('user_id', $user->id)->value('token');
        $this->get('/existing-member-link/'.$token)->assertOk()->assertSee('既存会員を連携しますか');
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->post('/existing-member-link/'.$token)->assertOk()->assertSee('連携しました');
        $this->assertDatabaseHas('members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '9001',
            'points' => 120,
        ]);
    }

    public function test_スマレジにメールアドレスがない会員はセルフ連携できないこと(): void
    {
        $project = $this->createProject();
        $this->enable($project, ['customer_code']);
        $customer = $this->customer();
        $customer['mailAddress'] = '';
        $this->mockCustomers([$customer]);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000034']);

        $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson('/api/members/existing-link?project_key=default', ['customer_code' => 'C100'])
            ->assertStatus(422);

        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->assertDatabaseCount('existing_member_link_requests', 0);
    }

    public function test_メールアドレスが条件なら確認後に連携すること(): void
    {
        Mail::fake();
        $project = $this->createProject();
        $this->enable($project, ['email']);
        $api = $this->mockCustomers([$this->customer()]);
        $api->shouldReceive('getCustomer')->once()->andReturn($this->customer());
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000032']);

        $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson('/api/members/existing-link?project_key=default', ['email' => 'a@example.com'])
            ->assertOk()
            ->assertJsonPath('data.linked', false);
        Mail::assertSent(ExistingMemberLinkMail::class);
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);

        $token = ExistingMemberLinkRequest::query()->where('user_id', $user->id)->value('token');
        $this->get('/existing-member-link/'.$token)->assertOk()->assertSee('既存会員を連携しますか');
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->post('/existing-member-link/'.$token)->assertOk()->assertSee('連携しました');
        $this->assertDatabaseHas('members', [
            'user_id' => $user->id,
            'smaregi_customer_id' => '9001',
        ]);
    }

    public function test_別店舗の同じスマレジ会員は妨げないこと(): void
    {
        $project = $this->createProject();
        $other = Project::factory()->create();
        $otherUser = User::factory()->create(['project_id' => $other->id]);
        Member::factory()->create([
            'project_id' => $other->id,
            'user_id' => $otherUser->id,
            'smaregi_customer_id' => '9001',
        ]);
        $this->enable($project, ['customer_code']);
        $this->mockCustomers([$this->customer()]);
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.existing-member-link.store', ['project_key' => $project->project_key]), [
                'customer_code' => 'C100',
            ])
            ->assertRedirect();

        $this->assertSame(1, Member::query()->where('project_id', $project->id)->where('smaregi_customer_id', '9001')->count());
    }

    public function test_無効または不一致では連携しないこと(): void
    {
        $project = $this->createProject();
        $this->mockCustomers([$this->customer()]);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000033']);

        $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson('/api/members/existing-link?project_key=default', ['customer_code' => 'C100'])
            ->assertStatus(422);

        $this->enable($project, ['customer_code']);
        $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson('/api/members/existing-link?project_key=default', ['customer_code' => 'NOPE'])
            ->assertStatus(422);
        $this->assertDatabaseMissing('members', ['smaregi_customer_id' => '9001', 'project_id' => $project->id]);
    }

    public function test_link_confirmation_requires_requester_session_and_explicit_post(): void
    {
        $project = $this->createProject();
        $requester = User::factory()->create(['project_id' => $project->id]);
        $other = User::factory()->create(['project_id' => $project->id]);
        $pending = ExistingMemberLinkRequest::create([
            'project_id' => $project->id, 'user_id' => $requester->id,
            'smaregi_customer_id' => '9001', 'email' => 'a@example.com',
            'token' => str_repeat('a', 48), 'expires_at' => now()->addMinutes(30),
        ]);
        $api = $this->mockCustomers([$this->customer()]);
        $api->shouldReceive('getCustomer')->once()->andReturn($this->customer());
        $url = '/existing-member-link/'.$pending->token;
        $this->get($url)->assertOk()->assertSee('既存会員を連携しますか');
        $this->post($url)->assertOk()->assertSee('申請したアプリ・ブラウザ');
        $this->withSession(['mobile_order_user_id_'.$project->id => $other->id])
            ->post($url)->assertOk()->assertSee('申請したアプリ・ブラウザ');
        $this->assertNull($pending->fresh()->confirmed_at);
        $this->assertDatabaseMissing('members', ['smaregi_customer_id' => '9001']);
        $this->withSession(['mobile_order_user_id_'.$project->id => $requester->id])
            ->post($url)->assertOk()->assertSee('連携しました');
        $this->assertNotNull($pending->fresh()->confirmed_at);
        $this->post($url)->assertOk()->assertSee('確認リンクが無効');
        $this->assertDatabaseCount('members', 1);
    }

    public function test_expired_confirmation_cannot_link_member(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $pending = ExistingMemberLinkRequest::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'smaregi_customer_id' => '9001', 'email' => 'a@example.com',
            'token' => str_repeat('b', 48), 'expires_at' => now()->subMinute(),
        ]);
        $this->withSession(['mobile_order_user_id_'.$project->id => $user->id])
            ->post('/existing-member-link/'.$pending->token)->assertOk()->assertSee('確認リンクが無効');
        $this->assertDatabaseCount('members', 0);
    }

    public function test_native_confirmation_requires_matching_authenticated_user_and_project(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000035']);
        $otherProject = Project::factory()->create();
        $other = User::factory()->create(['project_id' => $otherProject->id, 'line_user_id' => 'U00000000000000000000000000000036']);
        $pending = ExistingMemberLinkRequest::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'smaregi_customer_id' => '9001', 'email' => 'a@example.com',
            'token' => str_repeat('c', 48), 'expires_at' => now()->addMinutes(30),
        ]);
        $api = $this->mockCustomers([$this->customer()]);
        $api->shouldReceive('getCustomer')->once()->andReturn($this->customer());
        $endpoint = '/api/members/existing-link/confirm?project_key=';
        $this->postJson($endpoint.'default', ['token' => $pending->token])->assertUnauthorized();
        $this->withHeaders(['X-Line-User-Id' => $other->line_user_id])
            ->postJson($endpoint.$otherProject->project_key, ['token' => $pending->token])->assertUnprocessable();
        $this->assertNull($pending->fresh()->confirmed_at);
        $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson($endpoint.'default', ['token' => $pending->token])->assertOk()->assertJsonPath('data.linked', true);
        $this->assertDatabaseHas('members', ['project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => '9001']);
    }

    public function test_older_pending_confirmation_cannot_replace_an_already_confirmed_link(): void
    {
        Mail::fake();
        $project = $this->createProject();
        $this->enable($project, ['customer_code']);
        $user = User::factory()->create(['project_id' => $project->id]);
        $older = $this->customer();
        $newer = array_replace($older, ['customerId' => '9002', 'customerCode' => 'C200', 'point' => 50]);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCustomers')->twice()->andReturn(['customers' => [$older]], ['customers' => [$newer]]);
        $api->shouldReceive('getCustomer')->with('contract-1', '9002')->once()->andReturn($newer);
        $api->shouldReceive('getCustomer')->with('contract-1', '9001')->andReturn($older);
        $requests = app(RequestExistingMemberLinkUseCase::class);
        $requests->handle((int) $project->id, $user->fresh(), ['customer_code' => 'C100']);
        $requests->handle((int) $project->id, $user->fresh(), ['customer_code' => 'C200']);
        $olderRequest = ExistingMemberLinkRequest::where('smaregi_customer_id', '9001')->firstOrFail();
        $newerRequest = ExistingMemberLinkRequest::where('smaregi_customer_id', '9002')->firstOrFail();
        $confirm = app(ConfirmExistingMemberLinkUseCase::class);
        $confirm->handle($newerRequest->token, $user->fresh());
        $this->assertSame('9002', $user->fresh()->member->smaregi_customer_id);
        $rejected = false;
        try {
            $confirm->handle($olderRequest->token, $user->fresh());
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
        }
        $this->assertSame('9002', $user->fresh()->member->smaregi_customer_id);
        $this->assertSame(50, $user->fresh()->member->points);
        $this->assertNull($olderRequest->fresh()->confirmed_at);
        $this->assertTrue($rejected);
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    /** @param  list<string>  $fields */
    private function enable(Project $project, array $fields): void
    {
        ExistingMemberLinkSetting::query()->create([
            'project_id' => $project->id,
            'is_enabled' => true,
            'description' => '店頭の会員コードを入力してください',
            'identifier_fields' => $fields,
        ]);
    }

    /** @param  list<array<string, string|int>>  $customers */
    private function mockCustomers(array $customers): SmaregiApiService
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCustomers')->andReturn(['customers' => $customers]);
        $this->app->instance(SmaregiApiService::class, $api);

        return $api;
    }

    /** @return array<string, string|int> */
    private function customer(): array
    {
        return [
            'customerId' => '9001',
            'customerCode' => 'C100',
            'mailAddress' => 'a@example.com',
            'phoneNumber' => '09011112222',
            'lastName' => '山田',
            'firstName' => '太郎',
            'birthDate' => '1990-01-02',
            'address' => '東京都港区1-2-3',
            'point' => 120,
        ];
    }
}

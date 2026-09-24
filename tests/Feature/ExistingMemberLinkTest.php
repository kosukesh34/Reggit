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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class ExistingMemberLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_条件が一致すれば会員を作って結びつけること(): void
    {
        $project = $this->createProject();
        $this->enable($project, ['customer_code', 'phone', 'name']);
        $this->mockCustomers([$this->customer()]);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U00000000000000000000000000000031']);

        $response = $this->withHeaders(['X-Line-User-Id' => $user->line_user_id])
            ->postJson('/api/members/existing-link?project_key=default', [
                'customer_code' => 'C100',
                'phone' => '090-1111-2222',
                'name' => '山田太郎',
            ]);

        $response->assertOk()->assertJsonPath('data.linked', true);
        $this->assertDatabaseHas('members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '9001',
            'points' => 120,
        ]);
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
        $this->get('/existing-member-link/'.$token)->assertOk()->assertSee('連携しました');
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

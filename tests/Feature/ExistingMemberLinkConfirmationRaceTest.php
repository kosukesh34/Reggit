<?php

namespace Tests\Feature;

use App\Domain\Settings\Repositories\SmaregiSettings;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\ExistingMemberLinkRequest;
use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiClient;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiTransactionCustomerResolver;
use App\UseCases\Member\ConfirmExistingMemberLinkUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ExistingMemberLinkConfirmationRaceTest extends TestCase
{
    use DatabaseTruncation;

    #[BeforeClass]
    public static function resetMigrationBeforeClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    #[AfterClass]
    public static function resetMigrationAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    public function test_contract_committed_during_customer_lookup_prevents_confirmation(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        $pending = ExistingMemberLinkRequest::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'contract_id' => 'contract-1', 'smaregi_customer_id' => '9001',
            'email' => 'original@example.com', 'token' => str_repeat('r', 48),
            'expires_at' => now()->addMinutes(30),
        ]);
        config(['database.connections.review_writer' => config('database.connections.'.DB::getDefaultConnection())]);
        $api = Mockery::mock(SmaregiApiService::class, [
            app(SmaregiApiClient::class), app(SmaregiTransactionCustomerResolver::class), app(SmaregiSettings::class),
        ])->makePartial();
        $api->shouldReceive('getCustomer')->with('contract-1', '9001')->once()->andReturnUsing(function () use ($project): array {
            $writer = DB::connection('review_writer');
            $this->assertSame(0, $writer->transactionLevel());
            $this->assertSame(1, $writer->table((new ProjectProfileSmaregiSetting)->getTable())
                ->where('project_id', $project->id)->update(['smaregi_contract_id' => 'contract-2']));

            return ['customerId' => '9001', 'customerCode' => 'ORIGINAL', 'point' => 50];
        });
        $this->app->instance(SmaregiApiService::class, $api);
        $rejected = false;
        try {
            app(ConfirmExistingMemberLinkUseCase::class)->handle($pending->token, $user);
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
        } finally {
            DB::purge('review_writer');
        }
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->assertNull($pending->fresh()->confirmed_at);
        $this->assertTrue($rejected);
        $this->assertSame('contract-2', ShopSettingStore::getValue('smaregi_contract_id', null, (int) $project->id));
    }

    #[TestWith(['expires_at'])]
    #[TestWith(['smaregi_customer_id'])]
    public function test_request_changed_during_customer_lookup_is_not_confirmed(string $field): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $pending = ExistingMemberLinkRequest::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'contract_id' => 'contract-1', 'smaregi_customer_id' => '9001',
            'token' => str_repeat('s', 48), 'expires_at' => now()->addMinutes(30),
        ]);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCustomer')->with('contract-1', '9001')->once()->andReturnUsing(function () use ($pending, $field): array {
            $this->assertSame(0, DB::transactionLevel());
            $pending->update([$field => $field === 'expires_at' ? now()->subMinute() : '9002']);

            return ['customerId' => '9001', 'customerCode' => 'ORIGINAL', 'point' => 50];
        });
        $rejected = false;
        try {
            app(ConfirmExistingMemberLinkUseCase::class)->handle($pending->token, $user);
        } catch (InvalidArgumentException $exception) {
            $rejected = true;
        }
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->assertNull($pending->fresh()->confirmed_at);
        $this->assertTrue($rejected);
    }
}

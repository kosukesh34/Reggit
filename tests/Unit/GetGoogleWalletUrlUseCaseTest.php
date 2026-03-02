<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\GoogleWalletPassService;
use App\Services\MemberService;
use App\UseCases\Member\GoogleWallet\Data\GetGoogleWalletUrlInput;
use App\UseCases\Member\GoogleWallet\Data\GetGoogleWalletUrlOutput;
use App\UseCases\Member\GoogleWallet\GetGoogleWalletUrlUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * GetGoogleWalletUrlUseCase の Unit テスト。
 * 利用可否・トークン/セッション分岐・他店舗データの干渉を検証する。
 */
class GetGoogleWalletUrlUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_利用不可時にunavailableを返すこと(): void
    {
        Config::set('google_wallet.enabled', false);
        $walletService = Mockery::mock(GoogleWalletPassService::class);
        $walletService->shouldReceive('isAvailable')->once()->andReturn(false);
        $walletService->shouldReceive('getUnavailabilityReasons')->once()->andReturn(['disabled']);

        $memberService = $this->app->make(MemberService::class);
        $useCase = new GetGoogleWalletUrlUseCase($walletService, $memberService);
        $input = new GetGoogleWalletUrlInput(null, null, 1, 'default', null);

        $output = $useCase->handle($input);

        $this->assertFalse($output->success);
        $this->assertSame(GetGoogleWalletUrlOutput::ERROR_UNAVAILABLE, $output->errorType);
        $this->assertSame(['disabled'], $output->reasons);
    }

    public function test_handle_会員でない且つデモ不可でnot_memberを返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => '', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);
        Config::set('google_wallet.allow_demo_user', false);

        $walletService = Mockery::mock(GoogleWalletPassService::class);
        $walletService->shouldReceive('isAvailable')->once()->andReturn(true);

        $memberService = Mockery::mock(MemberService::class);
        $memberService->shouldReceive('getMemberData')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id))
            ->andReturn(['is_member' => false]);

        $useCase = new GetGoogleWalletUrlUseCase($walletService, $memberService);
        $input = new GetGoogleWalletUrlInput(null, $user, $project->id, 'default', 'Test');

        $output = $useCase->handle($input);

        $this->assertFalse($output->success);
        $this->assertSame(GetGoogleWalletUrlOutput::ERROR_NOT_MEMBER, $output->errorType);
    }

    public function test_handle_トークンでキャッシュミス時はurl_failedを返すこと(): void
    {
        $walletService = Mockery::mock(GoogleWalletPassService::class);
        $walletService->shouldReceive('isAvailable')->once()->andReturn(true);

        $memberService = $this->app->make(MemberService::class);
        $useCase = new GetGoogleWalletUrlUseCase($walletService, $memberService);
        $input = new GetGoogleWalletUrlInput('invalid-token', null, 1, 'default', null);

        Cache::shouldReceive('get')->with('wallet_pass_token:invalid-token')->andReturn(null);

        $output = $useCase->handle($input);

        $this->assertFalse($output->success);
        $this->assertSame(GetGoogleWalletUrlOutput::ERROR_URL_FAILED, $output->errorType);
    }

    public function test_handle_会員の場合はsuccessを返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Test', 'description' => '', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $expectedUrl = 'https://pay.google.com/gp/v/save/dummy-jwt';
        $walletService = Mockery::mock(GoogleWalletPassService::class);
        $walletService->shouldReceive('isAvailable')->once()->andReturn(true);
        $walletService->shouldReceive('createSaveUrlForUser')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id))
            ->andReturn($expectedUrl);

        $memberService = $this->app->make(MemberService::class);
        $useCase = new GetGoogleWalletUrlUseCase($walletService, $memberService);
        $input = new GetGoogleWalletUrlInput(null, $user, $project->id, 'default', null);

        $output = $useCase->handle($input);

        $this->assertTrue($output->success);
        $this->assertSame($expectedUrl, $output->url);
    }
}

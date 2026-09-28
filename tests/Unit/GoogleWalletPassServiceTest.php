<?php

namespace Tests\Unit;

use App\Domain\Settings\Repositories\WalletSettings;
use App\Enums\GoogleWalletApi;
use App\Models\Project;
use App\Models\User;
use App\Services\GoogleWallet\GoogleWalletLoyaltyPayloadBuilder;
use App\Services\GoogleWalletPassService;
use App\Services\MemberCardAppearanceService;
use App\Services\MemberService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * GoogleWalletPassService の Unit テスト。
 * 設定・利用可否・他店舗データの干渉がないことを検証する。本番 API は呼ばない。
 */
class GoogleWalletPassServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createService(): GoogleWalletPassService
    {
        return new GoogleWalletPassService(
            new GoogleWalletLoyaltyPayloadBuilder,
            app(WalletSettings::class),
            app(MemberService::class),
            app(MemberCardAppearanceService::class),
        );
    }

    public function test_is_available_無効設定でfalseを返すこと(): void
    {
        Config::set('google_wallet.enabled', false);
        Config::set('google_wallet.issuer_id', '123');

        $service = $this->createService();

        $this->assertFalse($service->isAvailable());
    }

    public function test_is_available_issuer_id未設定でfalseを返すこと(): void
    {
        Config::set('google_wallet.enabled', true);
        Config::set('google_wallet.issuer_id', '');

        $service = $this->createService();

        $this->assertFalse($service->isAvailable());
    }

    public function test_get_unavailability_reasons_無効時に理由を返すこと(): void
    {
        Config::set('google_wallet.enabled', false);

        $service = $this->createService();
        $reasons = $service->getUnavailabilityReasons();

        $this->assertIsArray($reasons);
        $this->assertNotEmpty($reasons);
        $this->assertStringContainsString('disabled', $reasons[0]);
    }

    public function test_create_save_url_利用不可時はnullを返すこと(): void
    {
        Config::set('google_wallet.enabled', false);

        $service = $this->createService();
        $url = $service->createSaveUrl(
            'Test Org',
            'barcode-1',
            'obj-suffix',
            0,
            '',
            '',
            null
        );

        $this->assertNull($url);
    }

    public function test_create_save_url_for_user_id_プロジェクト未設定時はnullを返すこと(): void
    {
        Config::set('google_wallet.enabled', false);
        AdminContext::setCurrentProject(null);

        $service = $this->createService();
        $url = $service->createSaveUrlForUserId(99999);

        $this->assertNull($url);
    }

    public function test_create_save_url_for_user_id_他プロジェクトのユーザーは参照しないこと(): void
    {
        $projectA = Project::firstOrCreate(
            ['project_key' => 'proj-a'],
            ['name' => 'Project A', 'description' => '', 'is_active' => true]
        );
        $projectB = Project::firstOrCreate(
            ['project_key' => 'proj-b'],
            ['name' => 'Project B', 'description' => '', 'is_active' => true]
        );
        $userB = User::factory()->create(['project_id' => $projectB->id]);

        AdminContext::setCurrentProject($projectA);
        Config::set('google_wallet.enabled', false);

        $service = $this->createService();
        $url = $service->createSaveUrlForUserId($userB->id);

        $this->assertNull($url);
    }

    public function test_truncate_for_wallet_ビルダー経由で文字列が切り詰められること(): void
    {
        $builder = new GoogleWalletLoyaltyPayloadBuilder;
        $maxLen = GoogleWalletApi::MAX_DISPLAY_LENGTH;

        $this->assertSame('あいう', $builder->truncateForWallet('あいう', $maxLen));
        $long = str_repeat('あ', $maxLen + 5);
        $this->assertSame($maxLen, mb_strlen($builder->truncateForWallet($long, $maxLen)));
    }
}

<?php

namespace Tests\Unit\Payment;

use App\Models\Order;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\ProjectCredentialedGateway;
use App\UseCases\Payment\ProjectPaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class ProjectPaymentGatewayResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_決済設定のある注文はその秘密鍵で動く決済を作ること(): void
    {
        $project = Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);
        $setting = $this->createSetting((int) $project->id);
        $legacy = new CredentialedFakeGateway;

        $gateway = app(ProjectPaymentGatewayResolver::class)->forOrder(
            $this->order((int) $project->id, (int) $setting->id, 'paypay'),
            $legacy,
        );

        $this->assertNotSame($legacy, $gateway);
        $this->assertSame([
            'secretKey' => 'sk_project',
            'brand' => 'paypay',
            'metadata' => ['project_id' => (string) $project->id, 'payment_setting_id' => (string) $setting->id],
        ], $legacy->received);
    }

    public function test_資格情報を自分で持つ決済は決済設定があってもそのまま使うこと(): void
    {
        $project = Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);
        $setting = $this->createSetting((int) $project->id);
        $legacy = new FakePaymentGateway;

        $gateway = app(ProjectPaymentGatewayResolver::class)->forOrder($this->order((int) $project->id, (int) $setting->id), $legacy);

        $this->assertSame($legacy, $gateway);
    }

    public function test_決済設定のない注文は既定プロジェクト以外では共通の秘密鍵を使わないこと(): void
    {
        config(['app.default_project_key' => 'default']);
        $project = Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);

        $this->expectException(InvalidArgumentException::class);
        app(ProjectPaymentGatewayResolver::class)->forOrder($this->order((int) $project->id, null), new CredentialedFakeGateway);
    }

    public function test_決済設定のない注文でも資格情報を自分で持つ決済はそのまま使うこと(): void
    {
        config(['app.default_project_key' => 'default']);
        $project = Project::factory()->create(['project_key' => 'shop-a', 'is_active' => true]);
        $legacy = new FakePaymentGateway;

        $this->assertSame($legacy, app(ProjectPaymentGatewayResolver::class)->forOrder($this->order((int) $project->id, null), $legacy));
    }

    private function createSetting(int $projectId): ProjectPaymentSetting
    {
        return ProjectPaymentSetting::create([
            'project_id' => $projectId,
            'account_id' => 'acct_test',
            'publishable_key' => 'pk_project',
            'secret_key' => 'sk_project',
            'webhook_secret' => 'whsec_test',
            'methods' => ['card'],
        ]);
    }

    private function order(int $projectId, ?int $settingId, ?string $brand = null): Order
    {
        return (new Order)->forceFill([
            'project_id' => $projectId,
            'payment_setting_id' => $settingId,
            'payment_brand' => $brand,
        ]);
    }
}

class CredentialedFakeGateway extends FakePaymentGateway implements ProjectCredentialedGateway
{
    /** @var array{secretKey: string, brand: string, metadata: array<string, string>}|null */
    public ?array $received = null;

    public function withProjectCredentials(string $secretKey, string $brand, array $metadata): PaymentGateway
    {
        $this->received = compact('secretKey', 'brand', 'metadata');

        return new FakePaymentGateway;
    }
}

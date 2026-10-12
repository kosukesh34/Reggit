<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Services\GoogleWalletPassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GoogleWalletProjectBrandTest extends TestCase
{
    use RefreshDatabase;

    private string $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credentials = tempnam(sys_get_temp_dir(), 'google-wallet-test-');
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $pem);
        File::put($this->credentials, json_encode(['client_email' => 'test@example.test', 'private_key' => $pem]));
        config(['google_wallet.enabled' => true, 'google_wallet.issuer_id' => '123',
            'google_wallet.class_suffix' => 'membership', 'google_wallet.credentials_path' => $this->credentials]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::delete($this->credentials);
        parent::tearDown();
    }

    public function test_new_passes_use_separate_project_classes_and_objects(): void
    {
        $classes = [];
        Http::fake(function (Request $request) use (&$classes) {
            if (str_contains($request->url(), 'oauth2.googleapis.com')) {
                return Http::response(['access_token' => 'token']);
            }
            if ($request->method() === 'POST') {
                $classes[$request['id']] = $request->data();

                return Http::response($request->data());
            }

            return Http::response($classes[basename($request->url())] ?? [], isset($classes[basename($request->url())]) ? 200 : 404);
        });
        foreach (['Brand A', 'Brand B'] as $name) {
            $project = Project::factory()->create(['name' => $name]);
            $url = app(GoogleWalletPassService::class)->createSaveUrl($name, '123', 'member-1-b', projectId: (int) $project->id);
            $this->assertNotNull($url);
            $jwt = explode('.', basename($url));
            $payload = json_decode(base64_decode(strtr($jwt[1], '-_', '+/')), true);
            $object = $payload['payload']['loyaltyObjects'][0];
            $classId = '123.membership-project-'.$project->id;
            $this->assertSame($classId, $object['classId']);
            $this->assertSame('123.member-1-b-p'.$project->id, $object['id']);
            $this->assertSame($name, $classes[$classId]['programName']);
            $this->assertSame($name, $classes[$classId]['issuerName']);
        }
        $this->assertCount(2, $classes);
    }

    #[TestWith([200])]
    #[TestWith([404])]
    #[TestWith([503])]
    public function test_sync_preserves_existing_pass_class_and_updates_new_generation(int $legacyStatus): void
    {
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        $legacyId = '123.member-'.$member->id.'-b';
        $newId = $legacyId.'-p'.$project->id;
        Http::fake(function (Request $request) use ($legacyId, $legacyStatus) {
            if (str_contains($request->url(), 'oauth2.googleapis.com')) {
                return Http::response(['access_token' => 'token']);
            }
            if ($request->method() === 'PATCH' && str_ends_with($request->url(), '/'.$legacyId)) {
                return Http::response([], $legacyStatus);
            }

            return Http::response([]);
        });
        $this->assertSame($legacyStatus !== 503, app(GoogleWalletPassService::class)->syncLoyaltyObjectForMember($member));
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/'.$legacyId) && ! isset($request['classId']) && ! isset($request['id']));
        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/'.$newId)
            && $request['classId'] === '123.membership-project-'.$project->id);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/loyaltyClass/123.membership')
            && ! str_contains($request->url(), 'membership-project-') && $request->method() !== 'GET');
    }
}

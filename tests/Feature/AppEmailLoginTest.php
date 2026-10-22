<?php

namespace Tests\Feature;

use App\Mail\ShopMagicLinkMail;
use App\Models\Member;
use App\Models\MemberTransferCode;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppEmailLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_web登録会員はメールのリンクでアプリにログインできること(): void
    {
        Mail::fake();
        $this->createProject();
        $webUser = User::factory()->create([
            'project_id' => Project::where('project_key', 'default')->value('id'),
            'line_user_id' => 'guest-mail-abc',
            'email' => 'web@example.com',
            'email_verified_at' => now(),
        ]);
        $member = Member::factory()->create(['project_id' => $webUser->project_id, 'user_id' => $webUser->id]);
        $deviceToken = $this->registerAppDevice('app-device');

        $this->withAppHeaders('app-device', $deviceToken)
            ->postJson('/api/members/email-login?project_key=default', ['email' => 'WEB@example.com'])
            ->assertOk();

        $loginUrl = null;
        Mail::assertSent(ShopMagicLinkMail::class, function (ShopMagicLinkMail $mail) use (&$loginUrl): bool {
            $loginUrl = $mail->loginUrl;

            return $mail->hasTo('web@example.com');
        });
        $this->assertIsString($loginUrl);
        parse_str((string) parse_url($loginUrl, PHP_URL_QUERY), $query);
        $this->assertSame((string) $member->id, $query['member_id']);

        $this->get($loginUrl)->assertOk()->assertSee('reggit://login?', false);

        $this->withAppHeaders('app-device', $deviceToken)
            ->postJson('/api/members/transfer?project_key=default', [
                'member_id' => (int) $query['member_id'],
                'code' => (string) $query['code'],
            ])
            ->assertOk();

        $this->assertSame($webUser->id, UserDevice::query()->where('device_identifier', 'app-device')->value('user_id'));
    }

    public function test_未確認のメールや会員でないユーザーにはリンクを送らないこと(): void
    {
        Mail::fake();
        $project = $this->createProject();
        $unverified = User::factory()->create([
            'project_id' => $project->id,
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $unverified->id]);
        User::factory()->create([
            'project_id' => $project->id,
            'email' => 'guest@example.com',
            'email_verified_at' => now(),
        ]);
        $deviceToken = $this->registerAppDevice('app-device');

        foreach (['unverified@example.com', 'guest@example.com', 'nobody@example.com'] as $email) {
            $this->withAppHeaders('app-device', $deviceToken)
                ->postJson('/api/members/email-login?project_key=default', ['email' => $email])
                ->assertOk()
                ->assertJsonPath('message', '登録済みのメールアドレスであれば、ログイン用のリンクを送信しました。');
        }

        Mail::assertNothingSent();
        $this->assertSame(0, MemberTransferCode::query()->count());
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    /** LINE を使わないネイティブアプリと同じく、端末IDだけで端末登録する。 */
    private function registerAppDevice(string $deviceIdentifier): string
    {
        $response = $this->withHeaders(['X-Device-Identifier' => $deviceIdentifier])
            ->postJson('/api/devices/register?project_key=default', [
                'platform' => 'ios',
                'device_identifier' => $deviceIdentifier,
            ]);
        $response->assertOk();

        return (string) $response->json('device_token');
    }

    private function withAppHeaders(string $deviceIdentifier, string $deviceToken): static
    {
        return $this->withHeaders([
            'X-Device-Identifier' => $deviceIdentifier,
            'X-Device-Token' => $deviceToken,
        ]);
    }
}

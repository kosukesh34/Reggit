<?php

namespace App\Services;

use App\Models\Member;
use App\Models\WalletPassRegistration;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppleWalletPushService
{
    public function notifyMemberUpdated(Member $member): void
    {
        $passTypeId = (string) config('wallet_pass.type_id', '');
        if ($passTypeId === '') {
            Log::info('[WalletPass] APNs push skipped: pass type id not configured', [
                'member_id' => (int) $member->id,
            ]);

            return;
        }

        $registrations = WalletPassRegistration::query()
            ->where('member_id', (int) $member->id)
            ->where('pass_type_identifier', $passTypeId)
            ->get(['push_token']);

        if ($registrations->isEmpty()) {
            Log::info('[WalletPass] APNs push skipped: no device registration for member', [
                'member_id' => (int) $member->id,
                'pass_type_identifier' => $passTypeId,
            ]);

            return;
        }

        $jwt = $this->buildProviderToken();
        if ($jwt === null) {
            Log::info('[WalletPass] APNs push skipped: provider token unavailable', [
                'member_id' => (int) $member->id,
            ]);

            return;
        }

        $base = config('wallet_pass.apns_use_sandbox', true)
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';

        $sent = 0;
        $failed = 0;

        foreach ($registrations as $registration) {
            $pushToken = trim((string) $registration->push_token);
            if ($pushToken === '') {
                continue;
            }

            $response = Http::withHeaders([
                'authorization' => 'bearer '.$jwt,
                'apns-topic' => $passTypeId,
                'apns-push-type' => 'background',
                'apns-priority' => '5',
            ])
                ->withBody('{}', 'application/json')
                ->post($base.'/3/device/'.$pushToken);

            if ($response->successful()) {
                $sent++;
            } else {
                $failed++;
                Log::warning('[WalletPass] APNs push failed', [
                    'member_id' => (int) $member->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        }

        Log::info('[WalletPass] APNs push finished', [
            'member_id' => (int) $member->id,
            'member_points' => (int) $member->points,
            'registration_count' => $registrations->count(),
            'sent' => $sent,
            'failed' => $failed,
            'sandbox' => (bool) config('wallet_pass.apns_use_sandbox', true),
        ]);
    }

    private function buildProviderToken(): ?string
    {
        $keyId = (string) config('wallet_pass.apns_key_id', '');
        $teamId = (string) config('wallet_pass.apns_team_id', '');
        $path = (string) config('wallet_pass.apns_auth_key_path', '');
        if ($keyId === '' || $teamId === '' || $path === '') {
            return null;
        }

        $resolvedPath = $this->resolvePath($path);
        if (! is_readable($resolvedPath)) {
            Log::warning('[WalletPass] APNs key unreadable', ['path' => $resolvedPath]);

            return null;
        }

        try {
            $privateKey = file_get_contents($resolvedPath);
            if (! is_string($privateKey) || $privateKey === '') {
                return null;
            }

            return JWT::encode(
                ['iss' => $teamId, 'iat' => time()],
                $privateKey,
                'ES256',
                $keyId,
                ['kid' => $keyId]
            );
        } catch (\Throwable $e) {
            Log::warning('[WalletPass] APNs JWT build failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }
        if (str_starts_with($path, '/') || (strlen($path) >= 2 && $path[1] === ':')) {
            return $path;
        }

        return base_path($path);
    }
}

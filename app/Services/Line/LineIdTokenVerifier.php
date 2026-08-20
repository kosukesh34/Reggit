<?php

namespace App\Services\Line;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Support\MemberChannel;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Http;

class LineIdTokenVerifier
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function verify(string $idToken, int $projectId): ?string
    {
        $token = trim($idToken);
        if ($token === '') {
            return null;
        }
        $channelId = $this->channelId($projectId);
        if ($channelId === '') {
            LogHelper::warning('[LINE] id token skipped: no channel id', [
                'project_id' => $projectId,
            ]);

            return null;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://api.line.me/oauth2/v2.1/verify', [
                    'id_token' => $token,
                    'client_id' => $channelId,
                ]);
        } catch (\Throwable $e) {
            LogHelper::warning('[LINE] id token verify failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if ($response->successful() !== true) {
            return null;
        }
        $sub = $response->json('sub');
        if (! is_string($sub) || MemberChannel::isValidLineUserId($sub) === false) {
            return null;
        }

        return $sub;
    }

    private function channelId(int $projectId): string
    {
        $liffId = trim($this->shopSettingRepository->getLineLiffId($projectId));
        if ($liffId === '') {
            $liffId = trim((string) config('line.liff_id', ''));
        }
        $fromLiff = strstr($liffId, '-', true);
        if (is_string($fromLiff) && ctype_digit($fromLiff)) {
            return $fromLiff;
        }

        return trim((string) config('line.channel_id', ''));
    }
}

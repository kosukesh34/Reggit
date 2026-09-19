<?php

namespace App\UseCases\Member\WalletPass;

use App\Services\MemberService;
use App\Services\WalletPassService;
use App\UseCases\Member\WalletPass\Data\WalletPassUrlIssueInput;
use App\UseCases\Member\WalletPass\Data\WalletPassUrlIssueOutput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Safari で開く用のワンタイム URL を発行する UseCase。
 * LINE アプリ内では .pkpass が開けないため、この URL を返して Safari で開かせる。
 */
class WalletPassUrlIssueUseCase
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';

    private const TOKEN_TTL_MINUTES = 3;

    public function __construct(
        private WalletPassService $walletPassService,
        private MemberService $memberService,
    ) {}

    public function handle(WalletPassUrlIssueInput $input): WalletPassUrlIssueOutput
    {
        if ($this->walletPassService->isAvailable() !== true) {
            return WalletPassUrlIssueOutput::unavailable(
                $this->walletPassService->getUnavailabilityReasons(),
            );
        }

        $memberData = $this->memberService->getMemberData($input->user);
        if (! isset($memberData['is_member']) || $memberData['is_member'] === false) {
            return WalletPassUrlIssueOutput::notMember();
        }

        $token = Str::random(64);
        $key = self::TOKEN_CACHE_PREFIX.$token;
        Cache::put($key, [
            'user_id' => $input->user->id,
            'project_id' => $input->projectId,
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return WalletPassUrlIssueOutput::success(
            $this->buildWalletAddUrl($token, $input->projectKey, $input->requestOrigin),
        );
    }

    public function buildWalletAddUrl(string $token, string $projectKey, string $requestOrigin): string
    {
        return rtrim($requestOrigin, '/').'/wallet-add?'.http_build_query([
            'pass_token' => $token,
            'project_key' => $projectKey,
            'type' => 'apple',
        ]);
    }

    public function buildPassDownloadUrl(string $token, string $projectKey, string $requestOrigin): ?string
    {
        $cacheKey = self::TOKEN_CACHE_PREFIX.$token;
        $cached = Cache::get($cacheKey);
        if (! is_array($cached)) {
            return null;
        }

        return rtrim($requestOrigin, '/').'/api/member-wallet/pass?'.http_build_query([
            'project_key' => $projectKey,
            'token' => $token,
        ]);
    }
}

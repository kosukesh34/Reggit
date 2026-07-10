<?php

namespace App\UseCases\Member\GoogleWallet;

use App\Http\Support\RequestOrigin;
use App\Services\GoogleWalletPassService;
use App\Services\MemberService;
use App\UseCases\Member\GoogleWallet\Data\GetGoogleWalletUrlInput;
use App\UseCases\Member\GoogleWallet\Data\GetGoogleWalletUrlOutput;
use App\Util\StrHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Google Wallet に追加するための URL（JWT 付き）を発行する UseCase。
 * トークン認証またはセッション（会員）認証に対応する。
 */
class GetGoogleWalletUrlUseCase
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';

    public function __construct(
        private readonly GoogleWalletPassService $googleWalletPassService,
        private readonly MemberService $memberService,
        private readonly GoogleWalletSaveUrlIssuer $saveUrlIssuer
    ) {}

    public function handle(GetGoogleWalletUrlInput $input): GetGoogleWalletUrlOutput
    {
        $this->logRequest($input);

        if (! $this->googleWalletPassService->isAvailable()) {
            $reasons = $this->googleWalletPassService->getUnavailabilityReasons();
            Log::warning('google-wallet-url unavailable', ['reasons' => $reasons]);

            return GetGoogleWalletUrlOutput::unavailable($reasons);
        }

        if (! StrHelper::isEmptyStringOrNull($input->token)) {
            $url = $this->resolveUrlByToken($input);
            if ($url === null) {
                Log::warning('google-wallet-url resolveUrlByToken returned null', [
                    'project_key' => $input->projectKey,
                    'project_id' => $input->projectId,
                ]);
                $this->logUrlFailed($input);

                return GetGoogleWalletUrlOutput::urlFailed(
                    $this->googleWalletPassService->getLastError() ?? 'URL を発行できませんでした'
                );
            }

            Log::info('google-wallet-url success', [
                'project_key' => $input->projectKey,
                'project_id' => $input->projectId,
            ]);

            return GetGoogleWalletUrlOutput::success($this->wrapClientUrl($url, $input->projectKey, $input->requestOrigin));
        }

        $sessionResult = $this->resolveUrlBySession($input);
        if ($sessionResult instanceof GetGoogleWalletUrlOutput) {
            return $sessionResult;
        }
        if ($sessionResult !== null) {
            Log::info('google-wallet-url success', [
                'project_key' => $input->projectKey,
                'project_id' => $input->projectId,
            ]);

            return GetGoogleWalletUrlOutput::success($this->wrapClientUrl($sessionResult, $input->projectKey, $input->requestOrigin));
        }

        $this->logUrlFailed($input);

        $lastError = $this->googleWalletPassService->getLastError();

        return GetGoogleWalletUrlOutput::urlFailed(
            $lastError ?? 'URL を発行できませんでした',
            $lastError !== null && $lastError !== '' ? [$lastError] : []
        );
    }

    private function logRequest(GetGoogleWalletUrlInput $input): void
    {
        Log::info('google-wallet-url request', [
            'has_token' => ! StrHelper::isEmptyStringOrNull($input->token),
            'project_key' => $input->projectKey,
            'project_id' => $input->projectId,
        ]);
    }

    private function logUrlFailed(GetGoogleWalletUrlInput $input): void
    {
        Log::warning('google-wallet-url url is null', [
            'project_key' => $input->projectKey,
            'project_id' => $input->projectId,
            'last_error' => $this->googleWalletPassService->getLastError(),
        ]);
    }

    private function resolveUrlByToken(GetGoogleWalletUrlInput $input): ?string
    {
        $key = self::TOKEN_CACHE_PREFIX.$input->token;
        $data = Cache::get($key);

        if ($data === null || ! isset($data['user_id'], $data['project_id'])) {
            Log::info('google-wallet-url token cache miss or invalid', [
                'has_data' => $data !== null,
                'project_id' => $input->projectId,
            ]);

            return null;
        }

        $contextProjectId = $input->projectId;
        if ($contextProjectId === null || $contextProjectId === false
            || (int) $data['project_id'] !== (int) $contextProjectId) {
            Log::info('google-wallet-url project mismatch', [
                'token_project_id' => $data['project_id'] ?? null,
                'context_project_id' => $contextProjectId,
            ]);

            return null;
        }

        return $this->googleWalletPassService->createSaveUrlForUserId((int) $data['user_id']);
    }

    /**
     * セッションで URL を解決する。会員でない且つデモ不可の場合は notMember の Output を返す。
     *
     * @return string|GetGoogleWalletUrlOutput|null 成功時は URL、会員登録必要時は Output、その他失敗時は null
     */
    private function resolveUrlBySession(GetGoogleWalletUrlInput $input): string|GetGoogleWalletUrlOutput|null
    {
        if ($input->user === null) {
            return null;
        }

        $memberData = $this->memberService->getMemberData($input->user);

        if (isset($memberData['is_member']) && $memberData['is_member'] === true) {
            return $this->googleWalletPassService->createSaveUrlForUser($input->user);
        }

        $isDemoAllowed = config('google_wallet.allow_demo_user', false) === true;
        if (! $isDemoAllowed) {
            Log::info('google-wallet-url not a member', ['user_id' => $input->user->id ?? null]);

            return GetGoogleWalletUrlOutput::notMember();
        }

        $organizationName = $input->projectName ?? config('app.name', 'Reggit');
        $userId = $input->user->id ?? 0;
        $objectSuffix = 'demo-'.$userId.'-b';
        $projectIdInt = $input->projectId !== null && $input->projectId !== false ? (int) $input->projectId : null;
        $url = $this->googleWalletPassService->createSaveUrl(
            $organizationName,
            'demo-'.$userId,
            $objectSuffix,
            0,
            '',
            $input->user->name ?? '',
            $projectIdInt
        );
        Log::info('google-wallet-url demo user url issued', ['user_id' => $userId]);

        return $url;
    }

    private function wrapClientUrl(string $saveUrl, string $projectKey, string $requestOrigin): string
    {
        $origin = $requestOrigin !== '' ? $requestOrigin : RequestOrigin::fromConfig();

        return $this->saveUrlIssuer->wrapForClient($saveUrl, $projectKey, $origin);
    }
}

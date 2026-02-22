<?php

namespace App\UseCases\Member\WalletPass;

use App\Services\WalletPassService;
use App\UseCases\Member\WalletPass\Data\WalletPassDownloadInput;
use App\UseCases\Member\WalletPass\Data\WalletPassDownloadOutput;
use App\Util\AdminContext;
use Illuminate\Support\Facades\Cache;

/**
 * 会員証 Wallet Pass（.pkpass）のダウンロードを実行する UseCase。
 * トークン指定時とセッション（User）指定時の両方に対応。
 */
class WalletPassDownloadUseCase
{
    private const TOKEN_CACHE_PREFIX = 'wallet_pass_token:';

    public function __construct(
        private WalletPassService $walletPassService
    ) {
    }

    public function handle(WalletPassDownloadInput $input): WalletPassDownloadOutput
    {
        if ($input->token !== null && $input->token !== '') {
            return $this->handleWithToken($input->token);
        }
        if ($input->user !== null) {
            $content = $this->walletPassService->generateFromUser($input->user);
            return $this->toOutput($content);
        }
        return $this->toOutput(null);
    }

    private function handleWithToken(string $token): WalletPassDownloadOutput
    {
        $key = self::TOKEN_CACHE_PREFIX . $token;
        $data = Cache::get($key);
        if ($data === null || !isset($data['user_id'], $data['project_id'])) {
            return $this->toOutput(null);
        }
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || (int) $data['project_id'] !== (int) $projectId) {
            return $this->toOutput(null);
        }
        Cache::forget($key);
        $content = $this->walletPassService->generateForUser((int) $data['user_id']);
        return $this->toOutput($content);
    }

    private function toOutput(?string $content): WalletPassDownloadOutput
    {
        if ($content !== null) {
            return new WalletPassDownloadOutput($content, []);
        }
        $reasons = $this->walletPassService->getUnavailabilityReasons();
        return new WalletPassDownloadOutput(null, $reasons);
    }
}

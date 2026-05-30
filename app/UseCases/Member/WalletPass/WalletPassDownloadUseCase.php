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
    ) {}

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
        $key = self::TOKEN_CACHE_PREFIX.$token;
        $data = Cache::get($key);
        if ($data === null || ! isset($data['user_id'], $data['project_id'])) {
            return new WalletPassDownloadOutput(null, [
                'ダウンロードリンクの有効期限が切れています。LINE アプリの会員証画面から「Apple Walletに追加」をもう一度タップしてください。',
            ]);
        }
        $projectId = AdminContext::getCurrentProjectId();
        if ($projectId === null || (int) $data['project_id'] !== (int) $projectId) {
            return new WalletPassDownloadOutput(null, [
                'プロジェクトの設定が一致しません。会員証画面から再度お試しください。',
            ]);
        }

        // Safari が wallet-add から二重にリクエストすることがあるため、TTL 内は再利用可能にする
        $content = $this->walletPassService->generateForUser((int) $data['user_id']);

        return $this->toOutput($content);
    }

    private function toOutput(?string $content): WalletPassDownloadOutput
    {
        if ($content !== null) {
            return new WalletPassDownloadOutput($content, []);
        }

        $reasons = $this->walletPassService->getUnavailabilityReasons();
        $lastError = $this->walletPassService->getLastError();
        if ($lastError !== null && $lastError !== '') {
            $reasons[] = $lastError;
        }
        if ($reasons === []) {
            $reasons[] = '会員証の生成に失敗しました。しばらくしてから再度お試しください。';
        }

        return new WalletPassDownloadOutput(null, $reasons);
    }
}

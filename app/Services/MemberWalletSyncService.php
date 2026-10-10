<?php

namespace App\Services;

use App\Models\Member;
use App\Util\LogHelper;
use Illuminate\Support\Facades\Log;

/**
 * ポイント・会員情報変更後に Apple Wallet / Google Wallet へ最新状態を反映する。
 */
class MemberWalletSyncService
{
    public function __construct(
        private WalletPassSyncService $walletPassSyncService,
        private AppleWalletPushService $appleWalletPushService,
        private GoogleWalletPassService $googleWalletPassService,
        private MemberLiffWalletNotifier $liffWalletNotifier,
    ) {}

    public function syncAfterMemberChanged(Member $member, string $reason = 'member_changed'): void
    {
        $this->liffWalletNotifier->notify($member);

        $appleCanSync = $this->canSyncAppleWallet();
        $googleCanSync = $this->googleWalletPassService->canSyncExistingObjects();
        if (! $appleCanSync && ! $googleCanSync) {
            Log::debug('[MemberWallet] sync skipped: wallet backends not configured', [
                'member_id' => (int) $member->id,
                'reason' => $reason,
            ]);

            return;
        }

        $member = $member->fresh(['user', 'project']);
        if ($member === null) {
            return;
        }

        $appleSynced = false;
        if ($appleCanSync) {
            try {
                $this->walletPassSyncService->markMemberUpdated($member);
                $this->appleWalletPushService->notifyMemberUpdated($member);
                $appleSynced = true;
            } catch (\Throwable $e) {
                LogHelper::warning('[MemberWallet] Apple sync failed', [
                    'member_id' => (int) $member->id,
                    'reason' => $reason,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $googleSynced = false;
            if ($googleCanSync) {
                $googleSynced = $this->googleWalletPassService->syncLoyaltyObjectForMember($member);
            }

            Log::info('[MemberWallet] sync completed', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'reason' => $reason,
                'member_points' => $member->getDisplayPoints(),
                'apple_wallet_sync' => $appleSynced,
                'google_wallet_synced' => $googleSynced,
            ]);
        } catch (\Throwable $e) {
            LogHelper::warning('[MemberWallet] sync failed', [
                'member_id' => (int) $member->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function canSyncAppleWallet(): bool
    {
        if (trim((string) config('wallet_pass.type_id', '')) === '') {
            return false;
        }

        $webServiceUrl = trim((string) config('wallet_pass.web_service_url', ''));
        if ($webServiceUrl !== '' && str_starts_with(strtolower($webServiceUrl), 'https://')) {
            return true;
        }

        $keyId = trim((string) config('wallet_pass.apns_key_id', ''));
        $teamId = trim((string) config('wallet_pass.apns_team_id', ''));
        $authKeyPath = trim((string) config('wallet_pass.apns_auth_key_path', ''));

        return $keyId !== '' && $teamId !== '' && $authKeyPath !== '';
    }
}

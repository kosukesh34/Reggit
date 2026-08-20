<?php

namespace App\UseCases\Stamp;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\StampCycleMode;
use App\Enums\StampGrantLimit;
use App\Models\StampGrantCode;
use App\Models\StampGrantRedemption;
use App\Services\Admin\MemberAdminService;
use App\Services\MemberRankSyncService;
use App\UseCases\Stamp\Data\StampGrantByCodeInput;
use App\UseCases\Stamp\Data\StampGrantOutput;
use Illuminate\Support\Facades\DB;

class StampGrantByCodeUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private StampGrantOutcomeMapper $outcomeMapper,
        private ShopSettingRepository $shopSettingRepository,
        private MemberRankSyncService $memberRankSyncService,
    ) {}

    public function handle(StampGrantByCodeInput $input): StampGrantOutput
    {
        try {
            $result = DB::transaction(fn () => $this->grant($input));
        } catch (\Throwable $e) {
            report($e);

            return new StampGrantOutput(false, 500, 'スタンプを追加できませんでした');
        }
        if ($result->isSuccess) {
            $member = $input->user->member()->where('project_id', $input->user->project_id)->first();
            if ($member !== null) {
                $this->memberRankSyncService->sync($member);
            }
        }

        return $result;
    }

    private function grant(StampGrantByCodeInput $input): StampGrantOutput
    {
        $code = StampGrantCode::query()->where('token', $input->token)->lockForUpdate()->first();
        if ($code === null) {
            return new StampGrantOutput(false, 404, '付与QRが見つかりません');
        }
        if ($code->isExpired()) {
            return new StampGrantOutput(false, 410, 'このQRの有効期限が切れています');
        }

        $card = $code->stampCard;
        if ($card === null || $card->is_active !== true || (int) $card->project_id !== (int) $code->project_id) {
            return new StampGrantOutput(false, 404, '有効なスタンプカードが見つかりません');
        }
        if ($this->shopSettingRepository->getUseStamps((int) $code->project_id) !== true) {
            return new StampGrantOutput(false, 403, 'スタンプ機能は利用できません');
        }

        $member = $input->user->member()->where('project_id', $code->project_id)->first();
        if ($member === null) {
            return new StampGrantOutput(false, 403, 'スタンプの付与には会員登録が必要です');
        }

        $limit = StampGrantLimit::normalize($code->usage_limit);
        $blocked = $this->blockedMessage($code, $limit, (int) $member->id);
        if ($blocked !== null) {
            return new StampGrantOutput(false, 409, $blocked);
        }

        $existing = $this->outcomeMapper->existingStamp($member, $card);
        if (StampCycleMode::isGrantClosed((string) $card->cycle_mode, $existing)) {
            return $this->outcomeMapper->closed($card);
        }

        $amount = StampGrantLimit::normalizeAmount((int) $code->amount);
        $memberStamp = $this->memberAdminService->addStampsToMember(
            $member,
            (int) $card->id,
            $amount,
            '店舗QRから付与'
        );
        if ($limit !== StampGrantLimit::UNLIMITED) {
            StampGrantRedemption::query()->create([
                'project_id' => $code->project_id,
                'stamp_grant_code_id' => $code->id,
                'member_id' => $member->id,
                'created_at' => now(),
            ]);
        }

        return $this->outcomeMapper->success($card, $memberStamp, $amount, $existing);
    }

    private function blockedMessage(StampGrantCode $code, string $limit, int $memberId): ?string
    {
        if ($limit === StampGrantLimit::UNLIMITED) {
            return null;
        }
        if ($limit === StampGrantLimit::ONCE) {
            if ($code->redemptions()->where('member_id', $memberId)->exists()) {
                return StampGrantLimit::redeemedMessage(StampGrantLimit::PER_MEMBER);
            }
            if ($code->redemptions()->count() >= StampGrantLimit::normalizeQuota((int) $code->usage_quota)) {
                return StampGrantLimit::redeemedMessage(StampGrantLimit::ONCE);
            }

            return null;
        }
        $query = $code->redemptions()->where('member_id', $memberId);
        if ($limit === StampGrantLimit::DAILY) {
            $query->where('created_at', '>=', now()->startOfDay());
        }

        return $query->exists() ? StampGrantLimit::redeemedMessage($limit) : null;
    }
}

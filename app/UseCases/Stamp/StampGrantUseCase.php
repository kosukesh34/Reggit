<?php

namespace App\UseCases\Stamp;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\StampCycleMode;
use App\Enums\StampGrantLimit;
use App\Models\StampCard;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Stamp\Data\StampGrantInput;
use App\UseCases\Stamp\Data\StampGrantOutput;
use App\Util\DateUtil;
use Illuminate\Support\Facades\Cache;

class StampGrantUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
        private StampGrantOutcomeMapper $outcomeMapper,
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(StampGrantInput $input): StampGrantOutput
    {
        if ($this->shopSettingRepository->getUseStamps($input->projectId) !== true) {
            return new StampGrantOutput(false, 403, 'スタンプ機能は利用できません');
        }
        $card = StampCard::query()
            ->where('project_id', $input->projectId)
            ->where('is_active', true)
            ->find($input->stampCardId);
        if ($card === null) {
            return new StampGrantOutput(false, 404, '有効なスタンプカードが見つかりません');
        }

        $member = $input->user->member()->where('project_id', $input->projectId)->first();
        if ($member === null) {
            return new StampGrantOutput(false, 403, 'スタンプの付与には会員登録が必要です');
        }

        $nonce = trim($input->nonce);
        if ($nonce === '') {
            return new StampGrantOutput(false, 422, '付与QRが正しくありません');
        }

        $existing = $this->outcomeMapper->existingStamp($member, $card);
        if (StampCycleMode::isGrantClosed((string) $card->cycle_mode, $existing)) {
            return $this->outcomeMapper->closed($card);
        }

        $amount = StampGrantLimit::normalizeAmount($input->amount);
        $limit = StampGrantLimit::normalize($input->limit);
        $quota = StampGrantLimit::normalizeQuota($input->quota);
        $ttl = $this->cacheTtl($input->expiresAt, $limit);
        $nonceHash = hash('sha256', $nonce);
        $memberId = (int) $member->id;
        $redemptionKey = $this->redemptionKey($input->projectId, $nonceHash, $limit, $memberId);
        $onceMemberKey = $limit === StampGrantLimit::ONCE
            ? $this->onceMemberKey($input->projectId, $nonceHash, $memberId)
            : null;
        $onceCountKey = $limit === StampGrantLimit::ONCE
            ? $this->onceCountKey($input->projectId, $nonceHash)
            : null;

        $blocked = $this->reserveSlot($limit, $quota, $ttl, $redemptionKey, $onceMemberKey, $onceCountKey);
        if ($blocked !== null) {
            return new StampGrantOutput(false, 409, $blocked);
        }

        try {
            $memberStamp = $this->memberAdminService->addStampsToMember(
                $member,
                (int) $card->id,
                $amount,
                '店舗QRから付与'
            );
        } catch (\Throwable $e) {
            $this->releaseSlot($redemptionKey, $onceMemberKey, $onceCountKey);
            report($e);

            return new StampGrantOutput(false, 500, 'スタンプを追加できませんでした');
        }

        return $this->outcomeMapper->success($card, $memberStamp, $amount, $existing);
    }

    private function reserveSlot(
        string $limit,
        int $quota,
        \DateTimeInterface $ttl,
        ?string $redemptionKey,
        ?string $onceMemberKey,
        ?string $onceCountKey,
    ): ?string {
        if ($limit === StampGrantLimit::UNLIMITED) {
            return null;
        }
        if ($onceMemberKey !== null && $onceCountKey !== null) {
            if (! Cache::add($onceMemberKey, true, $ttl)) {
                return StampGrantLimit::redeemedMessage(StampGrantLimit::PER_MEMBER);
            }
            Cache::add($onceCountKey, 0, $ttl);
            $count = Cache::increment($onceCountKey);
            if (is_int($count) === false || $count > $quota) {
                Cache::decrement($onceCountKey);
                Cache::forget($onceMemberKey);

                return StampGrantLimit::redeemedMessage(StampGrantLimit::ONCE);
            }

            return null;
        }
        if ($redemptionKey !== null && ! Cache::add($redemptionKey, true, $ttl)) {
            return StampGrantLimit::redeemedMessage($limit);
        }

        return null;
    }

    private function releaseSlot(?string $redemptionKey, ?string $onceMemberKey, ?string $onceCountKey): void
    {
        if ($onceMemberKey !== null) {
            Cache::forget($onceMemberKey);
        }
        if ($onceCountKey !== null) {
            Cache::decrement($onceCountKey);
        }
        if ($redemptionKey !== null) {
            Cache::forget($redemptionKey);
        }
    }

    private function redemptionKey(int $projectId, string $nonceHash, string $limit, int $memberId): ?string
    {
        if ($limit === StampGrantLimit::UNLIMITED || $limit === StampGrantLimit::ONCE) {
            return null;
        }
        if ($limit === StampGrantLimit::DAILY) {
            return sprintf('stamp-grant:%d:%s:%d:%s', $projectId, $nonceHash, $memberId, now()->toDateString());
        }

        return sprintf('stamp-grant:%d:%s:%d', $projectId, $nonceHash, $memberId);
    }

    private function onceMemberKey(int $projectId, string $nonceHash, int $memberId): string
    {
        return sprintf('stamp-grant:%d:%s:%d', $projectId, $nonceHash, $memberId);
    }

    private function onceCountKey(int $projectId, string $nonceHash): string
    {
        return sprintf('stamp-grant:%d:%s:count', $projectId, $nonceHash);
    }

    private function cacheTtl(?int $expiresAt, string $limit): \DateTimeInterface
    {
        $until = $expiresAt !== null ? DateUtil::nullableCarbon('@'.$expiresAt) : null;
        if ($until !== null && $until->lte(now())) {
            $until = null;
        }
        if ($limit === StampGrantLimit::DAILY) {
            $endOfDay = now()->endOfDay();
            if ($until === null || $until->gt($endOfDay)) {
                $until = $endOfDay;
            }
        }
        if ($until !== null && $until->gt(now())) {
            return $until;
        }

        return now()->addHour();
    }
}

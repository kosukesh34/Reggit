<?php

namespace App\UseCases\Stamp;

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
    ) {}

    public function handle(StampGrantInput $input): StampGrantOutput
    {
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

        $amount = StampGrantLimit::normalizeAmount($input->amount);
        $limit = StampGrantLimit::normalize($input->limit);
        $redemptionKey = $this->redemptionKey($input->projectId, $nonce, $limit, (int) $member->id);
        if ($redemptionKey !== null && ! Cache::add($redemptionKey, true, $this->cacheTtl($input->expiresAt))) {
            $message = $limit === StampGrantLimit::ONCE
                ? 'このQRは使用済みです'
                : 'このQRからのスタンプは付与済みです';

            return new StampGrantOutput(false, 409, $message);
        }

        try {
            $memberStamp = $this->memberAdminService->addStampsToMember(
                $member,
                (int) $card->id,
                $amount,
                '店舗QRから付与'
            );
        } catch (\Throwable $e) {
            if ($redemptionKey !== null) {
                Cache::forget($redemptionKey);
            }
            report($e);

            return new StampGrantOutput(false, 500, 'スタンプを追加できませんでした');
        }

        return new StampGrantOutput(
            true,
            200,
            'スタンプを'.$amount.'個追加しました',
            (int) $card->id,
            (int) $memberStamp->current_stamps,
            $amount,
        );
    }

    private function redemptionKey(int $projectId, string $nonce, string $limit, int $memberId): ?string
    {
        if ($limit === StampGrantLimit::UNLIMITED) {
            return null;
        }
        $nonceHash = hash('sha256', $nonce);
        if ($limit === StampGrantLimit::ONCE) {
            return sprintf('stamp-grant:%d:%s', $projectId, $nonceHash);
        }

        return sprintf('stamp-grant:%d:%s:%d', $projectId, $nonceHash, $memberId);
    }

    private function cacheTtl(?int $expiresAt): \DateTimeInterface
    {
        $until = $expiresAt !== null ? DateUtil::nullableCarbon('@'.$expiresAt) : null;
        if ($until !== null && $until->gt(now())) {
            return $until;
        }

        return now()->addHour();
    }
}

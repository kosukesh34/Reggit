<?php

namespace App\UseCases\Stamp;

use App\Models\StampCard;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Stamp\Data\StampGrantInput;
use App\UseCases\Stamp\Data\StampGrantOutput;
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

        $redemptionKey = sprintf('stamp-grant:%d:%s:%d', $input->projectId, hash('sha256', $nonce), $member->id);
        if (! Cache::add($redemptionKey, true, now()->addHour())) {
            return new StampGrantOutput(false, 409, 'このQRからのスタンプは付与済みです');
        }

        try {
            $memberStamp = $this->memberAdminService->addStampsToMember(
                $member,
                (int) $card->id,
                1,
                '店舗QRから付与'
            );
        } catch (\Throwable $e) {
            Cache::forget($redemptionKey);
            report($e);

            return new StampGrantOutput(false, 500, 'スタンプを追加できませんでした');
        }

        return new StampGrantOutput(
            true,
            200,
            'スタンプを1個追加しました',
            (int) $card->id,
            (int) $memberStamp->current_stamps,
        );
    }
}

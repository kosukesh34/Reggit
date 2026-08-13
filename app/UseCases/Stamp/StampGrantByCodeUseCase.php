<?php

namespace App\UseCases\Stamp;

use App\Enums\StampGrantLimit;
use App\Models\StampGrantCode;
use App\Models\StampGrantRedemption;
use App\Services\Admin\MemberAdminService;
use App\UseCases\Stamp\Data\StampGrantByCodeInput;
use App\UseCases\Stamp\Data\StampGrantOutput;
use Illuminate\Support\Facades\DB;

class StampGrantByCodeUseCase
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    public function handle(StampGrantByCodeInput $input): StampGrantOutput
    {
        try {
            return DB::transaction(fn () => $this->grant($input));
        } catch (\Throwable $e) {
            report($e);

            return new StampGrantOutput(false, 500, 'スタンプを追加できませんでした');
        }
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

        $member = $input->user->member()->where('project_id', $code->project_id)->first();
        if ($member === null) {
            return new StampGrantOutput(false, 403, 'スタンプの付与には会員登録が必要です');
        }

        $limit = StampGrantLimit::normalize($code->usage_limit);
        if ($this->isRedeemed($code, $limit, (int) $member->id)) {
            $message = $limit === StampGrantLimit::ONCE
                ? 'このQRは使用済みです'
                : 'このQRからのスタンプは付与済みです';

            return new StampGrantOutput(false, 409, $message);
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

        return new StampGrantOutput(
            true,
            200,
            'スタンプを'.$amount.'個追加しました',
            (int) $card->id,
            (int) $memberStamp->current_stamps,
            $amount,
        );
    }

    private function isRedeemed(StampGrantCode $code, string $limit, int $memberId): bool
    {
        if ($limit === StampGrantLimit::UNLIMITED) {
            return false;
        }
        $query = $code->redemptions();
        if ($limit === StampGrantLimit::PER_MEMBER) {
            $query->where('member_id', $memberId);
        }

        return $query->exists();
    }
}

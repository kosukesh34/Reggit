<?php

namespace App\Services\Member;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Settings\Repositories\OrderSettings;
use App\Models\PointGrantCode;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointQrRedeemer
{
    public function __construct(private readonly MemberRepository $members, private readonly OrderSettings $settings, private readonly CurrentMemberPrincipal $principals) {}

    /** @return array{member_id: int, granted_points: int, points: int} */
    public function redeem(User $user, string $token): array
    {
        return DB::transaction(fn () => $this->grant($user, $token));
    }

    /** @return array{member_id: int, granted_points: int, points: int} */
    private function grant(User $user, string $token): array
    {
        $projectId = (int) $user->project_id;
        $owner = $this->principals->lock($user, $projectId);
        abort_if($owner === null, 403, 'ログインし直してください。');
        $memberModel = $owner->member;
        abort_if($memberModel === null, 403, 'ポイントの受け取りには会員登録が必要です。');

        $code = PointGrantCode::query()->where('project_id', $projectId)->where('token', $token)->lockForUpdate()->firstOrFail();
        $this->assertAvailable($projectId, $code);
        $memberId = (int) $memberModel->id;
        $this->assertCanRedeem($projectId, $code, (int) $memberId);
        $this->members->syncPointsLedger((int) $memberId, $projectId);
        $member = $this->members->findByIdForUpdate((int) $memberId, $projectId);
        abort_if($member === null, 403, '会員が見つかりません。');
        $member->earnPoints($code->points, '店舗QRから付与', null, CarbonImmutable::now()->addDays($this->settings->getPointsValidDays($projectId)));
        $this->members->save($member);
        DB::table('point_grant_redemptions')->insert(['project_id' => $projectId, 'point_grant_code_id' => $code->id, 'member_id' => $memberId, 'created_at' => CarbonImmutable::now(), 'updated_at' => CarbonImmutable::now()]);

        return ['member_id' => (int) $memberId, 'granted_points' => $code->points, 'points' => $member->displayPoints()];
    }

    private function assertAvailable(int $projectId, PointGrantCode $code): void
    {
        abort_unless($this->settings->getUsePoints($projectId), 403, 'ポイント機能は利用できません。');
        abort_if($code->expires_at->lte(CarbonImmutable::now()), 410, 'このQRの有効期限が切れています。');
    }

    private function assertCanRedeem(int $projectId, PointGrantCode $code, int $memberId): void
    {
        $redemptions = DB::table('point_grant_redemptions')->where('project_id', $projectId)->where('point_grant_code_id', $code->id);
        if ((clone $redemptions)->where('member_id', $memberId)->exists()) {
            throw ValidationException::withMessages(['qr' => 'このQRからのポイントは受け取り済みです。']);
        }
        if ($redemptions->count() >= $code->usage_quota) {
            throw ValidationException::withMessages(['qr' => '利用回数の上限に達したため、このQRは終了しました。']);
        }
    }
}

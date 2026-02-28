<?php

namespace App\Services\Smaregi;

use App\Models\Coupon;
use Illuminate\Support\Facades\Log;

/**
 * アプリで作成・更新したクーポンをスマレジPOSに同期する。
 * プロジェクトにスマレジ契約が紐づいている場合のみ実行する。
 *
 * スマレジPOS API: https://developers.smaregi.dev/platform-api-reference/apis/pos/
 * クーポン登録・更新のパラメータは公式仕様書を参照。パラメータ名が異なる場合は要調整。
 */
class SmaregiCouponSyncService
{
    /** 特典タイプ: 2=割引（率） */
    private const AWARD_TYPE_RATE = '2';

    /** 特典タイプ: 1=値引（金額） */
    private const AWARD_TYPE_AMOUNT = '1';

    private SmaregiApiService $smaregiApi;

    public function __construct(SmaregiApiService $smaregiApi)
    {
        $this->smaregiApi = $smaregiApi;
    }

    /**
     * スマレジに契約がある場合、フォームデータを先にスマレジへ登録し、成功時のみ返却する。
     * 新規作成時用。返却された smaregi_coupon_id を $data に含めてDB保存すること。
     *
     * @param array<string, mixed> $data バリデーション済みフォームデータ（code, name, type, value, start_at, end_at 等）
     * @return array{smaregi_coupon_id: string}|null 成功時は ['smaregi_coupon_id' => '...']、失敗または契約なしは null
     */
    public function registerToSmaregi(int $projectId, array $data): ?array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if ($contractId === null || $contractId === '') {
            return null;
        }
        $body = $this->buildBodyFromArray($data);
        if ($body === []) {
            return null;
        }
        $result = $this->smaregiApi->createCoupon($contractId, $body);
        if ($result === null) {
            return null;
        }
        $smaregiId = $this->extractCouponIdFromResponse($result);
        if ($smaregiId === null) {
            return null;
        }
        Log::info('[Smaregi] Coupon registered on POS', ['smaregi_coupon_id' => $smaregiId]);
        return ['smaregi_coupon_id' => $smaregiId];
    }

    /**
     * スマレジに契約がある場合、フォームデータでスマレジを更新し、成功時のみ true を返す。
     * 更新時用。true の場合のみDBを更新すること。
     *
     * @param array<string, mixed> $data バリデーション済みフォームデータ
     */
    public function updateOnSmaregi(int $projectId, string $smaregiCouponId, array $data): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if ($contractId === null || $contractId === '') {
            return true;
        }
        $body = $this->buildBodyFromArray($data);
        if ($body === []) {
            return false;
        }
        $result = $this->smaregiApi->updateCoupon($contractId, $smaregiCouponId, $body);
        if ($result === null) {
            return false;
        }
        Log::info('[Smaregi] Coupon updated on POS', ['smaregi_coupon_id' => $smaregiCouponId]);
        return true;
    }

    /**
     * プロジェクトにスマレジ契約が紐づいているか
     */
    public function hasSmaregiContract(int $projectId): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        return $contractId !== null && $contractId !== '';
    }

    /**
     * クーポンをスマレジPOSに登録または更新する（既存の同期処理。更新時はDB先に保存したあとで呼ぶ想定）。
     * 既に smaregi_coupon_id があれば PATCH、なければ POST。
     */
    public function sync(Coupon $coupon): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($coupon->project_id);
        if ($contractId === null || $contractId === '') {
            return false;
        }

        $body = $this->buildCouponBody($coupon);
        if ($body === []) {
            return false;
        }

        if ($coupon->smaregi_coupon_id !== null && $coupon->smaregi_coupon_id !== '') {
            $result = $this->smaregiApi->updateCoupon($contractId, $coupon->smaregi_coupon_id, $body);
            if ($result !== null) {
                Log::info('[Smaregi] Coupon updated on POS', [
                    'coupon_id' => $coupon->id,
                    'smaregi_coupon_id' => $coupon->smaregi_coupon_id,
                ]);
                return true;
            }
            Log::warning('[Smaregi] Coupon update failed, may try create', [
                'coupon_id' => $coupon->id,
                'smaregi_coupon_id' => $coupon->smaregi_coupon_id,
            ]);
            $coupon->smaregi_coupon_id = null;
            $coupon->save();
        }

        $result = $this->smaregiApi->createCoupon($contractId, $body);
        if ($result === null) {
            Log::warning('[Smaregi] Coupon create failed', [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
            ]);
            return false;
        }

        $smaregiId = $this->extractCouponIdFromResponse($result);
        if ($smaregiId !== null) {
            $coupon->smaregi_coupon_id = $smaregiId;
            $coupon->save();
            Log::info('[Smaregi] Coupon created on POS', [
                'coupon_id' => $coupon->id,
                'smaregi_coupon_id' => $smaregiId,
            ]);
        }
        return true;
    }

    /**
     * フォームデータ（配列）をスマレジPOS API のリクエストボディに変換する。
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    /**
     * スマレジPOS API クーポン登録・更新用ボディ。公式仕様に準拠（required・文字数制限・型）。
     */
    private function buildBodyFromArray(array $data): array
    {
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            return [];
        }
        $name = trim((string) ($data['name'] ?? $code));
        $awardType = ($data['type'] ?? 'percent') === 'percent' ? self::AWARD_TYPE_RATE : self::AWARD_TYPE_AMOUNT;
        $value = (float) ($data['value'] ?? 0);
        $body = [
            'serialNumber' => mb_substr($code, 0, 20),
            'couponName' => mb_substr($name !== '' ? $name : $code, 0, 85),
            'awardType' => $awardType,
            'awardValue' => (string) (int) round($value),
            'applyConditionDivision' => '0',
            'couponDivision' => '0',
            'combineDivision' => '3',
        ];
        $summary = trim((string) ($data['description'] ?? ''));
        if ($summary !== '') {
            $body['summary'] = mb_substr($summary, 0, 255);
        }
        $minOrder = (int) ($data['min_order_amount'] ?? 0);
        if ($minOrder > 0 && $minOrder <= 9999999999) {
            $body['minTargetPrice'] = (string) $minOrder;
        }
        $startAt = $data['start_at'] ?? null;
        if ($startAt !== null && $startAt !== '') {
            $body['startDate'] = $startAt instanceof \DateTimeInterface ? $startAt->format('Y-m-d') : (\Carbon\Carbon::parse($startAt)->format('Y-m-d'));
        }
        $endAt = $data['end_at'] ?? null;
        if ($endAt !== null && $endAt !== '') {
            $body['endDate'] = $endAt instanceof \DateTimeInterface ? $endAt->format('Y-m-d') : (\Carbon\Carbon::parse($endAt)->format('Y-m-d'));
        }
        return $body;
    }

    /**
     * アプリの Coupon をスマレジPOS API のリクエストボディに変換する。
     *
     * @return array<string, mixed>
     */
    private function buildCouponBody(Coupon $coupon): array
    {
        return $this->buildBodyFromArray($coupon->getAttributes());
    }

    /**
     * @param array<int|string, mixed> $response
     */
    private function extractCouponIdFromResponse(array $response): ?string
    {
        $id = $response['couponId'] ?? $response['coupon_id'] ?? null;
        if ($id !== null) {
            return (string) $id;
        }
        if (isset($response['id'])) {
            return (string) $response['id'];
        }
        return null;
    }
}

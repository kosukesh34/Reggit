<?php

namespace App\Services\Smaregi;

use App\Enums\CouponApplyConditionDivision;
use App\Enums\CouponCombineDivision;
use App\Models\Coupon;
use App\Util\StrHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmaregiCouponSyncService
{
    private const AWARD_TYPE_RATE = '2';

    private const AWARD_TYPE_AMOUNT = '1';

    private const SERIAL_NUMBER_MAX_LENGTH = 20;

    private const COUPON_NAME_MAX_LENGTH = 85;

    private const SUMMARY_MAX_LENGTH = 255;

    private const MIN_TARGET_PRICE_MAX = 9999999999;

    private SmaregiApiService $smaregiApi;

    public function __construct(SmaregiApiService $smaregiApi)
    {
        $this->smaregiApi = $smaregiApi;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $data  フォーム（code, name, type, value, start_at, end_at 等）
     * @return array{smaregi_coupon_id: string}|null
     */
    public function registerToSmaregi(int $projectId, array $data): ?array
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
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

        return ['smaregi_coupon_id' => $smaregiId];
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $data  フォームデータ
     */
    public function updateOnSmaregi(int $projectId, string $smaregiCouponId, array $data): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return true;
        }
        $body = $this->buildBodyFromArray($data);
        if ($body === []) {
            return false;
        }
        $result = $this->smaregiApi->updateCoupon($contractId, $smaregiCouponId, $body);

        return $result !== null;
    }

    /**
     * スマレジに契約がある場合、スマレジ上のクーポンを削除する。成功時 true。
     */
    public function deleteOnSmaregi(int $projectId, string $smaregiCouponId): bool
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return true;
        }

        $result = $this->smaregiApi->deleteCoupon($contractId, $smaregiCouponId);

        return $result !== null;
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
        if (StrHelper::isEmptyStringOrNull($contractId)) {
            return false;
        }

        $body = $this->buildCouponBody($coupon);
        if ($body === []) {
            return false;
        }

        $smaregiId = $coupon->smaregi_coupon_id ?? '';
        if (! StrHelper::isEmptyStringOrNull($smaregiId)) {
            $result = $this->smaregiApi->updateCoupon($contractId, $smaregiId, $body);
            if ($result !== null) {
                return true;
            }
            Log::warning('[Smaregi] coupon sync update failed', [
                'project_id' => $coupon->project_id,
                'coupon_id' => $coupon->id,
            ]);
            $coupon->smaregi_coupon_id = null;
            $coupon->save();
        }

        $result = $this->smaregiApi->createCoupon($contractId, $body);
        if ($result === null) {
            Log::warning('[Smaregi] coupon sync create failed', [
                'project_id' => $coupon->project_id,
                'coupon_id' => $coupon->id,
            ]);

            return false;
        }

        $newId = $this->extractCouponIdFromResponse($result);
        if ($newId !== null) {
            $coupon->smaregi_coupon_id = $newId;
            $coupon->save();
        }

        return true;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $data
     * @return array<string, string|int|float|bool|null>
     */
    private function buildBodyFromArray(array $data): array
    {
        $code = isset($data['code']) ? trim((string) $data['code']) : '';
        if (StrHelper::isEmptyStringOrNull($code)) {
            return [];
        }
        $name = trim((string) ($data['name'] ?? $code));
        $awardType = ($data['type'] ?? 'percent') === 'percent' ? self::AWARD_TYPE_RATE : self::AWARD_TYPE_AMOUNT;
        $value = (float) ($data['value'] ?? 0);
        $couponName = $name !== '' ? $name : $code;
        $body = [
            'serialNumber' => mb_substr($code, 0, self::SERIAL_NUMBER_MAX_LENGTH),
            'couponName' => mb_substr($couponName, 0, self::COUPON_NAME_MAX_LENGTH),
            'awardType' => $awardType,
            'awardValue' => (string) (int) round($value),
            'applyConditionDivision' => $this->normalizeApplyConditionDivision($data['apply_condition_division'] ?? null),
            'couponDivision' => '0',
            'combineDivision' => $this->normalizeCombineDivision($data['combine_division'] ?? null),
        ];
        $summary = isset($data['description']) ? trim((string) $data['description']) : '';
        if ($summary !== '') {
            $body['summary'] = mb_substr($summary, 0, self::SUMMARY_MAX_LENGTH);
        }
        $minOrder = (int) ($data['min_order_amount'] ?? 0);
        if ($minOrder > 0 && $minOrder <= self::MIN_TARGET_PRICE_MAX) {
            $body['minTargetPrice'] = (string) $minOrder;
        }
        $maxOrder = (int) ($data['max_order_amount'] ?? 0);
        if ($maxOrder > 0 && $maxOrder <= self::MIN_TARGET_PRICE_MAX) {
            $body['maxTargetPrice'] = (string) $maxOrder;
        }
        $startAt = $data['start_at'] ?? null;
        if ($startAt !== null && $startAt !== '') {
            $body['startDate'] = $startAt instanceof \DateTimeInterface ? $startAt->format('Y-m-d') : (Carbon::parse($startAt)->format('Y-m-d'));
        }
        $endAt = $data['end_at'] ?? null;
        if ($endAt !== null && $endAt !== '') {
            $body['endDate'] = $endAt instanceof \DateTimeInterface ? $endAt->format('Y-m-d') : (Carbon::parse($endAt)->format('Y-m-d'));
        }

        return $body;
    }

    /**
     * アプリの Coupon をスマレジPOS API のリクエストボディに変換する。
     *
     * @return array<string, string|int|float|bool|null>
     */
    private function buildCouponBody(Coupon $coupon): array
    {
        return $this->buildBodyFromArray($coupon->getAttributes());
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $response  スマレジAPI応答
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

    private function normalizeApplyConditionDivision(mixed $value): string
    {
        $division = (string) ($value ?? CouponApplyConditionDivision::ALL);

        return in_array($division, CouponApplyConditionDivision::keys(), true)
            ? $division
            : CouponApplyConditionDivision::ALL;
    }

    private function normalizeCombineDivision(mixed $value): string
    {
        $division = (string) ($value ?? CouponCombineDivision::ALLOWED);

        return in_array($division, CouponCombineDivision::keys(), true)
            ? $division
            : CouponCombineDivision::ALLOWED;
    }
}

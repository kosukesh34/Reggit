<?php

namespace App\Services\Smaregi;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ POS のクーポンと Reggit の coupons テーブルを双方向同期する。
 * 同期キー: project_id + smaregi_coupon_id。コード（serialNumber）でも紐付けを試みる。
 */
class SmaregiCouponImportSyncService
{
    private const LIMIT = 100;

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiCouponSyncService $smaregiCouponSync,
    ) {}

    /**
     * スマレジ → DB: POS のクーポンを取り込む。
     *
     * @return array{created: int, updated: int, failed: int, errors: list<string>}
     */
    public function syncFromSmaregi(int $projectId): array
    {
        $contractId = $this->requireContractId($projectId);

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $page = 1;

        do {
            $result = $this->smaregiApi->getCoupons($contractId, [
                'limit' => self::LIMIT,
                'page' => $page,
            ]);
            if ($result === null) {
                throw new \RuntimeException('スマレジAPI（クーポン一覧）の取得に失敗しました。ログ(storage/logs/laravel.log)を確認してください。');
            }

            $list = $this->extractListFromResponse($result, 'coupons');
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $smaregiCouponId = trim((string) ($row['couponId'] ?? ''));
                if ($smaregiCouponId === '') {
                    continue;
                }
                try {
                    $attrs = $this->mapSmaregiCouponToAttributes($row);
                    $coupon = $this->upsertCouponFromSmaregi($projectId, $smaregiCouponId, $attrs);
                    if ($coupon->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = sprintf('couponId %s: %s', $smaregiCouponId, $e->getMessage());
                    Log::warning('[Smaregi] coupon import failed', [
                        'project_id' => $projectId,
                        'smaregi_coupon_id' => $smaregiCouponId,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
            $page++;
        } while (count($list) === self::LIMIT);

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * DB → スマレジ: 未連携クーポンを POS に登録する。
     *
     * @return array{pushed: int, failed: int, errors: list<string>}
     */
    public function pushUnlinkedToSmaregi(int $projectId): array
    {
        if (! $this->smaregiCouponSync->hasSmaregiContract($projectId)) {
            throw new \RuntimeException('スマレジの契約IDが設定されていません。設定画面で契約を紐付けてください。');
        }

        $pushed = 0;
        $failed = 0;
        $errors = [];

        $coupons = Coupon::where('project_id', $projectId)
            ->where(function ($q) {
                $q->whereNull('smaregi_coupon_id')->orWhere('smaregi_coupon_id', '');
            })
            ->orderBy('id')
            ->get();

        foreach ($coupons as $coupon) {
            try {
                if ($this->smaregiCouponSync->sync($coupon)) {
                    $pushed++;
                } else {
                    $failed++;
                    $errors[] = sprintf('%s (%s): POS への登録に失敗しました', $coupon->name ?? $coupon->code, $coupon->code);
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = sprintf('%s (%s): %s', $coupon->name ?? $coupon->code, $coupon->code, $e->getMessage());
                Log::warning('[Smaregi] coupon push failed', [
                    'project_id' => $projectId,
                    'coupon_id' => $coupon->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'pushed' => $pushed,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * 双方向同期: スマレジ取り込み + 未連携の POS 登録。
     *
     * @return array{created: int, updated: int, pushed: int, failed: int, errors: list<string>}
     */
    public function fullSync(int $projectId): array
    {
        $import = $this->syncFromSmaregi($projectId);
        $push = $this->pushUnlinkedToSmaregi($projectId);

        return [
            'created' => $import['created'],
            'updated' => $import['updated'],
            'pushed' => $push['pushed'],
            'failed' => $import['failed'] + $push['failed'],
            'errors' => array_merge($import['errors'], $push['errors']),
        ];
    }

    private function requireContractId(int $projectId): string
    {
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '') {
            throw new \RuntimeException('スマレジの契約IDが設定されていません。設定画面で契約を紐付けてください。');
        }

        return $contractId;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $attrs
     */
    private function upsertCouponFromSmaregi(int $projectId, string $smaregiCouponId, array $attrs): Coupon
    {
        $bySmaregi = Coupon::where('project_id', $projectId)
            ->where('smaregi_coupon_id', $smaregiCouponId)
            ->first();

        if ($bySmaregi !== null) {
            $bySmaregi->update($attrs);

            return $bySmaregi;
        }

        $code = trim((string) ($attrs['code'] ?? ''));
        if ($code !== '') {
            $byCode = Coupon::where('project_id', $projectId)
                ->where('code', $code)
                ->orderByRaw("CASE WHEN smaregi_coupon_id IS NULL OR smaregi_coupon_id = '' THEN 1 ELSE 0 END")
                ->orderBy('id')
                ->first();

            if ($byCode !== null) {
                $byCode->update(array_merge($attrs, ['smaregi_coupon_id' => $smaregiCouponId]));

                return $byCode;
            }
        }

        return Coupon::create(array_merge($attrs, [
            'project_id' => $projectId,
            'smaregi_coupon_id' => $smaregiCouponId,
        ]));
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $row
     * @return array<string, string|int|float|bool|null>
     */
    private function mapSmaregiCouponToAttributes(array $row): array
    {
        $code = trim((string) ($row['serialNumber'] ?? ''));
        if ($code === '') {
            throw new \InvalidArgumentException('serialNumber が空です');
        }

        $name = trim((string) ($row['couponName'] ?? ''));
        if ($name === '') {
            $name = $code;
        }

        $awardType = (string) ($row['awardType'] ?? '1');
        $type = $awardType === '2' ? 'percent' : 'fixed';

        $value = (float) ($row['awardValue'] ?? 0);
        $summary = isset($row['summary']) ? trim((string) $row['summary']) : null;
        $minTarget = isset($row['minTargetPrice']) ? (int) $row['minTargetPrice'] : 0;

        $attrs = [
            'code' => mb_substr($code, 0, 64),
            'name' => mb_substr($name, 0, 255),
            'description' => $summary !== '' && $summary !== null ? mb_substr($summary, 0, 2000) : null,
            'type' => $type,
            'value' => $value,
            'min_order_amount' => $minTarget > 0 ? $minTarget : 0,
            'is_active' => true,
            'issue_trigger' => Coupon::ISSUE_TRIGGER_MANUAL,
            'use_display' => Coupon::USE_DISPLAY_BARCODE,
            'design_template' => Coupon::DESIGN_CLASSIC,
        ];

        $startDate = $row['startDate'] ?? null;
        if ($startDate !== null && $startDate !== '') {
            $attrs['start_at'] = Carbon::parse((string) $startDate)->startOfDay();
        }

        $endDate = $row['endDate'] ?? null;
        if ($endDate !== null && $endDate !== '') {
            $attrs['end_at'] = Carbon::parse((string) $endDate)->endOfDay();
        }

        return $attrs;
    }

    /**
     * @param  array<int|string, string|int|float|bool|array|null>  $result
     * @return array<int|string, string|int|float|bool|array|null>
     */
    private function extractListFromResponse(array $result, string $listKey): array
    {
        if ($result === [] || array_keys($result) === range(0, count($result) - 1)) {
            return $result;
        }
        $list = $result[$listKey] ?? [];

        return is_array($list) ? $list : [];
    }
}

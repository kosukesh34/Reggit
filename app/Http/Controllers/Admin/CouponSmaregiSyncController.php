<?php

namespace App\Http\Controllers\Admin;

use App\Services\Smaregi\SmaregiCouponImportSyncService;
use App\UseCases\Admin\Coupon\AdminCouponFullSyncWithSmaregiUseCase;
use App\UseCases\Admin\Coupon\AdminCouponPushToSmaregiUseCase;
use App\UseCases\Admin\Coupon\AdminCouponSyncFromSmaregiUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CouponSmaregiSyncController extends AdminBaseController
{
    public function __construct(
        private AdminCouponSyncFromSmaregiUseCase $syncFromSmaregiUseCase,
        private AdminCouponPushToSmaregiUseCase $pushToSmaregiUseCase,
        private AdminCouponFullSyncWithSmaregiUseCase $fullSyncWithSmaregiUseCase,
        private SmaregiCouponImportSyncService $couponSyncService,
    ) {}

    /** スマレジ → DB: POS のクーポンを取り込む */
    public function pull(): RedirectResponse
    {
        try {
            $result = $this->syncFromSmaregiUseCase->handle($this->resolveAdminProjectId());
            $message = sprintf(
                'スマレジからクーポンを取り込みました（新規: %d、更新: %d、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['failed'],
            );

            return $this->successResponse($message, $result);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    /** DB → スマレジ: 全クーポンを登録または更新する。 */
    public function syncToSmaregi(): RedirectResponse
    {
        try {
            $result = $this->couponSyncService->syncToSmaregi($this->resolveAdminProjectId());
            $message = sprintf('スマレジへクーポンを同期しました（新規: %d、更新: %d、失敗: %d）',
                $result['created'], $result['updated'], $result['failed']);

            return $this->successResponse($message, $result, true);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception, true);
        }
    }

    /** DB → スマレジ: 未連携クーポンを POS に登録 */
    public function push(Request $request): RedirectResponse
    {
        try {
            $couponId = $request->validate(['coupon_id' => ['nullable', 'integer', 'min:1']])['coupon_id'] ?? null;
            $result = $this->pushToSmaregiUseCase->handle($this->resolveAdminProjectId(), $couponId);
            $message = sprintf(
                '管理画面のクーポンをスマレジに反映しました（登録: %d、失敗: %d）',
                $result['pushed'],
                $result['failed'],
            );

            return $this->successResponse($message, $result, $couponId !== null);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception, $request->filled('coupon_id'));
        }
    }

    /** スマレジ取り込みと未連携クーポン登録をまとめて実行する。 */
    public function sync(): RedirectResponse
    {
        try {
            $result = $this->fullSyncWithSmaregiUseCase->handle($this->resolveAdminProjectId());
            $message = sprintf(
                'クーポンを完全同期しました（スマレジ→DB: 新規 %d・更新 %d、DB→スマレジ: %d 件、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['pushed'],
                $result['failed'],
            );

            return $this->successResponse($message, $result);
        } catch (\Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    /** @param array{failed: int, errors: list<string>} $result */
    private function successResponse(string $message, array $result, bool $toIndex = false): RedirectResponse
    {
        if ($result['failed'] > 0 && $result['errors'] !== []) {
            $message .= '。'.implode('; ', array_slice($result['errors'], 0, 3));
        }

        return redirect()->route($toIndex ? 'admin.coupons' : 'admin.coupons.bulk-issue', $this->buildAdminRouteParams())
            ->with('success', $message);
    }

    private function errorResponse(\Throwable $exception, bool $toIndex = false): RedirectResponse
    {
        return redirect()->route($toIndex ? 'admin.coupons' : 'admin.coupons.bulk-issue', $this->buildAdminRouteParams())
            ->with('error', $exception->getMessage());
    }
}

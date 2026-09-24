<?php

namespace App\Http\Controllers\Api\V1\Analysis;

use App\Enums\CouponDeliveryType;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\Util\AdminContext;
use App\Util\LogHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AnalysisDeliveryController extends BaseApiController
{
    public function __construct(
        private AdminCouponBulkIssueUseCase $adminCouponBulkIssueUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_id' => ['required', 'integer'],
            'send_notification' => ['sometimes', 'boolean'],
        ]);
        $projectId = AdminContext::requireCurrentProjectId();
        $couponId = (int) $validated['coupon_id'];
        $sendNotification = $request->exists('send_notification')
            ? $request->boolean('send_notification')
            : true;

        try {
            $result = $this->adminCouponBulkIssueUseCase->handle($projectId, $couponId, [
                'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
                'send_notification' => $sendNotification,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            LogHelper::error('[Analysis] delivery failed', [
                'project_id' => $projectId,
                'coupon_id' => $couponId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('配信の実行に失敗しました', 500);
        }

        LogHelper::info('[Analysis] delivery executed', [
            'project_id' => $projectId,
            'coupon_id' => $couponId,
            'issued' => $result['issued'],
        ]);

        return $this->success([
            'issued' => $result['issued'],
            'scheduled' => $result['scheduled'],
            'message' => $result['message'],
        ], $result['message']);
    }
}

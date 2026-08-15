<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CouponDeliveryType;
use App\Http\Requests\Admin\CouponBulkIssueRequest;
use App\Http\Requests\Admin\CouponQuickCreateRequest;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\AdminSavePublicImageUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementAudiencePreviewUseCase;
use App\UseCases\Admin\Announcement\AdminAnnouncementSegmentPreviewUseCase;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeleteUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeliveryDetailUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeliveryPreviewMembersUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeliveryPreviewUseCase;
use App\UseCases\Admin\Coupon\AdminCouponEditFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponFullSyncWithSmaregiUseCase;
use App\UseCases\Admin\Coupon\AdminCouponIndexUseCase;
use App\UseCases\Admin\Coupon\AdminCouponPushToSmaregiUseCase;
use App\UseCases\Admin\Coupon\AdminCouponRedemptionsUseCase;
use App\UseCases\Admin\Coupon\AdminCouponStoreUseCase;
use App\UseCases\Admin\Coupon\AdminCouponSyncFromSmaregiUseCase;
use App\UseCases\Admin\Coupon\AdminCouponUpdateUseCase;
use App\UseCases\Admin\Member\AdminMemberSearchByKeywordUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class CouponController extends AdminBaseController
{
    public function __construct(
        private AdminCouponIndexUseCase $indexUseCase,
        private AdminCouponStoreUseCase $storeUseCase,
        private AdminCouponEditFormUseCase $editFormUseCase,
        private AdminCouponUpdateUseCase $updateUseCase,
        private AdminCouponDeleteUseCase $deleteUseCase,
        private AdminCouponBulkIssueFormUseCase $bulkIssueFormUseCase,
        private AdminCouponBulkIssueUseCase $bulkIssueUseCase,
        private AdminSavePublicImageUseCase $saveImageUseCase,
        private AdminCouponSyncFromSmaregiUseCase $syncFromSmaregiUseCase,
        private AdminCouponPushToSmaregiUseCase $pushToSmaregiUseCase,
        private AdminCouponFullSyncWithSmaregiUseCase $fullSyncWithSmaregiUseCase,
        private AdminCouponDeliveryPreviewUseCase $deliveryPreviewUseCase,
        private AdminCouponDeliveryPreviewMembersUseCase $deliveryPreviewMembersUseCase,
        private AdminCouponDeliveryDetailUseCase $deliveryDetailUseCase,
        private AdminMemberSearchByKeywordUseCase $searchMembersUseCase,
        private AdminCouponRedemptionsUseCase $redemptionsUseCase,
        private AdminAnnouncementSegmentPreviewUseCase $segmentPreviewUseCase,
        private AdminAnnouncementAudiencePreviewUseCase $audiencePreviewUseCase,
    ) {}

    public function coupons()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->indexUseCase->handle($projectId);

        return view('admin.coupons.index', [
            'coupons' => $output->coupons,
            'deliveryHistories' => $output->deliveryHistories,
        ]);
    }

    public function createCoupon()
    {
        $this->resolveAdminProjectId();

        return view('admin.coupons.create');
    }

    public function storeCoupon(CouponStoreRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $imageFile = $request->file('image');
        if ($imageFile instanceof UploadedFile) {
            $validated['image_url'] = $this->saveImageUseCase->handle(
                $imageFile,
                $projectId,
                PublicImageStorageService::DIR_COUPON,
            );
        }
        unset($validated['image']);
        try {
            $coupon = $this->storeUseCase->handle($projectId, $validated);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons.create', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $smaregiNote = $coupon->smaregi_coupon_id ? '（スマレジにも反映済み）' : '';

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました'.$smaregiNote);
    }

    public function editCoupon(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->editFormUseCase->handle($projectId, (int) $id);

        return view('admin.coupons.edit', [
            'coupon' => $output->coupon,
            'stampCards' => $output->stampCards,
        ]);
    }

    public function updateCoupon(CouponUpdateRequest $request, string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();
        $imageFile = $request->file('image');
        if ($imageFile instanceof UploadedFile) {
            $validated['image_url'] = $this->saveImageUseCase->handle(
                $imageFile,
                $projectId,
                PublicImageStorageService::DIR_COUPON,
            );
        }
        unset($validated['image']);
        try {
            $this->updateUseCase->handle((int) $id, $validated, $projectId);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons.edit', array_merge($this->buildAdminRouteParams(), ['id' => $id]))
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    public function deleteCoupon(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $this->deleteUseCase->handle((int) $id, $projectId);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを削除しました');
    }

    public function couponRedemptions(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->redemptionsUseCase->handle($projectId, (int) $id);

        return view('admin.coupons.redemptions', ['view' => $output]);
    }

    /** プレゼント配信（クーポン一括配布）画面 */
    public function bulkIssueForm()
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->bulkIssueFormUseCase->handle($projectId);

        return view('admin.coupons.bulk-issue', ['view' => $output]);
    }

    /** 一括配布画面からのクイック作成（スマレジ契約ありの場合は POS にも登録） */
    public function quickCreateCoupon(CouponQuickCreateRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        try {
            $coupon = $this->storeUseCase->handle($projectId, $validated, true);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons.bulk-issue', array_merge($this->buildAdminRouteParams(), ['coupon_source' => 'existing']))
            ->with('success', 'クーポンを作成しました'.($coupon->smaregi_coupon_id ? '（スマレジにも反映済み）' : '').'。配布設定に進んでください。')
            ->withInput(['coupon_id' => $coupon->id, 'coupon_source' => 'existing']);
    }

    /** スマレジ → DB: POS のクーポンを取り込む */
    public function syncCouponsFromSmaregi()
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $result = $this->syncFromSmaregiUseCase->handle($projectId);
            $msg = sprintf(
                'スマレジからクーポンを取り込みました（新規: %d、更新: %d、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['failed']
            );
            if ($result['failed'] > 0 && $result['errors'] !== []) {
                $msg .= '。'.implode('; ', array_slice($result['errors'], 0, 3));
            }

            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }
    }

    /** DB → スマレジ: 未連携クーポンを POS に登録 */
    public function pushCouponsToSmaregi()
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $result = $this->pushToSmaregiUseCase->handle($projectId);
            $msg = sprintf(
                '管理画面のクーポンをスマレジに反映しました（登録: %d、失敗: %d）',
                $result['pushed'],
                $result['failed']
            );
            if ($result['failed'] > 0 && $result['errors'] !== []) {
                $msg .= '。'.implode('; ', array_slice($result['errors'], 0, 3));
            }

            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }
    }

    /** クーポン双方向同期（スマレジ取り込み + 未連携の POS 登録） */
    public function fullSyncCouponsWithSmaregi()
    {
        $projectId = $this->resolveAdminProjectId();
        try {
            $result = $this->fullSyncWithSmaregiUseCase->handle($projectId);
            $msg = sprintf(
                'クーポンを完全同期しました（スマレジ→DB: 新規 %d・更新 %d、DB→スマレジ: %d 件、失敗: %d）',
                $result['created'],
                $result['updated'],
                $result['pushed'],
                $result['failed']
            );
            if ($result['failed'] > 0 && $result['errors'] !== []) {
                $msg .= '。'.implode('; ', array_slice($result['errors'], 0, 3));
            }

            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }
    }

    /** プレゼント配信（クーポン一括配布）実行 */
    public function bulkIssue(CouponBulkIssueRequest $request)
    {
        $projectId = $this->resolveAdminProjectId();
        $validated = $request->validated();

        try {
            $result = $this->bulkIssueUseCase->handle($projectId, (int) $validated['coupon_id'], $validated);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.coupons.bulk-issue', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', $result['message']);
    }

    public function searchMembers(Request $request)
    {
        $members = $this->searchMembersUseCase->handle(
            $this->resolveAdminProjectId(),
            (string) $request->query('q', ''),
        );

        return response()->json(['data' => $members]);
    }

    public function segmentPreview(Request $request)
    {
        $count = $this->segmentPreviewUseCase->handle(
            $this->resolveAdminProjectId(),
            $request->query(),
        );

        return response()->json(['count' => $count]);
    }

    public function audiencePreview(Request $request)
    {
        $query = $request->query();
        if (! isset($query['delivery_coupon_id']) && $request->filled('coupon_id')) {
            $query['delivery_coupon_id'] = $request->query('coupon_id');
        }

        return response()->json(
            $this->audiencePreviewUseCase->handle($this->resolveAdminProjectId(), $query),
        );
    }

    public function deliveryPreview()
    {
        $projectId = $this->resolveAdminProjectId();
        $deliveryType = (string) request()->query('delivery_type', CouponDeliveryType::IMMEDIATE_ALL);
        $params = $this->deliveryPreviewParams();

        $result = $this->deliveryPreviewUseCase->handle($projectId, $deliveryType, $params);

        return response()->json($result);
    }

    public function deliveryPreviewMembers()
    {
        $projectId = $this->resolveAdminProjectId();
        $deliveryType = (string) request()->query('delivery_type', CouponDeliveryType::IMMEDIATE_ALL);
        $params = $this->deliveryPreviewParams();
        $output = $this->deliveryPreviewMembersUseCase->handle($projectId, $deliveryType, $params);

        return response()->json([
            'members' => $output->members,
            'total' => $output->totalCount,
            'error' => $output->error,
        ]);
    }

    public function deliveryShow(string $projectKey, $id)
    {
        $projectId = $this->resolveAdminProjectId();
        $output = $this->deliveryDetailUseCase->handle($projectId, (int) $id);

        return view('admin.coupons.delivery-detail', ['view' => $output]);
    }

    /** @return array<string, mixed> */
    private function deliveryPreviewParams(): array
    {
        $params = request()->only([
            'target_member_ids',
            'member_ids_input',
            'segment_period_days',
            'segment_condition',
            'segment_min_amount',
            'segment_channel',
            'segment_birthday_month',
            'segment_sex',
            'segment_visit_count_min',
            'segment_visit_count_max',
            'segment_rank',
            'segment_product_id',
            'segment_lifetime_points_min',
            'segment_lifetime_points_max',
            'segment_last_visit_mode',
            'segment_last_visit_days',
            'segment_stamp_card_id',
            'segment_stamp_count_min',
            'segment_stamp_completed',
            'segment_rfm_segments',
            'segment_tag_ids',
            'segment_birthday_months',
            'birthday_month',
            'new_member_days',
            'inactive_days',
            'target_rank',
            'stamp_card_id',
            'stamp_count_min',
            'stamp_completed',
            'days_after_last_visit',
        ]);

        if (request()->has('target_member_ids') && is_string($params['target_member_ids'] ?? null)) {
            $params['target_member_ids'] = array_filter(array_map('intval', explode(',', $params['target_member_ids'])));
        }

        return $params;
    }
}

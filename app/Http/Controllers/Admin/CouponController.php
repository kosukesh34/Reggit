<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponBulkIssueRequest;
use App\Http\Requests\Admin\CouponQuickCreateRequest;
use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use App\UseCases\Admin\Coupon\AdminCouponDeleteUseCase;
use App\UseCases\Admin\Coupon\AdminCouponEditFormUseCase;
use App\UseCases\Admin\Coupon\AdminCouponIndexUseCase;
use App\UseCases\Admin\Coupon\AdminCouponRedemptionsUseCase;
use App\UseCases\Admin\Coupon\AdminCouponStoreUseCase;
use App\UseCases\Admin\Coupon\AdminCouponUpdateUseCase;
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
        private PublicImageStorageService $publicImageStorage,
        private AdminCouponRedemptionsUseCase $redemptionsUseCase,
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
            $validated['image_url'] = $this->publicImageStorage->store(
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
            $validated['image_url'] = $this->publicImageStorage->store(
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
}

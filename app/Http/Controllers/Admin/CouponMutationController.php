<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CouponStoreRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Coupon\AdminCouponDeleteUseCase;
use App\UseCases\Admin\Coupon\AdminCouponStoreUseCase;
use App\UseCases\Admin\Coupon\AdminCouponUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CouponMutationController extends AdminBaseController
{
    public function __construct(
        private AdminCouponStoreUseCase $storeUseCase,
        private AdminCouponUpdateUseCase $updateUseCase,
        private AdminCouponDeleteUseCase $deleteUseCase,
        private PublicImageStorageService $publicImageStorage,
    ) {}

    public function store(CouponStoreRequest $request): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();

        try {
            $coupon = $this->storeUseCase->handle($projectId, $this->payload($request, $projectId));
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.coupons.create', $this->buildAdminRouteParams())
                ->withInput()->with('error', $exception->getMessage());
        }

        $smaregiNote = $coupon->smaregi_coupon_id ? '（スマレジにも反映済み）' : '';

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを作成しました'.$smaregiNote);
    }

    public function update(CouponUpdateRequest $request, string $projectKey, int $id): RedirectResponse
    {
        $projectId = $this->resolveAdminProjectId();

        try {
            $this->updateUseCase->handle($id, $this->payload($request, $projectId), $projectId);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.coupons.edit', array_merge($this->buildAdminRouteParams(), ['id' => $id]))
                ->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを更新しました');
    }

    public function destroy(string $projectKey, int $id): RedirectResponse
    {
        try {
            $this->deleteUseCase->handle($id, $this->resolveAdminProjectId());
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.coupons', $this->buildAdminRouteParams())
            ->with('success', 'クーポンを削除しました');
    }

    /** @return array<string, mixed> */
    private function payload(CouponStoreRequest|CouponUpdateRequest $request, int $projectId): array
    {
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

        return $validated;
    }
}

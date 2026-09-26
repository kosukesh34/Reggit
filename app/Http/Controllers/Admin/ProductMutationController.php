<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\Product\AdminProductDeleteUseCase;
use App\UseCases\Admin\Product\AdminProductStoreUseCase;
use App\UseCases\Admin\Product\AdminProductUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ProductMutationController extends AdminBaseController
{
    public function __construct(
        private AdminProductStoreUseCase $storeUseCase,
        private AdminProductUpdateUseCase $updateUseCase,
        private AdminProductDeleteUseCase $deleteUseCase,
        private PublicImageStorageService $publicImageStorage,
    ) {}

    public function store(ProductStoreRequest $request): RedirectResponse
    {
        try {
            $this->storeUseCase->handle($this->resolveAdminProjectId(), $this->productPayload($request));
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.products.create', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を追加しました');
    }

    public function update(ProductUpdateRequest $request, string $projectKey, int $id): RedirectResponse
    {
        try {
            $this->updateUseCase->handle($id, $this->productPayload($request));
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.products.edit', array_merge(
                $this->buildAdminRouteParams(),
                ['id' => $id],
            ))
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を更新しました');
    }

    public function destroy(string $projectKey, int $id): RedirectResponse
    {
        try {
            $this->deleteUseCase->handle($id);
        } catch (RuntimeException $exception) {
            return redirect()->route('admin.products', $this->buildAdminRouteParams())
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を削除しました');
    }

    /** @return array<string, mixed> */
    private function productPayload(ProductStoreRequest|ProductUpdateRequest $request): array
    {
        $validated = $request->validated();
        unset($validated['image'], $validated['image_clear']);

        $file = $request->file('image');
        if ($file instanceof UploadedFile) {
            $validated['image_url'] = $this->publicImageStorage->store(
                $file,
                $this->resolveAdminProjectId(),
                PublicImageStorageService::DIR_PRODUCT,
            );
        } elseif ($request->input('image_clear') === '1') {
            $validated['image_url'] = '';
        }

        return $validated;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\AdminSavePublicImageUseCase;
use App\UseCases\Admin\Product\AdminProductDeleteUseCase;
use App\UseCases\Admin\Product\AdminProductEditUseCase;
use App\UseCases\Admin\Product\AdminProductIndexUseCase;
use App\UseCases\Admin\Product\AdminProductStoreUseCase;
use App\UseCases\Admin\Product\AdminProductUpdateUseCase;
use Illuminate\Http\UploadedFile;

class ProductController extends AdminBaseController
{
    private AdminProductIndexUseCase $indexUseCase;

    private AdminProductStoreUseCase $storeUseCase;

    private AdminProductEditUseCase $editUseCase;

    private AdminProductUpdateUseCase $updateUseCase;

    private AdminProductDeleteUseCase $deleteUseCase;

    public function __construct(
        AdminProductIndexUseCase $indexUseCase,
        AdminProductStoreUseCase $storeUseCase,
        AdminProductEditUseCase $editUseCase,
        AdminProductUpdateUseCase $updateUseCase,
        AdminProductDeleteUseCase $deleteUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->storeUseCase = $storeUseCase;
        $this->editUseCase = $editUseCase;
        $this->updateUseCase = $updateUseCase;
        $this->deleteUseCase = $deleteUseCase;
    }

    public function products()
    {
        $products = $this->indexUseCase->handle();

        return view('admin.products', compact('products'));
    }

    public function createProduct()
    {
        return view('admin.product-form');
    }

    public function storeProduct(ProductStoreRequest $request)
    {
        try {
            $this->storeUseCase->handle($this->resolveAdminProjectId(), $this->productPayload($request));
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.products.create', $this->buildAdminRouteParams())
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を追加しました');
    }

    public function editProduct(string $projectKey, $id)
    {
        $product = $this->editUseCase->handle($this->resolveAdminProjectId(), (int) $id);

        return view('admin.product-form', compact('product'));
    }

    public function updateProduct(ProductUpdateRequest $request, string $projectKey, $id)
    {
        try {
            $this->updateUseCase->handle((int) $id, $this->productPayload($request));
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.products.edit', array_merge($this->buildAdminRouteParams(), ['id' => $id]))
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を更新しました');
    }

    public function deleteProduct(string $projectKey, $id)
    {
        try {
            $this->deleteUseCase->handle((int) $id);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.products', $this->buildAdminRouteParams())
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.products', $this->buildAdminRouteParams())
            ->with('success', '商品を削除しました');
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(ProductStoreRequest|ProductUpdateRequest $request): array
    {
        $validated = $request->validated();
        unset($validated['image'], $validated['image_clear']);
        $file = $request->file('image');
        if ($file instanceof UploadedFile) {
            $validated['image_url'] = resolve(AdminSavePublicImageUseCase::class)->handle(
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

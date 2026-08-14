<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Services\Admin\PublicImageStorageService;
use App\UseCases\Admin\AdminSavePublicImageUseCase;
use App\UseCases\Admin\Gallery\GalleryItemDestroyUseCase;
use App\UseCases\Admin\Gallery\GalleryItemIndexUseCase;
use App\UseCases\Admin\Gallery\GalleryItemStoreUseCase;
use App\UseCases\Admin\Gallery\GalleryItemUpdateUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ItemController extends AdminBaseController
{
    public function index(
        Request $request,
        GalleryItemIndexUseCase $useCase
    ): View {
        $categoryId = $request->query('category_id')
            ? (int) $request->query('category_id')
            : null;

        $view = $useCase->handle($categoryId);

        return view('admin.gallery.items', [
            'items' => $view->items,
            'categories' => $view->categories,
        ]);
    }

    public function store(
        Request $request,
        GalleryItemStoreUseCase $useCase
    ): RedirectResponse {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image' => 'required|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $imageFile = $request->file('image');
        if (! $imageFile instanceof UploadedFile) {
            return redirect()
                ->back()
                ->withErrors(['image' => '画像を選択してください'])
                ->withInput();
        }
        $validated['image_url'] = resolve(AdminSavePublicImageUseCase::class)->handle(
            $imageFile,
            $this->resolveAdminProjectId(),
            PublicImageStorageService::DIR_GALLERY,
        );
        unset($validated['image']);
        $validated['is_active'] = $request->boolean('is_active');

        $useCase->handle($validated, $this->resolveAdminProjectId());

        return redirect()
            ->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を追加しました');
    }

    public function update(
        Request $request,
        int $id,
        GalleryItemUpdateUseCase $useCase
    ): RedirectResponse {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
            'image_url' => 'nullable|url|max:2048',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $imageFile = $request->file('image');
        if ($imageFile instanceof UploadedFile) {
            $validated['image_url'] = resolve(AdminSavePublicImageUseCase::class)->handle(
                $imageFile,
                $this->resolveAdminProjectId(),
                PublicImageStorageService::DIR_GALLERY,
            );
        }
        unset($validated['image']);
        $validated['is_active'] = $request->boolean('is_active');

        $useCase->handle($id, $validated);

        return redirect()
            ->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を更新しました');
    }

    public function delete(
        int $id,
        GalleryItemDestroyUseCase $useCase
    ): RedirectResponse {
        $useCase->handle($id);

        return redirect()
            ->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を削除しました');
    }
}

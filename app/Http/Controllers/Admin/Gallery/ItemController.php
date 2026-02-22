<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Gallery\GalleryItemDestroyUseCase;
use App\UseCases\Admin\Gallery\GalleryItemIndexUseCase;
use App\UseCases\Admin\Gallery\GalleryItemStoreUseCase;
use App\UseCases\Admin\Gallery\GalleryItemUpdateUseCase;
use Illuminate\Http\Request;

class ItemController extends AdminBaseController
{
    public function index(
        Request $request,
        GalleryItemIndexUseCase $useCase
    ): \Illuminate\View\View {
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
    ): \Illuminate\Http\RedirectResponse {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image_url' => 'required|url|max:500',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
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
    ): \Illuminate\Http\RedirectResponse {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:gallery_categories,id',
            'title' => 'nullable|string|max:150',
            'image_url' => 'required|url|max:500',
            'description' => 'nullable|string|max:1000',
            'size' => 'nullable|in:small,medium,large',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $useCase->handle($id, $validated);

        return redirect()
            ->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を更新しました');
    }

    public function delete(
        int $id,
        GalleryItemDestroyUseCase $useCase
    ): \Illuminate\Http\RedirectResponse {
        $useCase->handle($id);

        return redirect()
            ->route('admin.gallery.items', $this->buildAdminRouteParams())
            ->with('success', '画像を削除しました');
    }
}

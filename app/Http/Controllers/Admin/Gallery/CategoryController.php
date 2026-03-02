<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Gallery\GalleryCategoryDestroyUseCase;
use App\UseCases\Admin\Gallery\GalleryCategoryIndexUseCase;
use App\UseCases\Admin\Gallery\GalleryCategoryStoreUseCase;
use App\UseCases\Admin\Gallery\GalleryCategoryUpdateUseCase;
use Illuminate\Http\Request;

class CategoryController extends AdminBaseController
{
    public function index(GalleryCategoryIndexUseCase $useCase): \Illuminate\View\View
    {
        $categories = $useCase->handle();

        return view('admin.gallery.categories', compact('categories'));
    }

    public function store(
        Request $request,
        GalleryCategoryStoreUseCase $useCase
    ): \Illuminate\Http\RedirectResponse {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $useCase->handle($validated, $this->resolveAdminProjectId());

        return redirect()
            ->route('admin.gallery.categories', $this->buildAdminRouteParams())
            ->with('success', 'カテゴリを追加しました');
    }

    public function update(
        Request $request,
        int $id,
        GalleryCategoryUpdateUseCase $useCase
    ): \Illuminate\Http\RedirectResponse {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable',
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $useCase->handle($id, $validated);

        return redirect()
            ->route('admin.gallery.categories', $this->buildAdminRouteParams())
            ->with('success', 'カテゴリを更新しました');
    }

    public function delete(
        int $id,
        GalleryCategoryDestroyUseCase $useCase
    ): \Illuminate\Http\RedirectResponse {
        $useCase->handle($id);

        return redirect()
            ->route('admin.gallery.categories', $this->buildAdminRouteParams())
            ->with('success', 'カテゴリを削除しました');
    }
}

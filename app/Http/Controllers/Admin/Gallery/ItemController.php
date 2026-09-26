<?php

namespace App\Http\Controllers\Admin\Gallery;

use App\Http\Controllers\Admin\AdminBaseController;
use App\UseCases\Admin\Gallery\GalleryItemIndexUseCase;
use Illuminate\Http\Request;
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
}

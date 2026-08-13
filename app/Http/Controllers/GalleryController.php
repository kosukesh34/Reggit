<?php

namespace App\Http\Controllers;

use App\UseCases\Gallery\GalleryIndexUseCase;
use App\Util\LogHelper;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(private GalleryIndexUseCase $indexUseCase) {}

    public function index(Request $request)
    {
        try {
            $categoryId = $request->query('category_id');
            $categoryId = $categoryId !== null ? (int) $categoryId : null;

            $result = $this->indexUseCase->handle($categoryId);

            return response()->json([
                'categories' => $result->categories,
                'items' => $result->items,
            ]);
        } catch (\Exception $e) {
            LogHelper::error('[Gallery] index failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'ギャラリーの取得に失敗しました'], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Util\AdminContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $projectId = AdminContext::getCurrentProjectId() ?? 1;
            $categoryId = $request->query('category_id');

            $categories = GalleryCategory::where('project_id', $projectId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $itemsQuery = GalleryItem::where('project_id', $projectId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id');
            if ($categoryId) {
                $itemsQuery->where('category_id', $categoryId);
            }
            $items = $itemsQuery->get();

            return response()->json([
                'categories' => $categories,
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            Log::error('Gallery index error: ' . $e->getMessage());
            return response()->json(['error' => 'ギャラリーの取得に失敗しました'], 500);
        }
    }
}

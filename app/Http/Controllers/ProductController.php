<?php

namespace App\Http\Controllers;

use App\UseCases\Product\ProductIndexUseCase;
use App\UseCases\Product\ProductShowUseCase;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductIndexUseCase $indexUseCase,
        private ProductShowUseCase $showUseCase
    ) {}

    public function index(Request $request)
    {
        $category = $request->query('category');
        if ($category === null) {
            $category = $request->get('category');
        }

        $result = $this->indexUseCase->handle($category, true, true);

        return response()->json([
            'products' => $result->products,
            'categories' => $result->categoriesToArray(),
        ]);
    }

    public function show($id)
    {
        $product = $this->showUseCase->handle((int) $id);

        return response()->json($product);
    }
}

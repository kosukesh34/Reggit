<?php

namespace App\Http\Controllers\Api\V1;

use App\UseCases\Product\ProductIndexUseCase;
use App\UseCases\Product\ProductShowUseCase;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    public function __construct(
        private ProductIndexUseCase $indexUseCase,
        private ProductShowUseCase $showUseCase
    ) {}

    public function index(Request $request)
    {
        $category = $request->query('category');
        $availableQuery = $request->query('available');

        $available = null;
        if ($availableQuery !== null) {
            $available = $availableQuery === 'true' || $availableQuery === '1';
        }

        $result = $this->indexUseCase->handle($category, $available, false);

        return $this->success(['products' => $result->products]);
    }

    public function show(int $id)
    {
        $product = $this->showUseCase->handle($id);

        return $this->success(['product' => $product]);
    }
}

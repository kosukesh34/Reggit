<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->has('available')) {
            $available = $request->query('available') === 'true' || $request->query('available') === '1';
            $query->where('is_available', $available);
        }

        $products = $query->orderBy('category')->orderBy('name')->get();

        return $this->success(['products' => $products]);
    }

    public function show(int $id)
    {
        $product = Product::findOrFail($id);
        return $this->success(['product' => $product]);
    }
}

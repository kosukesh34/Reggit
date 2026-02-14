<?php

namespace App\Services\Admin;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Product::orderByDesc('id')->paginate($perPage);
    }

    public function create(int $projectId, array $data): Product
    {
        $data['project_id'] = $projectId;
        return Product::create($data);
    }

    public function findOrFail(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    public function delete(int $id): void
    {
        Product::whereKey($id)->delete();
    }
}

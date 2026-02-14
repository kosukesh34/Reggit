<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string|url|max:500',
            'category' => 'nullable|string|max:64',
            'is_available' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0',
        ];
    }
}

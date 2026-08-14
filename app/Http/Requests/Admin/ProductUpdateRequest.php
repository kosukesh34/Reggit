<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->boolean('stock_unlimited')) {
            $this->merge(['stock' => null]);
        }
    }

    public function rules(): array
    {
        $stockRules = $this->boolean('stock_unlimited')
            ? ['nullable', 'integer', 'min:0']
            : ['required', 'integer', 'min:0'];

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string|url|max:500',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp|max:2048',
            'image_clear' => 'nullable|in:0,1',
            'category' => 'nullable|string|max:64',
            'is_available' => 'nullable|boolean',
            'stock_unlimited' => 'nullable|boolean',
            'stock' => $stockRules,
        ];
    }
}

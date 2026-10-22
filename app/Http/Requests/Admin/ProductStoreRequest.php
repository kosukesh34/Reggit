<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = ['is_available' => $this->boolean('is_available')];
        if ($this->boolean('stock_unlimited')) {
            $merged['stock'] = null;
        }
        if (! $this->boolean('ticket_enabled')) {
            $merged['ticket_uses'] = null;
            $merged['ticket_valid_days'] = null;
        }
        $this->merge($merged);
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
            'ticket_enabled' => 'nullable|boolean',
            'ticket_uses' => 'nullable|required_if_accepted:ticket_enabled|integer|min:1|max:1000',
            'ticket_valid_days' => 'nullable|integer|min:1|max:3650',
            'option_groups' => 'nullable|array|max:10',
            'option_groups.*.name' => 'required|string|max:100',
            'option_groups.*.selection_type' => 'required|in:single,multiple',
            'option_groups.*.is_required' => 'nullable|boolean',
            'option_groups.*.options' => 'required|array|min:1|max:20',
            'option_groups.*.options.*.name' => 'required|string|max:100',
            'option_groups.*.options.*.price_delta' => 'required|numeric|min:-999999|max:999999',
        ];
    }
}

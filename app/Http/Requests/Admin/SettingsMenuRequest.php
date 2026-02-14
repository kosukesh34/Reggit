<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTabs = ['home', 'products', 'orders', 'stamps', 'coupons', 'queue', 'reservations', 'member', 'gallery'];
        return [
            'menu_items' => 'required|array',
            'menu_items.*.tab' => 'required|string|in:' . implode(',', $validTabs),
            'menu_items.*.label' => 'nullable|string|max:50',
            'menu_items.*.icon' => 'nullable|string|max:50',
            'menu_items.*.enabled' => 'nullable',
            'menu_items.*.order' => 'nullable|integer|min:0',
        ];
    }
}

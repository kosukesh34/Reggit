<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:64',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image_url' => 'nullable|string|url|max:500',
            'image' => 'nullable|file|image|max:2048',
            'use_display' => 'nullable|string|in:none,barcode,qr,both',
            'type' => 'required|string|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_active' => 'nullable|boolean',
            'issue_trigger' => 'nullable|string|in:manual,first_registration,first_order,stamp_completed,birthday',
            'design_template' => 'nullable|string|in:classic,modern,minimal,gradient,card,barcode,qr,stripe,ticket,vintage,neon',
            'theme_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/|max:7',
            'stamp_card_id' => 'nullable|integer|exists:stamp_cards,id',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use App\Enums\StampGrantLimit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StampQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermission('stamps.manage') === true;
    }

    /** @return array<string, string|array<int, mixed>> */
    public function rules(): array
    {
        return [
            'stamp_card_id' => 'required|integer|min:1',
            'amount' => 'required|integer|min:1|max:'.StampGrantLimit::MAX_AMOUNT,
            'expires_minutes' => ['required', 'integer', Rule::in(array_keys(StampGrantLimit::EXPIRES_OPTIONS))],
            'limit' => ['required', Rule::in(StampGrantLimit::keys())],
            'quota' => 'required|integer|min:1|max:'.StampGrantLimit::MAX_QUOTA,
        ];
    }
}

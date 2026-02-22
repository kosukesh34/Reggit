<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRanksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rank_basis' => 'required|in:points,stamps,max',
            'point_earn_rate' => 'required|integer|min:1|max:100000',
            'points_valid_days' => 'required|integer|min:1|max:3650',
            'ranks' => 'array',
            'ranks.*.key' => 'nullable|string|max:50',
            'ranks.*.label' => 'nullable|string|max:50',
            'ranks.*.min_points' => 'nullable|integer|min:0|max:10000000',
            'ranks.*.order' => 'nullable|integer|min:0|max:999',
            'ranks.*.is_active' => 'nullable',
            'ranks.*.icon_url' => 'nullable|url|max:500',
            'ranks.*.stamp_background_url' => 'nullable|url|max:500',
        ];
    }

    /** @return array{rank_basis: string, point_earn_rate: int, points_valid_days: int, ranks: array<int, array{key?: string, label?: string, min_points?: int, order?: int, is_active?: bool, icon_url?: string|null, stamp_background_url?: string|null}>} */
    public function toPayload(): array
    {
        return $this->validated();
    }
}

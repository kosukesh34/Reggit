<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PointGrantQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->hasPermission('members.manage') === true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return ['points' => 'required|integer|min:1|max:100000', 'expires_minutes' => 'required|integer|min:1|max:525600', 'usage_quota' => 'required|integer|min:1|max:10000'];
    }
}

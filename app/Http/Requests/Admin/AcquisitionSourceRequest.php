<?php

namespace App\Http\Requests\Admin;

use App\Support\AppScreenCatalog;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcquisitionSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:40', Rule::unique('acquisition_sources')->where('project_id', AdminContext::requireCurrentProjectId())],
            'landing_tab' => ['nullable', Rule::in(array_keys(AppScreenCatalog::tabs()))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.unique' => '同じ名前の経路があります。'];
    }
}

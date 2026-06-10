<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CouponBulkIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon_id' => ['required', 'integer', Rule::exists('coupons', 'id')],
            'target' => ['required', Rule::in(['all', 'selected'])],
            'member_ids_input' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'coupon_id.required' => 'クーポンを選択してください',
            'target.in' => '配布対象を選択してください',
            'member_ids_input.required' => '会員を選択して配布では、対象会員のIDを1件以上入力してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('target') !== 'selected') {
                return;
            }

            $parsedIds = $this->parsedMemberIds();
            if ($parsedIds === []) {
                $validator->errors()->add(
                    'member_ids_input',
                    '会員を選択して配布では、対象会員のIDを1件以上入力してください'
                );

                return;
            }

            $projectId = AdminContext::getCurrentProjectId();
            if ($projectId === null) {
                return;
            }

            $existingCount = Member::withoutGlobalScopes()
                ->where('project_id', $projectId)
                ->whereIn('id', $parsedIds)
                ->count();

            if ($existingCount === 0) {
                $validator->errors()->add('member_ids_input', '入力された会員IDが見つかりません');
            }
        });
    }

    /** @return list<int> */
    public function parsedMemberIds(): array
    {
        $input = (string) $this->input('member_ids_input', '');

        return array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', trim($input)))));
    }
}

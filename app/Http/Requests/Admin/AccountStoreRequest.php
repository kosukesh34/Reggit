<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class AccountStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AdminUser $admin */
        $admin = $this->user('admin');
        if ($admin === null) {
            return false;
        }
        // 作成しようとしているロールを作成可能か
        $role = $this->input('role', '');

        return $admin->canCreateRole($role);
    }

    public function rules(): array
    {
        return [
            'login_id' => 'required|string|max:100|unique:admin_users,login_id|regex:/^[a-zA-Z0-9_-]+$/',
            'email' => 'required|email|unique:admin_users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'name' => 'required|string|max:255',
            'role' => 'required|in:project_admin',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|max:50',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->role !== AdminUser::ROLE_SYSTEM_ADMIN) {
                $ids = array_values(array_unique($this->project_ids ?? []));
                if ($ids === []) {
                    $validator->errors()->add(
                        'project_ids',
                        'システム管理者以外は1つ以上のプロジェクトを選択してください。'
                    );
                }
            }
        });
    }

    /** サービス層／Model に渡すアカウント作成用ペイロード */
    public function toAccountPayload(): array
    {
        $validated = $this->validated();
        unset($validated['password_confirmation'], $validated['project_ids'], $validated['permissions']);

        if ($validated['role'] === AdminUser::ROLE_SYSTEM_ADMIN) {
            $validated['project_id'] = null;
        } else {
            $ids = $this->toProjectIds();
            $validated['project_id'] = $ids[0] ?? null;
        }

        $validated['created_by_id'] = $this->user('admin')?->id;

        return $validated;
    }

    /** 紐づけるプロジェクトIDの配列 */
    public function toProjectIds(): array
    {
        if ($this->role === AdminUser::ROLE_SYSTEM_ADMIN) {
            return Project::pluck('id')->all();
        }

        return array_values(array_unique($this->validated('project_ids') ?? []));
    }

    /** 権限キーの配列（staff ロール用） */
    public function toPermissions(): array
    {
        return $this->validated('permissions') ?? [];
    }
}

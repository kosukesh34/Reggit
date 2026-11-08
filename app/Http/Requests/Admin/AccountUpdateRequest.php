<?php

namespace App\Http\Requests\Admin;

use App\Models\AdminUser;
use Illuminate\Validation\Rules\Password;

class AccountUpdateRequest extends AccountStoreRequest
{
    public function authorize(): bool
    {
        /** @var AdminUser $admin */
        $admin = $this->user('admin');
        if ($admin === null) {
            return false;
        }
        $target = AdminUser::find($this->route('id'));

        return $target !== null && $target->isProjectAdmin() && $admin->canEditUser($target);
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'login_id' => 'required|string|max:100|unique:admin_users,login_id,'.$id.'|regex:/^[a-zA-Z0-9_-]+$/',
            'email' => 'required|email|unique:admin_users,email,'.$id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ] + parent::rules();
    }

    /** サービス層／Model に渡すアカウント更新用ペイロード（パスワードは別途更新する） */
    public function toAccountPayload(): array
    {
        $payload = parent::toAccountPayload();
        unset($payload['password'], $payload['created_by_id']);

        return $payload;
    }
}

<?php

namespace App\Http\Requests;

use App\Services\MemberService;
use App\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;

class MemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = resolve(UserService::class)->getOrCreateUser($this);
        $member = $user->member;
        $projectId = $member !== null
            ? (int) $member->project_id
            : (int) ($user->project_id ?? 1);

        return resolve(MemberService::class)->getProfileValidationRules($projectId);
    }
}

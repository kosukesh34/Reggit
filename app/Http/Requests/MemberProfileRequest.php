<?php

namespace App\Http\Requests;

use App\Rules\ProfileValidationRules;
use App\Services\MemberProfileRules;
use App\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;

class MemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(UserService $users, MemberProfileRules $profileRules): array
    {
        $user = $users->getOrCreateUser($this);
        $member = $user->member;
        $projectId = $member !== null
            ? (int) $member->project_id
            : (int) ($user->project_id ?? 0);

        return $profileRules->getProfileValidationRules($projectId);
    }

    public function messages(): array
    {
        return ProfileValidationRules::messages();
    }
}

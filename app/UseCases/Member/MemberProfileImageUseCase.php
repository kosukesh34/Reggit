<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\MemberService;
use App\UseCases\Member\Data\MemberData;
use Illuminate\Http\UploadedFile;

class MemberProfileImageUseCase
{
    public function __construct(
        private MemberService $memberService,
    ) {}

    public function store(User $user, UploadedFile $image): MemberData
    {
        return new MemberData($this->memberService->saveProfileImage($user, $image));
    }

    public function destroy(User $user): MemberData
    {
        return new MemberData($this->memberService->deleteProfileImage($user));
    }
}
